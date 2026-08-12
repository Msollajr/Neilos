<?php
// ============================================================
// Notification Queue Helper
// ============================================================

function queueNotification(string $channel, string $recipient, string $subject, string $message, string $contextType = null, int $contextId = null): int {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notification_queue (channel, recipient, subject, message, context_type, context_id) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$channel, $recipient, $subject, $message, $contextType, $contextId]);
    return (int)$db->lastInsertId();
}

function queueKycNotification(int $kycId, string $event, string $extraReason = ''): void {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT pka.*, p.name as partner_name, u.email as partner_user_email
        FROM partner_kyc_applications pka
        JOIN partners p ON pka.partner_id = p.id
        LEFT JOIN users u ON u.partner_id = p.id AND u.is_active = 1
        WHERE pka.id = ?
        LIMIT 1
    ");
    $stmt->execute([$kycId]);
    $kyc = $stmt->fetch();
    if (!$kyc) return;

    $partnerEmail = $kyc['partner_user_email'] ?: 'partner@neilos.co.tz';
    $adminEmails = ['admin@neilos.co.tz', 'management@neilos.co.tz'];

    if ($event === 'Submit' || $event === 'Resubmit') {
        queueNotification('Email', $partnerEmail, "KYC Application Submitted for Review — {$kyc['partner_name']}", "Your KYC application has been submitted for approval. Please log into the portal to review and approve/reject.", 'kyc', $kycId);
        foreach ($adminEmails as $a) {
            queueNotification('Email', $a, "KYC Application Submitted — {$kyc['partner_name']}", "KYC Application #{$kycId} for {$kyc['partner_name']} has been submitted for approval.", 'kyc', $kycId);
        }
    } elseif ($event === 'Approve') {
        queueNotification('Email', $partnerEmail, "KYC Application Approved — {$kyc['partner_name']}", "Your KYC application #{$kycId} has been successfully approved.", 'kyc', $kycId);
        foreach ($adminEmails as $a) {
            queueNotification('Email', $a, "KYC Approved — {$kyc['partner_name']}", "KYC Application #{$kycId} for {$kyc['partner_name']} was approved by the partner/contractor.", 'kyc', $kycId);
        }
    } elseif ($event === 'Reject') {
        foreach ($adminEmails as $a) {
            queueNotification('Email', $a, "KYC Application Rejected — {$kyc['partner_name']}", "KYC Application #{$kycId} for {$kyc['partner_name']} was REJECTED.\nRejection Reason: {$extraReason}", 'kyc', $kycId);
        }
    }
}

function queueOrderNotification(int $orderId, string $event): void {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT o.*, p.name as partner_name,
               u_partner.email as partner_email,
               u_partner.mobile as partner_phone,
               u_kam.email  as kam_email,
               u_bsa.email  as bsa_email,
               u_pm.email   as pm_email,
               u_mgmt.email as management_email
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        LEFT JOIN (
            SELECT partner_id, email, mobile FROM users
            WHERE role IN ('Partner', 'Partner User') AND is_active = 1
        ) u_partner ON u_partner.partner_id = p.id
        LEFT JOIN (
            SELECT email FROM users WHERE role = 'KAM' AND is_active = 1 LIMIT 1
        ) u_kam ON 1=1
        LEFT JOIN (
            SELECT email FROM users WHERE role = 'BSA' AND is_active = 1 LIMIT 1
        ) u_bsa ON 1=1
        LEFT JOIN (
            SELECT email FROM users WHERE role = 'Project Manager' AND is_active = 1 LIMIT 1
        ) u_pm ON 1=1
        LEFT JOIN (
            SELECT email FROM users WHERE role = 'Management' AND is_active = 1 LIMIT 1
        ) u_mgmt ON 1=1
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $o = $stmt->fetch();
    $o['bsa_email'] = $o['bsa_email'] ?: 'comfortmnyinga@gmail.com';

    $orderRef = "Order {$o['order_number']} — {$o['customer_name']} ({$o['service_type']})";

    // Map event → recipients and message
    $notifications = match($event) {
        // Partner submits feasibility — BSA is notified
        'Feasibility Submitted' => [
            ['email' => $o['bsa_email'], 'subj' => "New Feasibility Request: {$o['order_number']}", 'msg' => "$orderRef submitted by partner. Please review technical feasibility."],
        ],
        // BSA confirms feasible — KAM is notified for commercial review
        'Technically Feasible' => [
            ['email' => $o['kam_email'], 'subj' => "Feasibility Confirmed: {$o['order_number']}", 'msg' => "$orderRef has been confirmed technically feasible by BSA. Please proceed with commercial review."],
        ],
        // BSA marks not feasible — Partner is notified
        'Technically Not Feasible' => [
            ['email' => $o['partner_email'], 'subj' => "Service Not Available: {$o['order_number']}", 'msg' => "$orderRef cannot be fulfilled at this time. BSA has determined this location is not technically feasible. Please contact your KAM for more information."],
        ],
        // KAM approved — Partner notified to sign SOF
        'Feasibility Approved' => [
            ['email' => $o['partner_email'], 'subj' => "Your Service Request Has Been Approved: {$o['order_number']}", 'msg' => "Great news! $orderRef has been reviewed and approved. Please log in to the Partner Portal to generate and upload your signed Service Order Form (SOF) to proceed."],
        ],
        // KAM escalated to Management — Management notified
        'KAM Requires Further Approval' => [
            ['email' => $o['management_email'], 'subj' => "Exception Approval Required: {$o['order_number']}", 'msg' => "$orderRef requires your exception approval for non-standard commercial terms. KAM: {$o['assigned_kam_name']}. Please log in to review and approve."],
        ],
        // Partner uploads signed SOF — KAM notified to countersign
        'Signed SOF Uploaded' => [
            ['email' => $o['kam_email'], 'subj' => "Signed SOF Received: {$o['order_number']}", 'msg' => "$orderRef — Partner has uploaded the signed SOF. Please review, countersign, and upload the Neilos copy to proceed to project."],
        ],
        // Countersigned SOF uploaded — Partner + PM notified
        'Countersigned SOF Uploaded' => [
            ['email' => $o['partner_email'], 'subj' => "SOF Executed — Installation Will Begin: {$o['order_number']}", 'msg' => "$orderRef — The Service Order Form has been executed by both parties. Our installation team will contact you to schedule site access."],
            ['email' => $o['pm_email'],      'subj' => "New Installation Order: {$o['order_number']}", 'msg' => "$orderRef is ready for project assignment. SOF is countersigned. Please assign a contractor and begin installation."],
        ],
        // Project released to installation — PM notified
        'Proceed to Project' => [
            ['email' => $o['pm_email'], 'subj' => "Order Released to Installation: {$o['order_number']}", 'msg' => "$orderRef has been released to installation. Please assign a contractor as soon as possible."],
        ],
        // Contractor assigned — notify (email optional, same contractor partner)
        'Contractor Assigned' => [],
        // Contractor completed — PM + BSA notified for testing
        'Contractor Completed' => [
            ['email' => $o['pm_email'],  'subj' => "Installation Completed — Review Required: {$o['order_number']}", 'msg' => "$orderRef — Contractor has submitted the installation as complete. Please review evidence and approve for UAT."],
            ['email' => $o['bsa_email'], 'subj' => "Testing Required: {$o['order_number']}", 'msg' => "$orderRef is ready for testing review. Contractor has submitted completion."],
        ],
        // Testing approved — Partner notified for UAT
        'Testing Approved' => [
            ['email' => $o['partner_email'], 'subj' => "Your Service Is Ready for Acceptance: {$o['order_number']}", 'msg' => "$orderRef — Installation is complete and has passed our internal testing. Please log in to the Partner Portal within 48 hours to accept the service or return it for correction. If no response is received within 48 hours, the service will be automatically accepted."],
        ],
        // Partner accepted — KAM + Finance notified
        'Partner Accepted Service' => [
            ['email' => $o['kam_email'], 'subj' => "Order Closed — Service Accepted: {$o['order_number']}", 'msg' => "$orderRef has been accepted by the partner. Order is now CLOSED. Billing start date: " . date('d M Y') . "."],
        ],
        // Partner returned from UAT — PM notified
        'Partner Returned Installation' => [
            ['email' => $o['pm_email'],  'subj' => "UAT Rejected — Returned to Installation: {$o['order_number']}", 'msg' => "$orderRef — Partner has returned the service from UAT to installation for corrections. Please review their remarks and reassign."],
            ['email' => $o['bsa_email'], 'subj' => "UAT Returned: {$o['order_number']}", 'msg' => "$orderRef has been returned from UAT by the partner. Installation corrections required."],
        ],
        // Partner returns from Pending SOF
        'Partner Returned Feasibility' => [
            ['email' => $o['bsa_email'], 'subj' => "Partner Returned: {$o['order_number']}", 'msg' => "$orderRef — Partner has returned the order from Pending SOF for review. Please check remarks and re-assess."],
            ['email' => $o['kam_email'], 'subj' => "Partner Returned: {$o['order_number']}", 'msg' => "$orderRef — Partner has returned the order from Pending SOF. Please review."],
        ],
        default => [],
    };

    foreach ($notifications as $n) {
        if (empty($n['email'])) continue;
        queueNotification('Email', $n['email'], $n['subj'], $n['msg'], 'order', $orderId);
    }
}


function processNotificationQueue(int $limit = 10): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notification_queue WHERE status = 'Queued' ORDER BY created_at ASC LIMIT ?");
    $stmt->execute([$limit]);
    $items = $stmt->fetchAll();
    $processed = 0;

    foreach ($items as $item) {
        $success = sendNotification($item['channel'], $item['recipient'], $item['subject'], $item['message']);
        $db->prepare("UPDATE notification_queue SET status = ?, attempts = attempts + 1, error_message = ?, sent_at = IF(? = 'Sent', NOW(), NULL) WHERE id = ?")
           ->execute([
               $success ? 'Sent' : 'Failed',
               $success ? null : 'Delivery failed',
               $success ? 'Sent' : 'Failed',
               $item['id']
           ]);
        $processed++;
    }
    return $processed;
}

function sendNotification(string $channel, string $recipient, string $subject, string $message): bool {
    switch ($channel) {
        case 'Email':
            $headers = "From: noreply@neilosnetwork.co.tz\r\nContent-Type: text/plain; charset=utf-8\r\n";
            return mail($recipient, $subject, $message, $headers);
        case 'SMS':
        case 'WhatsApp':
            return false;
        default:
            return false;
    }
}
