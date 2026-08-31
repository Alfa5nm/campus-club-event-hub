<?php

require_once dirname(__DIR__, 2) . '/mixed/api/_json.php';
$uid = (int)user()['user_id'];
$action = $_POST['action'] ?? '';
if ($action === 'read') {
    $id = (int)($_POST['notification_id'] ?? 0);
    if (!mark_notification_read($uid, $id)) {
        json_response(false, 'Notification not found.', [], 404);
    }
} elseif ($action === 'read_all') {
    mark_all_notifications_read($uid);
} else {
    json_response(false, 'Unknown notification action.', [], 400);
}
json_response(true, 'Notifications updated.', ['unread_count' => unread_notification_count(),'notification_id' => (int)($_POST['notification_id'] ?? 0)]);
