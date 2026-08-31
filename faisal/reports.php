<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
require_login();

if (!is_admin() && !managed_clubs()) {
    http_response_code(403);
    exit('Reporting access requires an executive or administrator account.');
}

$clubId = (int) ($_GET['club_id'] ?? 0);
$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-12-31');
$clubs = managed_clubs();
$allowedClubIds = array_map('intval', array_column($clubs, 'club_id'));

if ($clubId && !in_array($clubId, $allowedClubIds, true)) {
    http_response_code(403);
    exit('You cannot report on that club.');
}

$where = ['e.event_date BETWEEN ? AND ?'];
$parameters = [$from, $to];

if ($clubId) {
    $where[] = 'e.club_id = ?';
    $parameters[] = $clubId;
} elseif (!is_admin()) {
    $marks = implode(',', array_fill(0, count($allowedClubIds), '?'));
    $where[] = "e.club_id IN ($marks)";
    $parameters = array_merge($parameters, $allowedClubIds);
}

$sql = "SELECT
            c.club_name,
            e.title,
            e.event_date,
            e.maximum_participants,
            COUNT(DISTINCT CASE WHEN er.registration_status <> 'Cancelled' THEN er.registration_id END) AS registrations,
            COUNT(DISTINCT CASE WHEN er.registration_status = 'Attended' THEN er.registration_id END) AS attendees,
            COUNT(DISTINCT cert.certificate_id) AS certificates,
            ROUND(COALESCE(AVG(CASE WHEN f.status = 'Visible' THEN f.rating END), 0), 1) AS average_rating
        FROM events e
        JOIN clubs c ON c.club_id = e.club_id
        LEFT JOIN event_registration er ON er.event_id = e.event_id
        LEFT JOIN attendance a ON a.registration_id = er.registration_id
        LEFT JOIN certificate cert ON cert.attendance_id = a.attendance_id AND cert.status = 'Active'
        LEFT JOIN feedback f ON f.registration_id = er.registration_id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY e.event_id
        ORDER BY e.event_date DESC";
$statement = db()->prepare($sql);
$statement->execute($parameters);
$rows = $statement->fetchAll();

if (($_GET['format'] ?? '') === 'csv') {
    stream_csv(
        'campushub-report.csv',
        ['Club', 'Event', 'Date', 'Capacity', 'Registrations', 'Attendees', 'Certificates', 'Average rating'],
        $rows
    );
}

$totals = [
    'events' => count($rows),
    'registrations' => array_sum(array_column($rows, 'registrations')),
    'attendees' => array_sum(array_column($rows, 'attendees')),
    'certificates' => array_sum(array_column($rows, 'certificates')),
];

$scopeClubIds = $clubId ? [$clubId] : $allowedClubIds;
$scopeMarks = implode(',', array_fill(0, count($scopeClubIds), '?'));
$membershipGrowth = 0;
$announcementReach = 0;

if ($scopeClubIds) {
    $statement = db()->prepare(
        "SELECT COUNT(*) FROM club_membership
         WHERE club_id IN ($scopeMarks) AND DATE(join_date) BETWEEN ? AND ?"
    );
    $statement->execute(array_merge($scopeClubIds, [$from, $to]));
    $membershipGrowth = (int) $statement->fetchColumn();

    $statement = db()->prepare(
        "SELECT COUNT(n.notification_id)
         FROM announcement a
         JOIN notification n
           ON n.created_at >= a.published_at
          AND n.notification_type = a.announcement_type
         WHERE a.club_id IN ($scopeMarks) AND DATE(a.published_at) BETWEEN ? AND ?"
    );
    $statement->execute(array_merge($scopeClubIds, [$from, $to]));
    $announcementReach = (int) $statement->fetchColumn();
}

$pageTitle = 'Reports';
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">DERIVED REPORTS</span>
            <h2>Activity,<br><span class="accent-script">made legible.</span></h2>
            <p>Every figure is calculated from normalized source records.</p>
        </div>
    </div>

    <form class="filter-bar" method="get">
        <label class="field">Club<select name="club_id"><option value="0">All manageable clubs</option><?php foreach ($clubs as $club): ?><option value="<?= $club['club_id'] ?>" <?= $clubId === (int) $club['club_id'] ? 'selected' : '' ?>><?= e($club['club_name']) ?></option><?php endforeach; ?></select></label>
        <label class="field">From<input type="date" name="from" value="<?= e($from) ?>"></label>
        <label class="field">To<input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="button button-secondary">Apply</button>
        <button class="button button-quiet" name="format" value="csv">Download CSV</button>
    </form>

    <div class="stat-grid">
        <div class="stat"><strong><?= $totals['events'] ?></strong><span>Events</span></div>
        <div class="stat"><strong><?= $totals['registrations'] ?></strong><span>Registrations</span></div>
        <div class="stat"><strong><?= $totals['attendees'] ?></strong><span>Attendees</span></div>
        <div class="stat"><strong><?= $totals['certificates'] ?></strong><span>Certificates</span></div>
    </div>

    <div class="stat-grid">
        <div class="stat"><strong><?= $membershipGrowth ?></strong><span>New memberships</span></div>
        <div class="stat"><strong><?= $announcementReach ?></strong><span>Announcement deliveries</span></div>
        <div class="stat"><strong><?= $totals['registrations'] ? round(($totals['attendees'] / $totals['registrations']) * 100) : 0 ?>%</strong><span>Attendance rate</span></div>
        <div class="stat"><strong><?= array_sum(array_column($rows, 'maximum_participants')) ? round(($totals['registrations'] / array_sum(array_column($rows, 'maximum_participants'))) * 100) : 0 ?>%</strong><span>Capacity used</span></div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Event</th><th>Date</th><th>Capacity</th><th>Registered</th><th>Attended</th><th>Certificates</th><th>Rating</th></tr></thead>
            <tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['title']) ?></strong><small><?= e($row['club_name']) ?></small></td><td><?= e($row['event_date']) ?></td><td><?= (int) $row['maximum_participants'] ?></td><td><?= (int) $row['registrations'] ?></td><td><?= (int) $row['attendees'] ?></td><td><?= (int) $row['certificates'] ?></td><td><?= e((string) $row['average_rating']) ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
