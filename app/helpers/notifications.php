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

function queueOrderNotification(int $orderId, string $event): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT o.*, p.name as partner_name, p.email as partner_email, p.phone as partner_phone FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $o = $stmt->fetch();
    if (!$o) return;

    $subject = "Order {$o['order_number']} — $event";
    $msg = "Order {$o['order_number']} for {$o['customer_name']} ({$o['service_type']}) has been updated to: {$o['status']}.\n\nEvent: $event\nService: {$o['service_type']}\nCustomer: {$o['customer_name']}";

    if ($o['partner_email']) {
        queueNotification('Email', $o['partner_email'], $subject, $msg, 'order', $orderId);
    }
    if ($o['partner_phone']) {
        queueNotification('SMS', $o['partner_phone'], $subject, $msg, 'order', $orderId);
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
