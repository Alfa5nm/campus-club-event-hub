<?php
require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
require_login();
$uid = (int)user()['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'read_all') {
        mark_all_notifications_read($uid);
    } elseif ($action === 'read') {
        mark_notification_read($uid, (int) ($_POST['notification_id'] ?? 0));
    }flash('success', 'Notifications updated.');
    redirect('faisal/notifications.php');
}
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 12;
$totalStmt = db()->prepare('SELECT COUNT(*) FROM notification WHERE recipient_user_id=?');
$totalStmt->execute([$uid]);
$total = (int)$totalStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$offset = ($page - 1) * $per;
$stmt = db()->prepare("SELECT * FROM notification WHERE recipient_user_id=? ORDER BY created_at DESC LIMIT $per OFFSET $offset");
$stmt->execute([$uid]);
$notices = $stmt->fetchAll();
$pageTitle = 'Notifications';
require dirname(__DIR__) . '/mixed/includes/header.php';?>
<section class="page-shell"><div class="page-head"><div><span class="eyebrow">PERSONAL INBOX</span><h2>Campus signals,<br><span class="accent-script">collected.</span></h2><p>Notifications appear automatically when memberships, attendance, certificates, or announcements change.</p></div><form method="post" data-ajax="<?= e(app_url('faisal/api/notification.php')) ?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="button button-quiet" name="action" value="read_all">Mark all as read</button></form></div><div class="notification-list"><?php foreach ($notices as $n):?><article class="notification-item <?=$n['is_read'] ? '' : 'unread'?>" data-notification="<?=$n['notification_id']?>"><span class="notice-dot"></span><div><span class="card-tag"><?=e($n['notification_type'])?></span><p><?=e($n['message'])?></p><small><?=e(date('M j, Y · H:i', strtotime($n['created_at'])))?></small></div><?php if (!$n['is_read']):?><form method="post" data-ajax="<?= e(app_url('faisal/api/notification.php')) ?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="notification_id" value="<?=$n['notification_id']?>"><button class="text-link" name="action" value="read">Mark read</button></form><?php endif;?></article><?php endforeach;?><?php if (!$notices):?><div class="empty">Your inbox is clear.</div><?php endif;?></div><?php if ($pages > 1):?><nav class="pagination"><?php for ($i = 1;$i <= $pages;$i++):?><a class="<?=$i === $page ? 'active' : ''?>" href="<?= e(app_url('faisal/notifications.php')) ?>?page=<?=$i?>"><?=$i?></a><?php endfor;?></nav><?php endif;?></section><?php require dirname(__DIR__) . '/mixed/includes/footer.php';?>
