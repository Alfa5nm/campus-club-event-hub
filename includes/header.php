<?php
$pageTitle = $pageTitle ?? 'Campus Club Hub';
$currentUser = user();
$managedNavigation = $currentUser ? managed_clubs() : [];
$unreadCount = $currentUser ? unread_notification_count() : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · Campus Club Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/expansion.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php"><span class="brand-mark">CH</span><span>Campus<br><em>Hub</em></span></a>
    <div class="issue-stamp"><strong><?= e(strtoupper(date('D'))) ?></strong><span><?= e(date('d M Y')) ?><br>Campus edition</span></div>
    <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <nav class="nav-links" aria-label="Primary navigation">
        <a class="<?= active_page('clubs.php') ?>" href="clubs.php">Clubs</a>
        <a class="<?= active_page('events.php') ?>" href="events.php">Events</a>
        <a class="<?= active_page('leaderboard.php') ?>" href="leaderboard.php">Leaderboard</a>
        <?php if ($currentUser): ?>
            <a class="<?= active_page('memberships.php') ?>" href="memberships.php">Memberships</a>
            <?php if ($managedNavigation): ?><a class="<?= active_page('attendance.php') ?>" href="attendance.php">Attendance</a><a class="<?= active_page('announcements.php') ?>" href="announcements.php">Publish</a><a class="<?= active_page('reports.php') ?>" href="reports.php">Reports</a><?php endif; ?>
            <a class="<?= active_page('certificates.php') ?>" href="certificates.php">Certificates</a>
            <?php if (!is_admin()): ?><a class="<?= active_page('feedback.php') ?>" href="feedback.php">Feedback</a><?php endif; ?>
            <a class="<?= active_page('participation.php') ?>" href="participation.php">History</a>
            <?php if (!$currentUser || $currentUser['role'] !== 'Admin'): ?><a class="<?= active_page('recommendations.php') ?>" href="recommendations.php">For you</a><?php endif; ?>
            <a class="nav-notice <?= active_page('notifications.php') ?>" href="notifications.php">Inbox<?php if ($unreadCount):?><b data-unread-count><?=$unreadCount?></b><?php endif;?></a>
            <a class="<?= active_page('dashboard.php') ?>" href="dashboard.php">Dashboard</a>
            <?php if (is_admin()): ?><a class="<?= active_page('admin.php') ?>" href="admin.php">Admin</a><?php endif; ?>
            <a class="profile-link" href="profile.php"><span><?= e(strtoupper(substr($currentUser['full_name'], 0, 1))) ?></span><?= e(explode(' ', $currentUser['full_name'])[0]) ?></a>
            <a class="button button-ink" href="logout.php">Sign out</a>
        <?php else: ?>
            <a href="login.php">Sign in</a>
            <a class="button button-coral" href="signup.php">Join CampusHub ↗</a>
        <?php endif; ?>
    </nav>
</header>
<main>
<?php foreach (pull_flashes() as $message): ?>
    <div class="flash flash-<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
<?php endforeach; ?>
