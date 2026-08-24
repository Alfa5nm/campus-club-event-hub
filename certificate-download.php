<?php
require_once __DIR__.'/includes/bootstrap.php';require_login();
$stmt=db()->prepare("SELECT cert.*,er.student_user_id,e.club_id FROM certificate cert JOIN attendance a ON a.attendance_id=cert.attendance_id JOIN event_registration er ON er.registration_id=a.registration_id JOIN events e ON e.event_id=er.event_id WHERE cert.certificate_id=?");$stmt->execute([(int)($_GET['id']??0)]);$cert=$stmt->fetch();
if($cert&&(int)$cert['student_user_id']!==(int)user()['user_id']&&!can_manage_club((int)$cert['club_id']))$cert=null;
if(!$cert||$cert['status']!=='Active'){http_response_code(404);exit('Active certificate not found.');}$path=__DIR__.'/'.$cert['file_path'];if(!is_file($path)){http_response_code(404);exit('Certificate file is unavailable.');}
header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.basename($path).'"');header('Content-Length: '.filesize($path));readfile($path);
