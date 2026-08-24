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
$present=mark_attendance(1,'nafis.karim@g.bracu.ac.bd','Present','Manual');
expect($present['status']==='Present','authorized executive marks a registrant present');
expect(($present['certificate']['status']??'')==='Active','present attendance issues an active certificate');
$certificateId=(int)$present['certificate']['certificate_id'];
$stmt=db()->prepare('SELECT status,file_path FROM certificate WHERE certificate_id=?');$stmt->execute([$certificateId]);$certificate=$stmt->fetch();
expect($certificate&&is_file(dirname(__DIR__).'/'.$certificate['file_path']),'certificate PDF is written to protected storage');
$beforeRepeat=(int)db()->query("SELECT COUNT(*) FROM notification WHERE recipient_user_id=2 AND notification_type='Certificate issued'")->fetchColumn();
$repeat=mark_attendance(1,'nafis.karim@g.bracu.ac.bd','Present','QR');
$afterRepeat=(int)db()->query("SELECT COUNT(*) FROM notification WHERE recipient_user_id=2 AND notification_type='Certificate issued'")->fetchColumn();
expect((int)$repeat['certificate']['certificate_id']===$certificateId&&$afterRepeat===$beforeRepeat,'duplicate QR scans remain idempotent without duplicate certificates or notifications');

$absent=mark_attendance(1,'23101002','Absent','Manual');
$stmt=db()->prepare('SELECT status FROM certificate WHERE certificate_id=?');$stmt->execute([$certificateId]);
expect($stmt->fetchColumn()==='Revoked','changing attendance to absent revokes the certificate');

$announcement=save_announcement(['club_id'=>1,'title'=>'Smoke test notice','message'=>'One fan-out only.','announcement_type'=>'Club Notice','status'=>'Active','expiry_date'=>date('Y-m-d',strtotime('+1 day'))]);
expect($announcement['recipient_count']===2,'active club announcement targets approved active members');
$edited=save_announcement(['announcement_id'=>$announcement['announcement_id'],'club_id'=>1,'title'=>'Smoke test notice edited','message'=>'Still one fan-out only.','announcement_type'=>'Club Notice','status'=>'Active','expiry_date'=>date('Y-m-d',strtotime('+1 day'))]);
expect($edited['recipient_count']===0,'editing an activated announcement does not duplicate notifications');

$_SESSION['user']=['user_id'=>3,'full_name'=>'Sadia Islam','role'=>'Student'];
$blocked=false;try{mark_attendance(1,'23101002','Present','Manual');}catch(DomainException){$blocked=true;}
expect($blocked,'an executive from another club cannot manage attendance');

if (isset($certificate['file_path'])) {
    $generated=realpath(dirname(__DIR__).'/'.$certificate['file_path']);
    $uploadsRoot=realpath(dirname(__DIR__).'/uploads');
    if($generated&&$uploadsRoot&&str_starts_with($generated,$uploadsRoot)) unlink($generated);
}
db()->exec('DROP DATABASE campus_club_hub_test');
echo "CORE EXPANSION SMOKE TEST COMPLETE\n";
