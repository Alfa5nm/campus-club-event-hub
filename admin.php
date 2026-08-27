<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = $_POST['type'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = [
        'user' => ['table' => 'users', 'key' => 'user_id', 'field' => 'status', 'values' => ['Active', 'Suspended', 'Deactivated']],
        'club' => ['table' => 'clubs', 'key' => 'club_id', 'field' => 'status', 'values' => ['Pending', 'Active', 'Suspended']],
        'event' => ['table' => 'events', 'key' => 'event_id', 'field' => 'status', 'values' => ['Upcoming', 'Completed', 'Cancelled']],
        'feedback' => ['table' => 'feedback', 'key' => 'feedback_id', 'field' => 'status', 'values' => ['Visible', 'Hidden', 'Reported']],
        'certificate' => ['table' => 'certificate', 'key' => 'certificate_id', 'field' => 'status', 'values' => ['Active', 'Revoked']],
        'announcement' => ['table' => 'announcement', 'key' => 'announcement_id', 'field' => 'status', 'values' => ['Active', 'Expired', 'Removed']],
    ];

    if (!isset($allowed[$type]) || !in_array($status, $allowed[$type]['values'], true)) {
        flash('error', 'That moderation action is not valid.');
        redirect('admin.php');
    }

    $config = $allowed[$type];
    $sql = sprintf(
        'UPDATE `%s` SET `%s` = ? WHERE `%s` = ?',
        $config['table'],
        $config['field'],
        $config['key']
    );
    db()->prepare($sql)->execute([$status, $id]);
    flash('success', ucfirst($type) . ' status updated.');
    redirect('admin.php?section=' . urlencode($type));
}

$section = $_GET['section'] ?? 'user';
$sections = ['user', 'club', 'event', 'feedback', 'attendance', 'certificate', 'announcement'];

if (!in_array($section, $sections, true)) {
    $section = 'user';
}

$queries = [
    'user' => "SELECT user_id AS id, full_name AS name, email AS detail, status FROM users ORDER BY created_at DESC",
    'club' => "SELECT club_id AS id, club_name AS name, category AS detail, status FROM clubs ORDER BY created_at DESC",
    'event' => "SELECT event_id AS id, title AS name, event_date AS detail, status FROM events ORDER BY event_date DESC",
    'feedback' => "SELECT f.feedback_id AS id, e.title AS name, CONCAT(f.rating, '/5 · ', COALESCE(f.review_text, 'No review')) AS detail, f.status FROM feedback f JOIN event_registration er ON er.registration_id=f.registration_id JOIN events e ON e.event_id=er.event_id ORDER BY f.submitted_at DESC",
    'attendance' => "SELECT a.attendance_id AS id, u.full_name AS name, e.title AS detail, a.attendance_status AS status FROM attendance a JOIN event_registration er ON er.registration_id=a.registration_id JOIN users u ON u.user_id=er.student_user_id JOIN events e ON e.event_id=er.event_id ORDER BY a.marked_at DESC",
    'certificate' => "SELECT certificate_id AS id, certificate_number AS name, issue_date AS detail, status FROM certificate ORDER BY issue_date DESC",
    'announcement' => "SELECT announcement_id AS id, title AS name, announcement_type AS detail, status FROM announcement ORDER BY published_at DESC",
];
$items = db()->query($queries[$section])->fetchAll();

$statusChoices = [
    'user' => ['Active', 'Suspended', 'Deactivated'],
    'club' => ['Pending', 'Active', 'Suspended'],
    'event' => ['Upcoming', 'Completed', 'Cancelled'],
    'feedback' => ['Visible', 'Hidden', 'Reported'],
    'certificate' => ['Active', 'Revoked'],
    'announcement' => ['Active', 'Expired', 'Removed'],
];

$pageTitle = 'Administration';
require __DIR__ . '/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">SYSTEM MODERATION</span>
            <h2>Control with<br><span class="accent-script">history intact.</span></h2>
            <p>Moderation changes status without destroying relational records.</p>
        </div>
        <a class="button button-primary" href="reports.php">Open reports</a>
    </div>

    <nav class="filter-chips" aria-label="Administration sections">
        <?php foreach ($sections as $name): ?>
            <a class="filter-chip <?= $section === $name ? 'active' : '' ?>" href="admin.php?section=<?= e($name) ?>"><?= e(ucfirst($name)) ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Record</th><th>Details</th><th>Status</th><th>Moderation</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= e((string) $item['name']) ?></strong></td>
                        <td><?= e((string) $item['detail']) ?></td>
                        <td><span class="badge"><?= e((string) $item['status']) ?></span></td>
                        <td>
                            <?php if (isset($statusChoices[$section])): ?>
                                <form class="actions" method="post" data-ajax="api/moderation.php">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="type" value="<?= e($section) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <select name="status">
                                        <?php foreach ($statusChoices[$section] as $status): ?>
                                            <option <?= $status === $item['status'] ? 'selected' : '' ?>><?= e($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button button-quiet">Save</button>
                                </form>
                            <?php else: ?>
                                <a class="text-link" href="attendance.php">Manage attendance</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
