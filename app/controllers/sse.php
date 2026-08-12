<?php
// ============================================================
// Neilos Partner Portal — Server-Sent Events (SSE) Real-Time API
// ============================================================

requireLogin();

// Send SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

if (ob_get_level()) {
    ob_end_clean();
}

initSystemEventsTable();
$db = getDB();

$lastEventId = (int)($_GET['last_id'] ?? ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0));

$stmt = $db->prepare("SELECT * FROM system_events WHERE id > ? ORDER BY id ASC LIMIT 20");
$stmt->execute([$lastEventId]);
$events = $stmt->fetchAll();

if (!empty($events)) {
    foreach ($events as $ev) {
        $lastEventId = $ev['id'];
        $payload = json_decode($ev['payload'], true) ?: [];
        echo "id: {$ev['id']}\n";
        echo "event: system_update\n";
        echo "data: " . json_encode($payload) . "\n\n";
    }
} else {
    // Send lightweight SSE ping
    echo ": ping\n\n";
}

flush();
exit;
