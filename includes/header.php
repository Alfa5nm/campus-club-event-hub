<?php
$pageTitle = $pageTitle ?? 'Campus Club Hub';
$currentUser = user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · Campus Club Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php"><span class="brand-mark">C</span><span>Campus<span>Hub</span></span></a>
    <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <nav class="nav-links" aria-label="Primary navigation">
        <a class="<?= active_page('clubs.php') ?>" href="clubs.php">Clubs</a>
        <a class="<?= active_page('events.php') ?>" href="events.php">Events</a>
        <?php if ($currentUser): ?>
            <a class="<?= active_page('memberships.php') ?>" href="memberships.php">Memberships</a>
            <a class="<?= active_page('dashboard.php') ?>" href="dashboard.php">Dashboard</a>
            <a class="button button-quiet" href="logout.php">Sign out</a>
        <?php else: ?>
            <a href="login.php">Sign in</a>
            <a class="button button-primary" href="signup.php">Join CampusHub</a>
        <?php endif; ?>
    </nav>
</header>
<main>
<?php foreach (pull_flashes() as $message): ?>
    <div class="flash flash-<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
<?php endforeach; ?>
