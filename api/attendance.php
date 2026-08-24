<?php
require_once __DIR__ . '/_json.php';
try {
    $result=mark_attendance((int)($_POST['event_id']??0),trim($_POST['lookup']??''),$_POST['attendance_status']??'',$_POST['attendance_method']??'Manual');
    json_response(true,$result['student_name'].' marked '.$result['status'].'.',$result);
} catch(DomainException $e){json_response(false,$e->getMessage(),[],403);
} catch(InvalidArgumentException $e){json_response(false,$e->getMessage(),[],422);
} catch(RuntimeException $e){json_response(false,$e->getMessage(),[],409);
} catch(Throwable $e){json_response(false,'Attendance could not be updated.',[],500);}
