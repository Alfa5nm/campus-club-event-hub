<?php
require_once __DIR__ . '/_json.php';
try {
    $result=mark_attendance((int)($_POST['event_id']??0),(int)($_POST['registration_id']??0),$_POST['attendance_status']??'');
    json_response(true,$result['student_name'].' marked '.$result['status'].'.',$result);
} catch(DomainException $e){json_response(false,$e->getMessage(),[],403);
} catch(InvalidArgumentException $e){json_response(false,$e->getMessage(),[],422);
} catch(RuntimeException $e){json_response(false,$e->getMessage(),[],409);
} catch(Throwable $e){json_response(false,'Attendance could not be updated.',[],500);}
