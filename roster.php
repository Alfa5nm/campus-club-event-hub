<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$eventId = (int) ($_GET['event_id'] ?? 0);
$statement = db()->prepare(
    'SELECT e.*, c.club_name FROM events e JOIN clubs c ON c.club_id = e.club_id WHERE e.event_id = ?'
);
$statement->execute([$eventId]);
$event = $statement->fetch();

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

if (!can_manage_club((int) $event['club_id'])) {
    http_response_code(403);
    exit('You cannot view this roster.');
}

$statement = db()->prepare(
    'SELECT u.full_name, u.email, s.student_number, er.registration_date, er.registration_status
     FROM event_registration er
     JOIN users u ON u.user_id = er.student_user_id
     JOIN students s ON s.user_id = u.user_id
     WHERE er.event_id = ?
     ORDER BY u.full_name'
);
$statement->execute([$eventId]);
$students = $statement->fetchAll();

if (($_GET['format'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="event-roster-' . $eventId . '.csv"');
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['Student', 'Email', 'Student ID', 'Registered at', 'Status']);

    foreach ($students as $student) {
        fputcsv($output, array_values($student));
    }

    fclose($output);
    exit;
}

$pageTitle = 'Event roster';
require __DIR__ . '/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div><span class="eyebrow">REGISTRATION ROSTER</span><h2><?= e($event['title']) ?></h2><p><?= e($event['club_name']) ?> · <?= count($students) ?> registration(s)</p></div>
        <div class="actions"><a class="button button-quiet" href="roster.php?event_id=<?= $eventId ?>&amp;format=csv">Download CSV</a><form method="post" data-ajax="api/reminder.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="event_id" value="<?= $eventId ?>"><button class="button button-primary">Send reminders</button></form></div>
    </div>

    <div class="filter-bar"><label class="search-field"><span>⌕</span><input type="search" data-table-search placeholder="Search students"></label></div>
    <div class="table-wrap"><table class="data-table" data-search-table><thead><tr><th>Student</th><th>ID</th><th>Registered</th><th>Status</th></tr></thead><tbody><?php foreach ($students as $student): ?><tr><td><strong><?= e($student['full_name']) ?></strong><small><?= e($student['email']) ?></small></td><td><?= e($student['student_number']) ?></td><td><?= e(date('M j, Y H:i', strtotime($student['registration_date']))) ?></td><td><span class="badge"><?= e($student['registration_status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
