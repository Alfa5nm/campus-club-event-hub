<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Campus life, in one place';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div>
        <span class="eyebrow">Discover · Join · Belong</span>
        <h1>Your campus is full of possibilities.</h1>
        <p>Find the clubs that feel like you, register for upcoming events, and keep every membership, check-in, and certificate in one calm place.</p>
        <div class="hero-actions">
            <a class="button button-primary" href="events.php">Explore events</a>
            <a class="button button-secondary" href="clubs.php">Browse clubs</a>
        </div>
    </div>
    <div class="hero-art" aria-hidden="true">
        <span class="orb one"></span><span class="orb two"></span>
        <article class="event-ticket">
            <small>UPCOMING EVENT</small>
            <h3>Build Night: Ideas into Impact</h3>
            <p>Hosted by Computing Club</p>
            <div class="meta">Aug 28 · 4:00 PM<br>Innovation Lab</div>
        </article>
    </div>
</section>
<section class="section">
    <div class="section-head"><div><span class="eyebrow">One shared hub</span><h2>Everything between joining and showing up</h2></div></div>
    <div class="grid">
        <article class="card"><span class="card-tag">01</span><h3>Find your community</h3><p class="muted">Explore clubs by category, learn what they do, and request membership.</p></article>
        <article class="card"><span class="card-tag">02</span><h3>Never miss an event</h3><p class="muted">Browse upcoming activities, reserve your place, and keep your registrations organized.</p></article>
        <article class="card"><span class="card-tag">03</span><h3>Lead with clarity</h3><p class="muted">Approved executives can manage members, roles, club profiles, and events.</p></article>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
