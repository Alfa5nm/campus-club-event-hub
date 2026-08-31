<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/mixed/api/_json.php';

try {
    $sent = send_event_reminders((int) ($_POST['event_id'] ?? 0));
    json_response(true, "$sent reminder(s) sent.", ['sent' => $sent]);
} catch (DomainException $exception) {
    json_response(false, $exception->getMessage(), [], 403);
} catch (Throwable $exception) {
    json_response(false, $exception->getMessage(), [], 409);
}
