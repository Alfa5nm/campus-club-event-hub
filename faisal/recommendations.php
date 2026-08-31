<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
require_login();

$events = is_admin() ? [] : recommended_events(current_user_id());
$pageTitle = 'Recommended events';
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">PERSONALIZED DISCOVERY</span>
            <h2>Events selected<br><span class="accent-script">for your interests.</span></h2>
            <p>Recommendations combine interest matches, popularity, availability, and event timing.</p>
        </div>
    </div>

    <?php if (is_admin()): ?>
        <div class="empty">Recommendations are designed for student accounts.</div>
    <?php else: ?>
        <div class="grid dynamic-grid">
            <?php foreach ($events as $event): ?>
                <article class="card event-card">
                    <div class="card-content">
                        <span class="card-tag"><?= e($event['event_category']) ?></span>
                        <h3><?= e($event['title']) ?></h3>
                        <p><?= e($event['recommendation_reason']) ?></p>
                        <p class="muted"><?= e($event['club_name']) ?> · <?= e(date('M j', strtotime($event['event_date']))) ?> · <?= e($event['venue']) ?></p>
                        <div class="card-footer">
                            <span class="badge">Score <?= (int) $event['recommendation_score'] ?></span>
                            <a class="button button-primary" href="<?= e(app_url('diha/events.php')) ?>">View event</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$events): ?>
                <div class="empty">No open recommendations are available right now.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
