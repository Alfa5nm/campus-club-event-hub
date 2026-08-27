<?php
require_once __DIR__.'/includes/bootstrap.php';
require_login();
$clubs = managed_clubs();
if (!$clubs && !is_admin()) {
    http_response_code(403);
    exit('You do not manage an active club.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (($_POST['action'] ?? '') === 'save') {
            save_announcement($_POST);
            flash('success', 'Announcement saved and eligible recipients notified.');
        } else {
            $id = (int)($_POST['announcement_id'] ?? 0);
            $s = db()->prepare('SELECT club_id FROM announcement WHERE announcement_id=?');
            $s->execute([$id]);
            $club = $s->fetchColumn();
            if (($club === null && is_admin()) || ($club !== false && can_manage_club((int)$club))) {
                db()->prepare("UPDATE announcement SET status='Removed' WHERE announcement_id=?")->execute([$id]);
            }
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }redirect('announcements.php');
}
$edit = null;
if (isset($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM announcement WHERE announcement_id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
    if (!$edit || ($edit['club_id'] === null && !is_admin()) || ($edit['club_id'] !== null && !can_manage_club((int)$edit['club_id']))) {
        flash('error', 'You cannot edit that announcement.');
        redirect('announcements.php');
    }
}
if (is_admin()) {
    $items = db()->query("SELECT a.*,c.club_name,u.full_name FROM announcement a LEFT JOIN clubs c ON c.club_id=a.club_id JOIN users u ON u.user_id=a.publisher_user_id ORDER BY a.published_at DESC")->fetchAll();
} else {
    $ids = array_column($clubs, 'club_id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $s = db()->prepare("SELECT a.*,c.club_name,u.full_name FROM announcement a JOIN clubs c ON c.club_id=a.club_id JOIN users u ON u.user_id=a.publisher_user_id WHERE a.club_id IN ($in) ORDER BY a.published_at DESC");
    $s->execute($ids);
    $items = $s->fetchAll();
}
$pageTitle = 'Announcements';
require __DIR__.'/includes/header.php';?>
<section class="page-shell"><div class="page-head"><div><span class="eyebrow">PUBLISHING DESK</span><h2>Make the update<br><span class="accent-script">impossible to miss.</span></h2><p>Choose the audience and write the message. CampusHub creates the title, publishes it, and notifies eligible recipients automatically.</p></div></div>
<form class="card editor-card" method="post" data-ajax="api/announcement.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="announcement_id" value="<?=e((string)($edit['announcement_id'] ?? ''))?>"><h3><?=$edit ? 'Edit announcement' : 'Create announcement'?></h3><div class="field"><label>Audience</label><select name="club_id"><?php if (is_admin()):?><option value="" <?=($edit && $edit['club_id'] === null) ? 'selected' : ''?>>Entire CampusHub</option><?php endif;?><?php foreach ($clubs as $c):?><option value="<?=$c['club_id']?>" <?=(int)($edit['club_id'] ?? 0) === (int)$c['club_id'] ? 'selected' : ''?>><?=e($c['club_name'])?></option><?php endforeach;?></select></div><div class="field"><label>Message</label><textarea name="message" required maxlength="500" placeholder="What should students know?"><?=e($edit['message'] ?? '')?></textarea></div><p class="muted">The announcement publishes immediately. Notifications are created automatically; there is no separate notification form.</p><button class="button button-primary"><?=$edit ? 'Save changes' : 'Publish announcement'?></button><?php if ($edit):?><a class="button button-quiet" href="announcements.php">Cancel edit</a><?php endif;?></form>
<div class="agenda announcement-list"><?php foreach ($items as $a):?><article data-announcement="<?=$a['announcement_id']?>"><div class="event-date-block"><strong><?=e(date('d', strtotime($a['published_at'])))?></strong><span><?=e(strtoupper(date('M', strtotime($a['published_at']))))?></span></div><div><span class="card-tag"><?=e($a['announcement_type'])?></span><h3><?=e($a['title'])?></h3><p><?=e($a['message'])?></p><small><?=e($a['club_name'] ?? 'Entire CampusHub')?> · by <?=e($a['full_name'])?></small></div><span class="badge"><?=e($a['status'])?></span><div class="actions"><a class="button button-quiet" href="announcements.php?edit=<?=$a['announcement_id']?>">Edit</a><form method="post" data-ajax="api/announcement.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="announcement_id" value="<?=$a['announcement_id']?>"><button class="button button-danger" name="action" value="remove" data-confirm="Remove this announcement?">Remove</button></form></div></article><?php endforeach;?></div></section><?php require __DIR__.'/includes/footer.php';?>
