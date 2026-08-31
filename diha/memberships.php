<?php
require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
require_login();
$uid = (int)user()['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $membershipId = (int)($_POST['membership_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    try {
        $result = update_membership(
            $membershipId,
            $action,
            isset($_POST['member_role']) ? (string) $_POST['member_role'] : null
        );
        flash('success', 'Membership updated.');
        redirect('diha/memberships.php?club=' . $result['club_id']);
    } catch (DomainException $exception) {
        http_response_code(403);
        exit($exception->getMessage());
    } catch (InvalidArgumentException $exception) {
        flash('error', $exception->getMessage());
        redirect('diha/memberships.php');
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
        redirect('diha/memberships.php');
    }
}
$s = db()->prepare('SELECT cm.*,c.club_name,c.category FROM club_membership cm JOIN clubs c ON c.club_id=cm.club_id WHERE cm.student_user_id=? ORDER BY cm.join_date DESC');
$s->execute([$uid]);
$mine = $s->fetchAll();
$managed = managed_clubs(false);
$selected = (int)($_GET['club'] ?? ($managed[0]['club_id'] ?? 0));
$members = [];
if ($selected && can_manage_club($selected)) {
    $s = db()->prepare('SELECT cm.*,u.full_name,u.email,s.student_number FROM club_membership cm JOIN users u ON u.user_id=cm.student_user_id LEFT JOIN students s ON s.user_id=u.user_id WHERE cm.club_id=? ORDER BY FIELD(cm.approval_status,"Pending","Approved","Rejected"),u.full_name');
    $s->execute([$selected]);
    $members = $s->fetchAll();
}
$pageTitle = 'Memberships';
require dirname(__DIR__) . '/mixed/includes/header.php';?>
<section class="page-shell"><div class="page-head"><div><span class="eyebrow">Membership centre</span><h2>Requests, roles, and communities</h2><p>Track your club requests and manage members where you have executive authority.</p></div></div>
<h3>Your memberships</h3><div class="grid" style="margin-bottom:42px"><?php foreach ($mine as $m):?><article class="card"><span class="card-tag"><?=e($m['category'])?></span><h3><?=e($m['club_name'])?></h3><p><strong><?=e($m['member_role'])?></strong></p><span class="badge <?=$m['approval_status'] === 'Pending' ? 'badge-warn' : ''?>"><?=e($m['approval_status'])?> · <?=e($m['membership_status'])?></span></article><?php endforeach;?><?php if (!$mine):?><div class="empty">You have not requested any club memberships yet.</div><?php endif;?></div>
<?php if ($managed):?><div class="page-head"><div><h3>Manage club members</h3><p>Select one of your assigned clubs.</p></div><form method="get"><select name="club" onchange="this.form.submit()" class="button button-quiet"><?php foreach ($managed as $c):?><option value="<?=$c['club_id']?>" <?=$selected == (int)$c['club_id'] ? 'selected' : ''?>><?=e($c['club_name'])?></option><?php endforeach;?></select></form></div><table class="data-table"><thead><tr><th>Student</th><th>Joined</th><th>Approval</th><th>Role</th><th>Actions</th></tr></thead><tbody><?php foreach ($members as $m):?><tr data-membership-row><td><strong><?=e($m['full_name'])?></strong><br><small><?=e($m['student_number'] ?? $m['email'])?></small></td><td><?=e(date('M j, Y', strtotime($m['join_date'])))?></td><td><span class="badge <?=$m['approval_status'] === 'Pending' ? 'badge-warn' : ''?>" data-approval-state><?=e($m['approval_status'])?></span></td><td><form method="post" class="actions" data-ajax="<?= e(app_url('diha/api/membership.php')) ?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="role"><input type="hidden" name="membership_id" value="<?=$m['membership_id']?>"><select name="member_role"><?php foreach (array_merge(['Member'], executive_roles()) as $role):?><option <?=$role === $m['member_role'] ? 'selected' : ''?>><?=e($role)?></option><?php endforeach;?></select><button class="button button-quiet">Save</button></form></td><td><div class="actions"><?php if ($m['approval_status'] === 'Pending'):?><form method="post" data-ajax="<?= e(app_url('diha/api/membership.php')) ?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="membership_id" value="<?=$m['membership_id']?>"><button class="button button-secondary" name="action" value="approve">Approve</button><button class="button button-danger" name="action" value="reject">Reject</button></form><?php elseif ($m['membership_status'] === 'Active'):?><form method="post" data-ajax="<?= e(app_url('diha/api/membership.php')) ?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="membership_id" value="<?=$m['membership_id']?>"><button class="button button-danger" name="action" value="remove" data-confirm="Remove this member from the club?">Remove</button></form><?php endif;?></div></td></tr><?php endforeach;?></tbody></table><?php endif;?></section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php';?>
