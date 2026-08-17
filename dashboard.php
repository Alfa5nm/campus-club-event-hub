<?php
require_once __DIR__ . '/includes/bootstrap.php'; require_login();
$uid=(int)user()['user_id'];
$stats=['clubs'=>0,'memberships'=>0,'events'=>0,'managed'=>0];
$stats['clubs']=(int)db()->query("SELECT COUNT(*) FROM clubs WHERE status='Active'")->fetchColumn();
$stats['events']=(int)db()->query("SELECT COUNT(*) FROM events WHERE status IN ('Upcoming','Ongoing')")->fetchColumn();
$s=db()->prepare("SELECT COUNT(*) FROM club_membership WHERE student_user_id=? AND approval_status='Approved' AND membership_status='Active'");$s->execute([$uid]);$stats['memberships']=(int)$s->fetchColumn();
if(is_admin()){$stats['managed']=$stats['clubs'];}else{$marks=implode(',',array_fill(0,count(executive_roles()),'?'));$s=db()->prepare("SELECT COUNT(*) FROM club_membership WHERE student_user_id=? AND approval_status='Approved' AND membership_status='Active' AND member_role IN ($marks)");$s->execute(array_merge([$uid],executive_roles()));$stats['managed']=(int)$s->fetchColumn();}
$pageTitle='Dashboard';require __DIR__.'/includes/header.php';
?>
<section class="page-shell"><div class="page-head"><div><span class="eyebrow">Your overview</span><h2>Hello, <?= e(explode(' ',user()['full_name'])[0]) ?></h2><p>Here’s what is happening across your campus community.</p></div><span class="badge"><?= e(user()['role']) ?></span></div>
<div class="stat-grid"><div class="stat"><strong><?= $stats['clubs'] ?></strong><span>Active clubs</span></div><div class="stat"><strong><?= $stats['events'] ?></strong><span>Upcoming events</span></div><div class="stat"><strong><?= $stats['memberships'] ?></strong><span>Your memberships</span></div><div class="stat"><strong><?= $stats['managed'] ?></strong><span>Clubs you manage</span></div></div>
<div class="grid"><a class="card" href="clubs.php"><span class="card-tag">Explore</span><h3>Find a club</h3><p class="muted">Browse communities and request membership.</p></a><a class="card" href="events.php"><span class="card-tag">Discover</span><h3>Upcoming events</h3><p class="muted">See what clubs are organizing across campus.</p></a><a class="card" href="memberships.php"><span class="card-tag">Manage</span><h3>Membership centre</h3><p class="muted">Track requests or manage members if you are an executive.</p></a></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
