<?php
require_once __DIR__.'/_json.php';$action=$_POST['action']??'';
try{
    if($action==='save'){$data=save_announcement($_POST);json_response(true,'Announcement saved.',$data);}
    if($action==='remove'){$id=(int)($_POST['announcement_id']??0);$s=db()->prepare('SELECT club_id FROM announcement WHERE announcement_id=?');$s->execute([$id]);$club=$s->fetchColumn();if($club===false)json_response(false,'Announcement not found.',[],404);if($club===null&&!is_admin())json_response(false,'Not authorized.',[],403);if($club!==null&&!can_manage_club((int)$club))json_response(false,'Not authorized.',[],403);db()->prepare("UPDATE announcement SET status='Removed' WHERE announcement_id=?")->execute([$id]);json_response(true,'Announcement removed.',['announcement_id'=>$id,'status'=>'Removed']);}
    json_response(false,'Unknown announcement action.',[],400);
}catch(DomainException $e){json_response(false,$e->getMessage(),[],403);}catch(InvalidArgumentException $e){json_response(false,$e->getMessage(),[],422);}catch(RuntimeException $e){json_response(false,$e->getMessage(),[],404);}catch(Throwable $e){json_response(false,'Announcement could not be saved.',[],500);}
