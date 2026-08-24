<?php
require_once __DIR__.'/_json.php';$uid=(int)user()['user_id'];$action=$_POST['action']??'';
if($action==='read'){$id=(int)($_POST['notification_id']??0);$s=db()->prepare('UPDATE notification SET is_read=1 WHERE notification_id=? AND recipient_user_id=?');$s->execute([$id,$uid]);if(!$s->rowCount())json_response(false,'Notification not found.',[],404);}
elseif($action==='read_all'){db()->prepare('UPDATE notification SET is_read=1 WHERE recipient_user_id=?')->execute([$uid]);}
else json_response(false,'Unknown notification action.',[],400);
json_response(true,'Notifications updated.',['unread_count'=>unread_notification_count(),'notification_id'=>(int)($_POST['notification_id']??0)]);
