<?php
require_once __DIR__ . '/_json.php';
$userId = (int) user()['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'request_join') {
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $student = db()->prepare('SELECT 1 FROM students WHERE user_id=?'); $student->execute([$userId]);
    if (!$student->fetchColumn()) json_response(false, 'Only student accounts can join clubs.', [], 403);
    try {
        db()->prepare("INSERT INTO club_membership (student_user_id,club_id,member_role,approval_status,membership_status) VALUES (?,?,'Member','Pending','Active')")->execute([$userId,$clubId]);
        json_response(true, 'Membership request sent.', ['state'=>'Pending']);
    } catch (PDOException $exception) {
        json_response(false, 'You already have a membership or pending request for this club.', [], 409);
    }
}

$membershipId = (int) ($_POST['membership_id'] ?? 0);
$stmt = db()->prepare('SELECT club_id FROM club_membership WHERE membership_id=?'); $stmt->execute([$membershipId]);
$clubId = (int) $stmt->fetchColumn();
if (!$clubId || !can_manage_club($clubId)) json_response(false, 'You are not authorized to manage this membership.', [], 403);

if ($action === 'approve') db()->prepare("UPDATE club_membership SET approval_status='Approved',membership_status='Active' WHERE membership_id=?")->execute([$membershipId]);
elseif ($action === 'reject') db()->prepare("UPDATE club_membership SET approval_status='Rejected' WHERE membership_id=?")->execute([$membershipId]);
elseif ($action === 'remove') db()->prepare("UPDATE club_membership SET membership_status='Removed' WHERE membership_id=?")->execute([$membershipId]);
elseif ($action === 'role') {
    $role = $_POST['member_role'] ?? 'Member';
    if (!in_array($role,array_merge(['Member'],executive_roles()),true)) json_response(false,'Invalid member role.',[],422);
    db()->prepare('UPDATE club_membership SET member_role=? WHERE membership_id=?')->execute([$role,$membershipId]);
} else json_response(false, 'Unknown membership action.', [], 400);

json_response(true, 'Membership updated.', ['action'=>$action,'membership_id'=>$membershipId]);
