<?php
// ============================================================
// Neilos Partner Portal — Price Change Audit Helper
// ============================================================

/**
 * Record an NRC/MRC price change for audit trail.
 * Call this every time a pricing field changes in the order lifecycle.
 */
function recordPriceChange(
    int    $orderId,
    string $fieldName,
    float|null $oldValue,
    float|null $newValue,
    int    $changedBy,
    string $stage,
    string $justification = ''
): void {
    try {
        $db = getDB();
        $db->prepare("
            INSERT INTO price_change_audit
                (order_id, field_name, old_value, new_value, currency, changed_by, stage, justification, changed_at)
            VALUES (?, ?, ?, ?, 'TZS', ?, ?, ?, NOW())
        ")->execute([$orderId, $fieldName, $oldValue, $newValue, $changedBy, $stage, $justification]);
    } catch (Exception $e) {
        // Silently fail — never break order workflow due to audit failure
        error_log('Price audit error: ' . $e->getMessage());
    }
}

/**
 * Retrieve the full price-change audit trail for an order.
 * Returns rows ordered by change time ascending.
 */
function getOrderPriceAudit(int $orderId): array {
    try {
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT pca.*, u.full_name AS changed_by_name
            FROM price_change_audit pca
            LEFT JOIN users u ON pca.changed_by = u.id
            WHERE pca.order_id = ?
            ORDER BY pca.changed_at ASC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Return human-friendly label for a price_change_audit field_name.
 */
function priceFieldLabel(string $fieldName): string {
    return match($fieldName) {
        'standard_nrc'       => 'Standard NRC (System)',
        'revised_nrc'        => 'BSA Revised NRC',
        'kam_proposed_nrc'   => 'KAM Proposed NRC Exception',
        'management_final_nrc' => 'Management Final NRC',
        'standard_mrc'       => 'Standard MRC (System)',
        'revised_mrc'        => 'KAM Revised MRC',
        'kam_proposed_mrc'   => 'KAM Proposed MRC Exception',
        'management_approved_price' => 'Management Approved MRC',
        'management_final_mrc' => 'Management Final MRC',
        default              => ucwords(str_replace('_', ' ', $fieldName)),
    };
}
