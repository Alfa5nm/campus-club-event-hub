<?php
require_once __DIR__ . '/includes/bootstrap.php';
$stats = ['clubs' => (int)db()->query("SELECT COUNT(*) FROM clubs WHERE status='Active'")->fetchColumn(),'events' => (int)db()->query("SELECT COUNT(*) FROM events WHERE status='Upcoming' AND event_date>=CURDATE()")->fetchColumn(),'members' => (int)db()->query("SELECT COUNT(*) FROM club_membership WHERE approval_status='Approved' AND membership_status='Active'")->fetchColumn()];
$featured = db()->query("SELECT e.*,c.club_name,COUNT(er.registration_id) registration_count FROM events e JOIN clubs c ON c.club_id=e.club_id LEFT JOIN event_registration er ON er.event_id=e.event_id AND er.registration_status='Registered' WHERE e.status='Upcoming' AND e.event_date>=CURDATE() GROUP BY e.event_id ORDER BY e.event_date,e.start_time LIMIT 3")->fetchAll();
$clubs = db()->query("SELECT c.*,COUNT(DISTINCT cm.membership_id) member_count,MIN(e.event_date) next_event FROM clubs c LEFT JOIN club_membership cm ON cm.club_id=c.club_id AND cm.approval_status='Approved' LEFT JOIN events e ON e.club_id=c.club_id AND e.status='Upcoming' AND e.event_date>=CURDATE() WHERE c.status='Active' GROUP BY c.club_id ORDER BY member_count DESC LIMIT 3")->fetchAll();
$hero = $featured[0] ?? null;
$images = ['assets/images/campus-study.jpg','assets/images/club-collaboration.jpg','assets/images/campus-walk.jpg'];
$pageTitle = 'The campus, happening now';
require __DIR__.'/includes/header.php';
?>
<section class="front-page">
  <div class="front-kicker"><span>THE CAMPUS EDIT</span><span><?=e(strtoupper(date('l, F j')))?> · LIVE</span></div>
  <div class="lead-grid">
    <article class="lead-story">
      <div class="lead-photo"><img src="<?= e(app_url('mixed/assets/images/campus-walk.jpg')) ?>" alt="Students walking together on campus"><span class="photo-label">CAMPUS / COMMUNITY</span></div>
      <div class="lead-copy"><span class="section-number">01</span><h1>Find your<br><em>people.</em></h1><p>Clubs, events, and the moments between lectures—collected in one living campus calendar.</p><div class="hero-actions"><a class="button button-coral" href="<?= e(app_url('diha/events.php')) ?>">See what’s on ↗</a><a class="underlined-link" href="<?= e(app_url('diha/clubs.php')) ?>">Explore <?= $stats['clubs'] ?> active clubs</a></div></div>
    </article>
    <aside class="now-column">
      <div class="now-head"><span class="live-pip"></span><strong>UP NEXT</strong><span>Auto-updated</span></div>
      <?php if ($hero):?><div class="date-poster"><span><?=e(strtoupper(date('M', strtotime($hero['event_date']))))?></span><strong><?=e(date('d', strtotime($hero['event_date'])))?></strong><small><?=e(date('D', strtotime($hero['event_date'])))?></small></div><span class="story-tag"><?=e($hero['event_category'])?></span><h2><?=e($hero['title'])?></h2><p><?=e($hero['club_name'])?> presents an evening at <?=e($hero['venue'])?>.</p><div class="rule-meta"><span><?=e(date('g:i A', strtotime($hero['start_time'])))?></span><span><?=(int)$hero['registration_count']?> / <?=(int)$hero['maximum_participants']?> joined</span></div><a class="arrow-link" href="<?= e(app_url('diha/events.php')) ?>">View event <b>→</b></a><?php endif;?>
    </aside>
  </div>
</section>
<section class="ticker" aria-label="Live campus statistics"><div class="ticker-track"><span><b data-count="<?=$stats['events']?>">0</b> events ahead</span><i>✦</i><span><b data-count="<?=$stats['members']?>">0</b> active memberships</span><i>✦</i><span><b data-count="<?=$stats['clubs']?>">0</b> communities</span><i>✦</i><span>Make this semester count</span></div></section>
<section class="editorial-section">
  <header class="editorial-heading"><div><span class="section-number">02</span><p>YOUR WEEK, EDITED</p></div><h2>Three reasons to<br>leave the group chat.</h2><a class="underlined-link" href="<?= e(app_url('diha/events.php')) ?>">Full calendar →</a></header>
  <div class="story-grid"><?php foreach ($featured as $i => $event):?><article class="event-story reveal" style="--delay:<?=$i * 90?>ms"><div class="story-image"><img src="<?=e(media_url($event['poster'] ?: $images[$i % count($images)]))?>" alt=""><span><?=e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT))?></span></div><div class="story-body"><span class="story-tag"><?=e($event['event_category'])?></span><h3><?=e($event['title'])?></h3><p><?=e($event['club_name'])?> · <?=e(date('M j', strtotime($event['event_date'])))?> · <?=e($event['venue'])?></p><div class="capacity"><span style="width:<?=min(100, round(((int)$event['registration_count'] / (int)$event['maximum_participants']) * 100))?>%"></span></div></div></article><?php endforeach;?></div>
</section>
<section class="club-spread"><div class="club-spread-intro"><span class="section-number">03</span><p>CLUB DIRECTORY</p><h2>Belong to<br>something.</h2><p>Not a mailing list. Not another forgotten group. Find the people building, debating, creating, and changing campus.</p><a class="button button-sun" href="<?= e(app_url('diha/clubs.php')) ?>">Meet the clubs →</a></div><div class="club-stack"><?php foreach ($clubs as $i => $club):?><a href="<?= e(app_url('diha/clubs.php')) ?>" class="club-line"><span>0<?=$i + 1?></span><div><small><?=e(strtoupper($club['category']))?></small><h3><?=e($club['club_name'])?></h3></div><div><b><?=(int)$club['member_count']?></b><small>members</small></div><strong>↗</strong></a><?php endforeach;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
