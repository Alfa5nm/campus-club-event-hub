<?php

require_once __DIR__ . '/_json.php';
$userId = (int) user()['user_id'];
$eventId = (int) ($_POST['event_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'cancel_registration') {
    try {
        $data = cancel_event_registration($userId, $eventId);
    } catch (RuntimeException $exception) {
        json_response(false, 'No active registration was found.', [], 409);
    }

    json_response(true, 'Registration cancelled.', $data);
}

if ($action !== 'register') {
    json_response(false, 'Unknown registration action.', [], 400);
}

try {
    json_response(true, 'Your place is confirmed.', register_for_event($userId, $eventId));
} catch (Throwable $exception) {
    json_response(false, $exception instanceof RuntimeException ? $exception->getMessage() : 'Registration could not be completed.', [], 409);
}
