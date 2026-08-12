<?php
// ============================================================
// Neilos Portal — Shared Analytics & Aggregation Engine
// Single Source of Truth for Dashboard & Reports Modules
// ============================================================

if (!function_exists('getSystemAnalyticsData')) {
    /**
     * Retrieves aggregated system analytics, KPIs, chart data, and records.
     * Enforces role permissions and supports dynamic report filters.
     *
     * @param array $filters Filters: start_date, end_date, service_type, status, partner_id, customer_name
     * @return array
     */
    function getSystemAnalyticsData(array $filters = []): array {
        $db   = getDB();
        $user = currentUser();
        $pw   = partnerWhere('o');
        $pwK  = partnerWhere('k');

        $whereConditions = ["{$pw['condition']}"];
        $params = $pw['params'];

        // 1. Start Date Filter
        if (!empty($filters['start_date'])) {
            $whereConditions[] = "o.created_at >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }

        // 2. End Date Filter
        if (!empty($filters['end_date'])) {
            $whereConditions[] = "o.created_at <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }

        // 3. Service Type Filter
        if (!empty($filters['service_type'])) {
            $whereConditions[] = "o.service_type = ?";
            $params[] = $filters['service_type'];
        }

        // 4. Order Status Filter
        if (!empty($filters['status'])) {
            $whereConditions[] = "o.status = ?";
            $params[] = $filters['status'];
        }

        // 5. Partner Filter
        if (!empty($filters['partner_id'])) {
            $whereConditions[] = "o.partner_id = ?";
            $params[] = (int)$filters['partner_id'];
        }

        // 6. Customer Filter
        if (!empty($filters['customer_name'])) {
            $whereConditions[] = "o.customer_name LIKE ?";
            $params[] = '%' . $filters['customer_name'] . '%';
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Fetch matching orders
        $stmt = $db->prepare("
            SELECT o.*, p.name as partner_name 
            FROM orders o 
            LEFT JOIN partners p ON o.partner_id = p.id 
            WHERE {$whereClause}
            ORDER BY o.created_at DESC
        ");
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Aggregation Containers
        $totalOrders       = count($orders);
        $completedOrders   = 0;
        $inProgressOrders  = 0;
        $notFeasibleOrders = 0;

        $serviceTypeDist = [];
        $orderStatusDist = [];
        $pipelineDist    = [
            'Feasibility Review'         => 0,
            'Await Commercial Approval'  => 0,
            'Management Approval'        => 0,
            'Pending SOF'                => 0,
            'SOF Review'                 => 0,
            'Installation'               => 0,
            'Testing'                    => 0,
            'UAT'                        => 0,
            'Closed'                     => 0,
            'Not Feasible / Cancelled'   => 0
        ];

        $totalNrcRevenue = 0.0;
        $totalMrcRevenue = 0.0;
        $totalVatRevenue = 0.0;

        foreach ($orders as $ord) {
            $st = $ord['status'] ?? 'Draft';

            // Status Distribution
            if (!isset($orderStatusDist[$st])) {
                $orderStatusDist[$st] = 0;
            }
            $orderStatusDist[$st]++;

            // Pipeline Mapping
            if (isset($pipelineDist[$st])) {
                $pipelineDist[$st]++;
            } elseif (in_array($st, ['Not Feasible', 'Cancelled'])) {
                $pipelineDist['Not Feasible / Cancelled']++;
            }

            // Health Status Mapping
            if (in_array($st, ['Closed', 'Activated', 'Billing Triggered'])) {
                $completedOrders++;
            } elseif (in_array($st, ['Not Feasible', 'Cancelled'])) {
                $notFeasibleOrders++;
            } else {
                $inProgressOrders++;
            }

            // Service Type Determination
            $stVal = trim($ord['service_type'] ?? '');
            if ($stVal === '') {
                if (!empty($ord['fttx_package'])) {
                    $stVal = 'FTTH';
                } elseif (!empty($ord['aggregate_capacity'])) {
                    $stVal = 'Layer 2 ( last mile)';
                } elseif (!empty($ord['bandwidth'])) {
                    $stVal = 'BIA (Broadband Internet Access)';
                } elseif ((float)($ord['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($ord['base_nrc_usd'] ?? 0) == 80000) {
                    $stVal = 'Remote Hands Only';
                } else {
                    $stVal = 'Unspecified';
                }
            }

            if (!isset($serviceTypeDist[$stVal])) {
                $serviceTypeDist[$stVal] = 0;
            }
            $serviceTypeDist[$stVal]++;

            // Commercial Revenue Calculation
            if (function_exists('getOrderCommercialSummary')) {
                $comm = getOrderCommercialSummary($ord);
                $totalNrcRevenue += $comm['total_nrc'];
                $totalMrcRevenue += $comm['total_mrc'];
                $totalVatRevenue += $comm['vat_total'];
            }
        }

        $totalCombinedRevenue = round($totalNrcRevenue + $totalMrcRevenue, 2);

        // Network Health Summary
        $networkHealth = [
            'Online / Closed'          => $completedOrders,
            'In Progress / Pending'    => $inProgressOrders,
            'Not Feasible / Cancelled' => $notFeasibleOrders
        ];

        // Active Partners & Contractors
        $stmtP = $db->query("SELECT COUNT(*) FROM partners WHERE status = 'Active' AND kyc_type = 'Partner'");
        $activePartners = $stmtP ? (int)$stmtP->fetchColumn() : 0;

        $stmtC = $db->query("SELECT COUNT(*) FROM partners WHERE status = 'Active' AND kyc_type = 'Contractor'");
        $activeContractors = $stmtC ? (int)$stmtC->fetchColumn() : 0;

        // KYC Records
        $kycStmt = $db->prepare("
            SELECT k.*, p.name as partner_name, u.full_name as reviewer_name
            FROM partner_kyc_applications k
            JOIN partners p ON k.partner_id = p.id
            LEFT JOIN users u ON k.reviewed_by = u.id
            WHERE {$pwK['condition']}
            ORDER BY k.created_at DESC
        ");
        $kycStmt->execute($pwK['params']);
        $kycRecords = $kycStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'orders'               => $orders,
            'totalOrders'          => $totalOrders,
            'completedOrders'      => $completedOrders,
            'inProgressOrders'     => $inProgressOrders,
            'notFeasibleOrders'    => $notFeasibleOrders,
            'serviceTypeDist'      => $serviceTypeDist,
            'orderStatusDist'      => $orderStatusDist,
            'pipelineDist'         => $pipelineDist,
            'networkHealth'        => $networkHealth,
            'totalNrcRevenue'      => round($totalNrcRevenue, 2),
            'totalMrcRevenue'      => round($totalMrcRevenue, 2),
            'totalVatRevenue'      => round($totalVatRevenue, 2),
            'totalCombinedRevenue' => $totalCombinedRevenue,
            'activePartners'       => $activePartners,
            'activeContractors'    => $activeContractors,
            'kycRecords'           => $kycRecords
        ];
    }
}
