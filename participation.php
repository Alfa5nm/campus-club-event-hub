<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$statement = db()->prepare(
    'SELECT
        er.registration_status,
        er.registration_date,
        er.updated_at,
        e.title,
        e.event_date,
        e.venue,
        c.club_name,
        a.attendance_status,
        a.marked_at,
        cert.certificate_id,
        cert.status AS certificate_status
     FROM event_registration er
     JOIN events e ON e.event_id = er.event_id
     JOIN clubs c ON c.club_id = e.club_id
     LEFT JOIN attendance a ON a.registration_id = er.registration_id
     LEFT JOIN certificate cert ON cert.attendance_id = a.attendance_id
     WHERE er.student_user_id = ?
     ORDER BY e.event_date DESC'
);
$statement->execute([current_user_id()]);
$history = $statement->fetchAll();

$pageTitle = 'Participation history';
require __DIR__ . '/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">MY EVENT RECORD</span>
            <h2>Every registration,<br><span class="accent-script">in one timeline.</span></h2>
            <p>Review registrations, cancellations, attendance, and certificate status.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Event</th><th>Date</th><th>Registration</th><th>Attendance</th><th>Certificate</th></tr></thead>
            <tbody>
                <?php foreach ($history as $item): ?>
                    <tr>
                        <td><strong><?= e($item['title']) ?></strong><small><?= e($item['club_name']) ?> · <?= e($item['venue']) ?></small></td>
                        <td><?= e(date('M j, Y', strtotime($item['event_date']))) ?></td>
                        <td><span class="badge"><?= e($item['registration_status']) ?></span></td>
                        <td><?= e($item['attendance_status'] ?? 'Not marked') ?></td>
                        <td><?= e($item['certificate_status'] ?? 'Not issued') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$history): ?><tr><td colspan="5"><div class="empty">No event history yet.</div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
