<?php
require_once __DIR__.'/includes/bootstrap.php';
require_login();
$clubs = managed_clubs();
if (!$clubs) {
    http_response_code(403);
    exit('You do not manage an active club.');
}
$clubIds = array_map('intval', array_column($clubs, 'club_id'));
$in = implode(',', array_fill(0, count($clubIds), '?'));
$eventsStmt = db()->prepare("SELECT e.event_id,e.title,e.event_date,c.club_name FROM events e JOIN clubs c ON c.club_id=e.club_id WHERE e.club_id IN ($in) AND e.status!='Draft' ORDER BY e.event_date DESC,e.title");
$eventsStmt->execute($clubIds);
$events = $eventsStmt->fetchAll();
$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? ($events[0]['event_id'] ?? 0));
$allowed = array_map('intval', array_column($events, 'event_id'));
if ($eventId && !in_array($eventId, $allowed, true)) {
    http_response_code(403);
    exit('Not authorized.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $r = mark_attendance($eventId, (int)($_POST['registration_id'] ?? 0), $_POST['attendance_status'] ?? '');
        flash('success', $r['student_name'].' marked '.$r['status'].'.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }redirect('attendance.php?event_id='.$eventId);
}
$registrations = [];
$counts = ['total' => 0,'present' => 0,'absent' => 0,'pending' => 0];
if ($eventId) {
    $s = db()->prepare("SELECT er.registration_id,er.registration_status,u.full_name,u.email,s.student_number,a.attendance_method,a.check_in_time,cert.certificate_id,cert.status certificate_status FROM event_registration er JOIN users u ON u.user_id=er.student_user_id JOIN students s ON s.user_id=u.user_id LEFT JOIN attendance a ON a.registration_id=er.registration_id LEFT JOIN certificate cert ON cert.attendance_id=a.attendance_id WHERE er.event_id=? AND er.registration_status!='Cancelled' ORDER BY u.full_name");
    $s->execute([$eventId]);
    $registrations = $s->fetchAll();
    foreach ($registrations as $r) {
        $counts['total']++;
        if ($r['registration_status'] === 'Attended') {
            $counts['present']++;
        } elseif ($r['registration_status'] === 'Absent') {
            $counts['absent']++;
        } else {
            $counts['pending']++;
        }
    }
}
$pageTitle = 'Attendance';
require __DIR__.'/includes/header.php';?>
<section class="page-shell attendance-page"><div class="page-head"><div><span class="eyebrow">EXECUTIVE ATTENDANCE DESK</span><h2>Attendance,<br><span class="accent-script">one roster away.</span></h2><p>Select an event, confirm the student, and mark attendance directly. Every update is permission-checked and auditable.</p></div></div>
<form class="filter-bar" method="get"><label class="field"><span>Event</span><select name="event_id" onchange="this.form.submit()"><?php foreach ($events as $e):?><option value="<?=$e['event_id']?>" <?=$eventId === (int)$e['event_id'] ? 'selected' : ''?>><?=e($e['title'].' · '.$e['club_name'].' · '.date('M j', strtotime($e['event_date'])))?></option><?php endforeach;?></select></label></form>
<?php if (!$events):?><div class="empty">Create an event before opening attendance.</div><?php else:?><div class="stat-grid" data-attendance-counts><div class="stat"><strong data-attendance-count="total"><?=$counts['total']?></strong><span>Eligible</span></div><div class="stat"><strong data-attendance-count="present"><?=$counts['present']?></strong><span>Present</span></div><div class="stat"><strong data-attendance-count="absent"><?=$counts['absent']?></strong><span>Absent</span></div><div class="stat"><strong data-attendance-count="pending"><?=$counts['pending']?></strong><span>Pending</span></div></div>
<div class="table-wrap"><table class="data-table attendance-roster"><thead><tr><th>Student</th><th>ID</th><th>Status</th><th>Certificate</th><th>Mark attendance</th></tr></thead><tbody><?php foreach ($registrations as $r):?><tr data-registration-row="<?=$r['registration_id']?>"><td><strong><?=e($r['full_name'])?></strong><small><?=e($r['email'])?></small></td><td><?=e($r['student_number'])?></td><td><span class="badge"><?=e($r['registration_status'])?></span></td><td><?=$r['certificate_id'] ? e($r['certificate_status']) : '—'?></td><td><form method="post" data-ajax="api/attendance.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="event_id" value="<?=$eventId?>"><input type="hidden" name="registration_id" value="<?=$r['registration_id']?>"><div class="button-row"><button class="button button-primary" name="attendance_status" value="Present">Present</button><button class="button button-danger" name="attendance_status" value="Absent">Absent</button></div></form></td></tr><?php endforeach;?><?php if (!$registrations):?><tr><td colspan="5"><div class="empty">No active registrations for this event.</div></td></tr><?php endif;?></tbody></table></div><?php endif;?></section>
<?php require __DIR__.'/includes/footer.php';?>
