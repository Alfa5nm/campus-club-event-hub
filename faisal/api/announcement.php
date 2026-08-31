<?php

require_once dirname(__DIR__, 2) . '/mixed/api/_json.php';
$action = $_POST['action'] ?? '';
try {
    if ($action === 'save') {
        $data = save_announcement($_POST);
        json_response(true, 'Announcement saved.', $data);
    }
    if ($action === 'remove') {
        $id = (int)($_POST['announcement_id'] ?? 0);
        remove_announcement($id);
        json_response(true, 'Announcement removed.', ['announcement_id' => $id,'status' => 'Removed']);
    }
    json_response(false, 'Unknown announcement action.', [], 400);
} catch (DomainException $e) {
    json_response(false, $e->getMessage(), [], 403);
} catch (InvalidArgumentException $e) {
    json_response(false, $e->getMessage(), [], 422);
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), [], 404);
} catch (Throwable $e) {
    json_response(false, 'Announcement could not be saved.', [], 500);
}
