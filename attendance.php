<?php

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$clubs = managed_clubs();

if (!$clubs) {
    http_response_code(403);
    exit('You do not manage an active club.');
}

$clubIds = array_map('intval', array_column($clubs, 'club_id'));
$placeholders = implode(',', array_fill(0, count($clubIds), '?'));
$eventsStmt = db()->prepare(
    "SELECT
        e.event_id,
        e.title,
        e.event_date,
        c.club_name
    FROM events e
    JOIN clubs c ON c.club_id = e.club_id
    WHERE e.club_id IN ($placeholders)
        AND e.status != 'Draft'
    ORDER BY e.event_date DESC, e.title"
);
$eventsStmt->execute($clubIds);
$events = $eventsStmt->fetchAll();
$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? ($events[0]['event_id'] ?? 0));
$allowed = array_map('intval', array_column($events, 'event_id'));

if ($eventId && !in_array($eventId, $allowed, true)) {
    http_response_code(403);
    exit('Not authorized.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $result = mark_attendance(
            $eventId,
            (int) ($_POST['registration_id'] ?? 0),
            $_POST['attendance_status'] ?? ''
        );
        flash('success', $result['student_name'] . ' marked ' . $result['status'] . '.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('attendance.php?event_id=' . $eventId);
}

$registrations = [];
$counts = [
    'total' => 0,
    'present' => 0,
    'absent' => 0,
    'pending' => 0,
];

if ($eventId) {
    $registrationStmt = db()->prepare(
        "SELECT
            er.registration_id,
            er.registration_status,
            u.full_name,
            u.email,
            s.student_number,
            a.attendance_method,
            a.check_in_time,
            cert.certificate_id,
            cert.status AS certificate_status
        FROM event_registration er
        JOIN users u ON u.user_id = er.student_user_id
        JOIN students s ON s.user_id = u.user_id
        LEFT JOIN attendance a ON a.registration_id = er.registration_id
        LEFT JOIN certificate cert ON cert.attendance_id = a.attendance_id
        WHERE er.event_id = ?
            AND er.registration_status != 'Cancelled'
        ORDER BY u.full_name"
    );
    $registrationStmt->execute([$eventId]);
    $registrations = $registrationStmt->fetchAll();

    foreach ($registrations as $registration) {
        $counts['total']++;

        if ($registration['registration_status'] === 'Attended') {
            $counts['present']++;
        } elseif ($registration['registration_status'] === 'Absent') {
            $counts['absent']++;
        } else {
            $counts['pending']++;
        }
    }
}

$pageTitle = 'Attendance';
require __DIR__ . '/includes/header.php';
?>
<section class="page-shell attendance-page">
    <div class="page-head">
        <div>
            <span class="eyebrow">EXECUTIVE ATTENDANCE DESK</span>
            <h2>Attendance,<br><span class="accent-script">one roster away.</span></h2>
            <p>Select an event, confirm the student, and mark attendance directly. Every update is permission-checked and auditable.</p>
        </div>
    </div>

    <form class="filter-bar" method="get">
        <label class="field">
            <span>Event</span>
            <select name="event_id" onchange="this.form.submit()">
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['event_id'] ?>" <?= $eventId === (int) $event['event_id'] ? 'selected' : '' ?>>
                        <?= e($event['title'] . ' · ' . $event['club_name'] . ' · ' . date('M j', strtotime($event['event_date']))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <?php if (!$events): ?>
        <div class="empty">Create an event before opening attendance.</div>
    <?php else: ?>
        <div class="stat-grid" data-attendance-counts>
            <?php foreach (['total' => 'Eligible', 'present' => 'Present', 'absent' => 'Absent', 'pending' => 'Pending'] as $key => $label): ?>
                <div class="stat">
                    <strong data-attendance-count="<?= $key ?>"><?= $counts[$key] ?></strong>
                    <span><?= $label ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <table class="data-table attendance-roster">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Certificate</th>
                        <th>Mark attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <tr data-registration-row="<?= $registration['registration_id'] ?>">
                            <td>
                                <strong><?= e($registration['full_name']) ?></strong>
                                <small><?= e($registration['email']) ?></small>
                            </td>
                            <td><?= e($registration['student_number']) ?></td>
                            <td><span class="badge"><?= e($registration['registration_status']) ?></span></td>
                            <td><?= $registration['certificate_id'] ? e($registration['certificate_status']) : '—' ?></td>
                            <td>
                                <form method="post" data-ajax="api/attendance.php">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                    <input type="hidden" name="registration_id" value="<?= $registration['registration_id'] ?>">
                                    <div class="button-row">
                                        <button class="button button-primary" name="attendance_status" value="Present">Present</button>
                                        <button class="button button-danger" name="attendance_status" value="Absent">Absent</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$registrations): ?>
                        <tr>
                            <td colspan="5"><div class="empty">No active registrations for this event.</div></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
