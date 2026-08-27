<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$clubs = leaderboard();
$pageTitle = 'Club leaderboard';
require __DIR__ . '/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">ACTIVE CLUB INDEX</span>
            <h2>Campus energy,<br><span class="accent-script">measured fairly.</span></h2>
            <p>Score = events × 10 + registrations × 2 + attendees × 3 + average rating × 5.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Rank</th><th>Club</th><th>Events</th><th>Registrations</th><th>Attendees</th><th>Rating</th><th>Score</th></tr></thead>
            <tbody>
                <?php foreach ($clubs as $index => $club): ?>
                    <tr>
                        <td><strong>#<?= $index + 1 ?></strong></td>
                        <td><?= e($club['club_name']) ?></td>
                        <td><?= (int) $club['event_count'] ?></td>
                        <td><?= (int) $club['registrations'] ?></td>
                        <td><?= (int) $club['attendees'] ?></td>
                        <td><?= e((string) $club['average_rating']) ?></td>
                        <td><span class="badge"><?= (int) $club['activity_score'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
