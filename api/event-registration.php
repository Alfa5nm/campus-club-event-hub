<?php
require_once __DIR__ . '/_json.php';
$userId = (int) user()['user_id'];
$eventId = (int) ($_POST['event_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'cancel_registration') {
    $stmt = db()->prepare("UPDATE event_registration SET registration_status='Cancelled', cancellation_reason='Cancelled by student' WHERE event_id=? AND student_user_id=? AND registration_status='Registered'");
    $stmt->execute([$eventId, $userId]);
    if (!$stmt->rowCount()) json_response(false, 'No active registration was found.', [], 409);
    $count = db()->prepare("SELECT COUNT(*) FROM event_registration WHERE event_id=? AND registration_status='Registered'");
    $count->execute([$eventId]);
    json_response(true, 'Registration cancelled.', ['state' => 'cancelled', 'registration_count' => (int)$count->fetchColumn()]);
}

if ($action !== 'register') json_response(false, 'Unknown registration action.', [], 400);

try {
    db()->beginTransaction();
    $student = db()->prepare('SELECT 1 FROM students WHERE user_id=?'); $student->execute([$userId]);
    if (!$student->fetchColumn()) throw new RuntimeException('Only student accounts can register.');
    $stmt = db()->prepare("SELECT e.*, COUNT(er.registration_id) AS taken FROM events e LEFT JOIN event_registration er ON er.event_id=e.event_id AND er.registration_status='Registered' WHERE e.event_id=? GROUP BY e.event_id FOR UPDATE");
    $stmt->execute([$eventId]); $event = $stmt->fetch();
    if (!$event || $event['status'] !== 'Upcoming') throw new RuntimeException('This event is not accepting registrations.');
    if ($event['registration_deadline'] && $event['registration_deadline'] < date('Y-m-d')) throw new RuntimeException('The registration deadline has passed.');
    if ((int)$event['taken'] >= (int)$event['maximum_participants']) throw new RuntimeException('This event has reached capacity.');
    db()->prepare("INSERT INTO event_registration (student_user_id,event_id,registration_status,qr_token) VALUES (?,?,'Registered',NULL) ON DUPLICATE KEY UPDATE registration_status='Registered',qr_token=NULL,cancellation_reason=NULL,updated_at=CURRENT_TIMESTAMP")->execute([$userId,$eventId]);
    db()->commit();
    json_response(true, 'Your place is confirmed.', ['state'=>'registered','registration_count'=>(int)$event['taken']+1,'capacity'=>(int)$event['maximum_participants']]);
} catch (Throwable $exception) {
    if (db()->inTransaction()) db()->rollBack();
    json_response(false, $exception instanceof RuntimeException ? $exception->getMessage() : 'Registration could not be completed.', [], 409);
}
