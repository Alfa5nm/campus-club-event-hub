<?php

declare(strict_types=1);

require __DIR__ . '/_json.php';

if (!is_admin()) {
    json_response(false, 'Administrator access is required.', [], 403);
}

$type = $_POST['type'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed = [
    'user' => ['users', 'user_id', ['Active', 'Suspended', 'Deactivated']],
    'club' => ['clubs', 'club_id', ['Pending', 'Active', 'Suspended']],
    'event' => ['events', 'event_id', ['Upcoming', 'Completed', 'Cancelled']],
    'feedback' => ['feedback', 'feedback_id', ['Visible', 'Hidden', 'Reported']],
    'certificate' => ['certificate', 'certificate_id', ['Active', 'Revoked']],
    'announcement' => ['announcement', 'announcement_id', ['Active', 'Expired', 'Removed']],
];

if (!isset($allowed[$type]) || !in_array($status, $allowed[$type][2], true)) {
    json_response(false, 'That moderation action is invalid.', [], 422);
}

[$table, $key] = $allowed[$type];
$sql = sprintf('UPDATE `%s` SET status = ? WHERE `%s` = ?', $table, $key);
$statement = db()->prepare($sql);
$statement->execute([$status, $id]);

if ($statement->rowCount() === 0) {
    json_response(false, 'The selected record was not changed.', [], 404);
}

json_response(true, ucfirst($type) . ' status updated.', ['id' => $id, 'status' => $status]);
