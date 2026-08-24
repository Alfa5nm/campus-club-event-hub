<?php
declare(strict_types=1);

putenv('CAMPUSHUB_DB_NAME=campus_club_hub_test');
session_start();
require __DIR__ . '/../includes/bootstrap.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo "PASS: $message\n";
}

$_SESSION['user']=['user_id'=>1,'full_name'=>'Amina Rahman','role'=>'Student'];
$present=mark_attendance(1,1,'Present');
expect($present['status']==='Present','authorized executive marks a registrant present');
expect(($present['certificate']['status']??'')==='Active','present attendance issues an active certificate');
$certificateId=(int)$present['certificate']['certificate_id'];
$stmt=db()->prepare('SELECT status,file_path FROM certificate WHERE certificate_id=?');$stmt->execute([$certificateId]);$certificate=$stmt->fetch();
expect($certificate&&is_file(dirname(__DIR__).'/'.$certificate['file_path']),'certificate PDF is written to protected storage');
$beforeRepeat=(int)db()->query("SELECT COUNT(*) FROM notification WHERE recipient_user_id=2 AND notification_type='Certificate issued'")->fetchColumn();
$repeat=mark_attendance(1,1,'Present');
$afterRepeat=(int)db()->query("SELECT COUNT(*) FROM notification WHERE recipient_user_id=2 AND notification_type='Certificate issued'")->fetchColumn();
expect((int)$repeat['certificate']['certificate_id']===$certificateId&&$afterRepeat===$beforeRepeat,'repeated roster clicks remain idempotent without duplicate certificates or notifications');

$absent=mark_attendance(1,1,'Absent');
$stmt=db()->prepare('SELECT status FROM certificate WHERE certificate_id=?');$stmt->execute([$certificateId]);
expect($stmt->fetchColumn()==='Revoked','changing attendance to absent revokes the certificate');

$announcement=save_announcement(['club_id'=>1,'message'=>'One fan-out only for this simplified announcement.']);
expect($announcement['recipient_count']===2,'active club announcement targets approved active members');
$title=(string)db()->query('SELECT title FROM announcement WHERE announcement_id='.(int)$announcement['announcement_id'])->fetchColumn();
expect($title==='One fan-out only for this simplified announcement','announcement title is generated from the message');
$edited=save_announcement(['announcement_id'=>$announcement['announcement_id'],'club_id'=>1,'message'=>'Still one fan-out only after editing.']);
expect($edited['recipient_count']===0,'editing an activated announcement does not duplicate notifications');

expect((int)db()->query('SELECT COUNT(*) FROM event_registration WHERE qr_token IS NOT NULL')->fetchColumn()===0,'fresh registrations do not use QR tokens');
$wrongEvent=false;try{mark_attendance(1,2,'Present');}catch(RuntimeException){$wrongEvent=true;}
expect($wrongEvent,'a registration from another event cannot be marked');
$cancelled=false;db()->exec("UPDATE event_registration SET registration_status='Cancelled' WHERE registration_id=1");try{mark_attendance(1,1,'Present');}catch(RuntimeException){$cancelled=true;}db()->exec("UPDATE event_registration SET registration_status='Absent' WHERE registration_id=1");
expect($cancelled,'cancelled registrations cannot be marked');

$_SESSION['user']=['user_id'=>3,'full_name'=>'Sadia Islam','role'=>'Student'];
$blocked=false;try{mark_attendance(1,1,'Present');}catch(DomainException){$blocked=true;}
expect($blocked,'an executive from another club cannot manage attendance');

if (isset($certificate['file_path'])) {
    $generated=realpath(dirname(__DIR__).'/'.$certificate['file_path']);
    $uploadsRoot=realpath(dirname(__DIR__).'/uploads');
    if($generated&&$uploadsRoot&&str_starts_with($generated,$uploadsRoot)) unlink($generated);
}
db()->exec('DROP DATABASE campus_club_hub_test');
echo "CORE EXPANSION SMOKE TEST COMPLETE\n";
