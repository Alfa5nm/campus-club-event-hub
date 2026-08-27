<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$_SESSION['user'] = ['user_id' => 1,'full_name' => 'Amina Rahman','role' => 'Student'];
$minimums = ['users' => 13,'students' => 11,'administrators' => 2,'student_interest' => 24,'student_guidance' => 5,'clubs' => 8,'club_membership' => 20,'club_gallery' => 10,'events' => 12,'event_registration' => 25,'attendance' => 7,'certificate' => 5,'feedback' => 5,'announcement' => 8,'notification' => 36,'password_reset_token' => 2];
foreach ($minimums as $table => $minimum) {
    test_expect((int)db()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() >= $minimum, "seed includes substantial $table data");
}
$seedFiles = (int)db()->query("SELECT COUNT(*) FROM certificate WHERE status='Active' AND file_path LIKE 'assets/demo-certificates/%'")->fetchColumn();
test_expect($seedFiles === 4, 'seed includes downloadable active certificate records');
foreach (db()->query("SELECT file_path FROM certificate WHERE status='Active'")->fetchAll(PDO::FETCH_COLUMN) as $seedFile) {
    test_expect(is_file(dirname(__DIR__).'/'.$seedFile), 'seeded active certificate file exists');
}
$activeUsers = (int)db()->query("SELECT COUNT(*) FROM users WHERE status='Active'")->fetchColumn();
$systemRecipients = (int)db()->query("SELECT COUNT(*) FROM notification WHERE notification_type='System Notice' AND message='Explore clubs and events from one shared campus platform.'")->fetchColumn();
test_expect($systemRecipients === $activeUsers, 'seeded system announcement reaches every active user');
$present = mark_attendance(1, 1, 'Present');
test_expect($present['status'] === 'Present', 'authorized executive marks a registrant present');
test_expect(($present['certificate']['status'] ?? '') === 'Active', 'present attendance issues an active certificate');
$certificateId = (int)$present['certificate']['certificate_id'];
$stmt = db()->prepare('SELECT status,file_path FROM certificate WHERE certificate_id=?');
$stmt->execute([$certificateId]);
$certificate = $stmt->fetch();
test_expect($certificate && is_file(dirname(__DIR__).'/'.$certificate['file_path']), 'certificate PDF is written to protected storage');
$beforeRepeat = (int)db()->query("SELECT COUNT(*) FROM notification WHERE recipient_user_id=2 AND notification_type='Certificate issued'")->fetchColumn();
$repeat = mark_attendance(1, 1, 'Present');
$afterRepeat = (int)db()->query("SELECT COUNT(*) FROM notification WHERE recipient_user_id=2 AND notification_type='Certificate issued'")->fetchColumn();
test_expect((int)$repeat['certificate']['certificate_id'] === $certificateId && $afterRepeat === $beforeRepeat, 'repeated roster clicks remain idempotent without duplicate certificates or notifications');

$absent = mark_attendance(1, 1, 'Absent');
$stmt = db()->prepare('SELECT status FROM certificate WHERE certificate_id=?');
$stmt->execute([$certificateId]);
test_expect($stmt->fetchColumn() === 'Revoked', 'changing attendance to absent revokes the certificate');

$expectedRecipients = (int)db()->query("SELECT COUNT(DISTINCT student_user_id) FROM club_membership WHERE club_id=1 AND approval_status='Approved' AND membership_status='Active'")->fetchColumn();
$announcement = save_announcement(['club_id' => 1,'message' => 'One fan-out only for this simplified announcement.']);
test_expect($announcement['recipient_count'] === $expectedRecipients, 'active club announcement targets every approved active member');
$title = (string)db()->query('SELECT title FROM announcement WHERE announcement_id='.(int)$announcement['announcement_id'])->fetchColumn();
test_expect($title === 'One fan-out only for this simplified announcement', 'announcement title is generated from the message');
$edited = save_announcement(['announcement_id' => $announcement['announcement_id'],'club_id' => 1,'message' => 'Still one fan-out only after editing.']);
test_expect($edited['recipient_count'] === 0, 'editing an activated announcement does not duplicate notifications');

test_expect((int)db()->query('SELECT COUNT(*) FROM event_registration WHERE qr_token IS NOT NULL')->fetchColumn() === 0, 'fresh registrations do not use QR tokens');
$wrongEvent = false;
try {
    mark_attendance(1, 2, 'Present');
} catch (RuntimeException) {
    $wrongEvent = true;
}
test_expect($wrongEvent, 'a registration from another event cannot be marked');
$cancelled = false;
db()->exec("UPDATE event_registration SET registration_status='Cancelled' WHERE registration_id=1");
try {
    mark_attendance(1, 1, 'Present');
} catch (RuntimeException) {
    $cancelled = true;
}db()->exec("UPDATE event_registration SET registration_status='Absent' WHERE registration_id=1");
test_expect($cancelled, 'cancelled registrations cannot be marked');

$_SESSION['user'] = ['user_id' => 3,'full_name' => 'Sadia Islam','role' => 'Student'];
$blocked = false;
try {
    mark_attendance(1, 1, 'Present');
} catch (DomainException) {
    $blocked = true;
}
test_expect($blocked, 'an executive from another club cannot manage attendance');

if (isset($certificate['file_path'])) {
    $generated = realpath(dirname(__DIR__).'/'.$certificate['file_path']);
    $uploadsRoot = realpath(dirname(__DIR__).'/uploads');
    if ($generated && $uploadsRoot && str_starts_with($generated, $uploadsRoot)) {
        unlink($generated);
    }
}
db()->exec('DROP DATABASE campus_club_hub_test');
echo "CORE EXPANSION SMOKE TEST COMPLETE\n";
