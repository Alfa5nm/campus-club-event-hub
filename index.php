<?php
require_once __DIR__ . '/includes/bootstrap.php';
$stats = [
    'clubs' => (int) db()->query("SELECT COUNT(*) FROM clubs WHERE status='Active'")->fetchColumn(),
    'events' => (int) db()->query("SELECT COUNT(*) FROM events WHERE status='Upcoming' AND event_date >= CURDATE()")->fetchColumn(),
    'members' => (int) db()->query("SELECT COUNT(*) FROM club_membership WHERE approval_status='Approved' AND membership_status='Active'")->fetchColumn(),
];
$featured = db()->query("SELECT e.*, c.club_name, COUNT(er.registration_id) AS registration_count FROM events e JOIN clubs c ON c.club_id=e.club_id LEFT JOIN event_registration er ON er.event_id=e.event_id AND er.registration_status='Registered' WHERE e.status='Upcoming' AND e.event_date >= CURDATE() GROUP BY e.event_id ORDER BY e.event_date,e.start_time LIMIT 3")->fetchAll();
$spotlight = $featured[0] ?? null;
$pageTitle = 'Campus life, in one place';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div>
        <span class="eyebrow">Discover · Join · Belong</span>
        <h1>Campus life,<br><span class="accent-script">in motion.</span></h1>
        <p>A live home for the communities, ideas, and events shaping your university—built around what is actually happening now.</p>
        <div class="hero-actions">
            <a class="button button-primary" href="events.php">Explore events</a>
            <a class="button button-secondary" href="clubs.php">Browse clubs</a>
        </div>
    </div>
    <div class="hero-art">
        <span class="orb one"></span><span class="orb two"></span><span class="orbit-line"></span>
        <article class="event-ticket">
            <div class="ticket-top"><small>UP NEXT</small><span class="live-dot">Live data</span></div>
            <div class="ticket-date"><strong><?= $spotlight ? e(date('d', strtotime($spotlight['event_date']))) : '—' ?></strong><span><?= $spotlight ? e(strtoupper(date('M', strtotime($spotlight['event_date'])))) : 'SOON' ?></span></div>
            <h3><?= e($spotlight['title'] ?? 'New events are on the way') ?></h3>
            <p><?= e($spotlight['club_name'] ?? 'CampusHub') ?></p>
            <div class="meta"><?= $spotlight ? e(date('g:i A', strtotime($spotlight['start_time']))) . ' · ' . e($spotlight['venue']) : 'Check back shortly' ?><br><?= $spotlight ? (int)$spotlight['registration_count'] . ' students registered' : '' ?></div>
        </article>
        <div class="floating-chip chip-one">✦ <?= $stats['clubs'] ?> active clubs</div>
        <div class="floating-chip chip-two">↗ <?= $stats['events'] ?> events ahead</div>
    </div>
</section>
<section class="pulse-strip" aria-label="CampusHub live statistics">
    <div><strong data-count="<?= $stats['clubs'] ?>">0</strong><span>active clubs</span></div>
    <div><strong data-count="<?= $stats['events'] ?>">0</strong><span>upcoming events</span></div>
    <div><strong data-count="<?= $stats['members'] ?>">0</strong><span>active memberships</span></div>
</section>
<section class="section event-showcase">
    <div class="section-head"><div><span class="eyebrow">Happening soon</span><h2>Pick your next campus moment</h2></div><a class="text-link" href="events.php">See all events →</a></div>
    <div class="grid dynamic-grid">
        <?php foreach ($featured as $i => $event): ?>
            <article class="card event-preview reveal" style="--delay:<?= $i * 80 ?>ms">
                <div class="event-date-block"><strong><?= e(date('d', strtotime($event['event_date']))) ?></strong><span><?= e(strtoupper(date('M', strtotime($event['event_date'])))) ?></span></div>
                <div><span class="card-tag"><?= e($event['event_category']) ?></span><h3><?= e($event['title']) ?></h3><p class="muted"><?= e($event['club_name']) ?> · <?= e($event['venue']) ?></p></div>
                <div class="capacity"><span style="width:<?= min(100,round(((int)$event['registration_count']/(int)$event['maximum_participants'])*100)) ?>%"></span></div>
                <small><?= (int)$event['registration_count'] ?> of <?= (int)$event['maximum_participants'] ?> places claimed</small>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
