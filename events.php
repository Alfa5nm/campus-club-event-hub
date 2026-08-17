<?php
require_once __DIR__ . '/includes/bootstrap.php';
$error = '';
$edit = null;
$currentUserId = (int) (user()['user_id'] ?? 0);

if (isset($_GET['edit'])) {
    require_login();
    $stmt = db()->prepare('SELECT * FROM events WHERE event_id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
    if (!$edit || !can_manage_club((int) $edit['club_id'])) {
        flash('error', 'You cannot manage that event.');
        redirect('events.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login(); verify_csrf();
    $action = $_POST['action'] ?? '';
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($action === 'register') {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare("SELECT e.*, COUNT(er.registration_id) AS taken FROM events e LEFT JOIN event_registration er ON er.event_id=e.event_id AND er.registration_status='Registered' WHERE e.event_id=? GROUP BY e.event_id FOR UPDATE");
            $stmt->execute([$eventId]); $event = $stmt->fetch();
            $isStudent = db()->prepare('SELECT 1 FROM students WHERE user_id=?'); $isStudent->execute([$currentUserId]);
            if (!$isStudent->fetchColumn()) throw new RuntimeException('Only student accounts can register for events.');
            if (!$event || $event['status'] !== 'Upcoming') throw new RuntimeException('This event is not accepting registrations.');
            if ($event['registration_deadline'] && $event['registration_deadline'] < date('Y-m-d')) throw new RuntimeException('The registration deadline has passed.');
            if ((int)$event['taken'] >= (int)$event['maximum_participants']) throw new RuntimeException('This event has reached capacity.');
            $token = hash('sha256', $currentUserId . ':' . $eventId . ':' . random_bytes(16));
            db()->prepare("INSERT INTO event_registration (student_user_id,event_id,registration_status,qr_token) VALUES (?,?,'Registered',?) ON DUPLICATE KEY UPDATE registration_status='Registered',qr_token=VALUES(qr_token),cancellation_reason=NULL,updated_at=CURRENT_TIMESTAMP")->execute([$currentUserId,$eventId,$token]);
            db()->commit(); flash('success', 'Your place is confirmed. The event is now on your dashboard.');
        } catch (Throwable $exception) {
            if (db()->inTransaction()) db()->rollBack();
            flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Registration could not be completed.');
        }
        redirect('events.php');
    }
    if ($action === 'cancel_registration') {
        db()->prepare("UPDATE event_registration SET registration_status='Cancelled',cancellation_reason='Cancelled by student' WHERE event_id=? AND student_user_id=? AND registration_status='Registered'")->execute([$eventId,$currentUserId]);
        flash('success','Your registration was cancelled.'); redirect('events.php');
    }
    if (in_array($action,['delete','cancel'],true)) {
        $stmt=db()->prepare('SELECT club_id FROM events WHERE event_id=?');$stmt->execute([$eventId]);$clubId=(int)$stmt->fetchColumn();
        if(!$clubId||!can_manage_club($clubId)){http_response_code(403);exit('Not authorized.');}
        if($action==='delete'){db()->prepare('DELETE FROM events WHERE event_id=?')->execute([$eventId]);flash('success','Event deleted.');}
        else{db()->prepare("UPDATE events SET status='Cancelled' WHERE event_id=?")->execute([$eventId]);flash('success','Event cancelled.');}
        redirect('events.php');
    }
    if ($action === 'save') {
        $clubId=(int)($_POST['club_id']??0); if(!can_manage_club($clubId)){http_response_code(403);exit('Not authorized.');}
        $title=trim($_POST['title']??'');$date=$_POST['event_date']??'';$deadline=$_POST['registration_deadline']??'';$capacity=(int)($_POST['maximum_participants']??0);
        if($title===''||!$date||$capacity<1||($deadline&&$deadline>$date)){$error='Enter a title and valid dates. Capacity must be greater than zero, and the deadline cannot follow the event date.';}
        else{$values=[$clubId,$currentUserId,$title,trim($_POST['description']??''),trim($_POST['event_category']??''),$date,$_POST['start_time']??null,$_POST['end_time']??null,trim($_POST['venue']??''),$capacity,$deadline?:null,$_POST['status']??'Draft'];
            if($eventId){$stmt=db()->prepare('SELECT club_id FROM events WHERE event_id=?');$stmt->execute([$eventId]);if(!can_manage_club((int)$stmt->fetchColumn())){http_response_code(403);exit('Not authorized.');}$values[]=$eventId;db()->prepare('UPDATE events SET club_id=?,created_by_user_id=?,title=?,description=?,event_category=?,event_date=?,start_time=?,end_time=?,venue=?,maximum_participants=?,registration_deadline=?,status=? WHERE event_id=?')->execute($values);flash('success','Event updated.');}
            else{db()->prepare('INSERT INTO events (club_id,created_by_user_id,title,description,event_category,event_date,start_time,end_time,venue,maximum_participants,registration_deadline,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values);flash('success','Event created.');}
            redirect('events.php');
        }
    }
}

$manageable=[];
if(user()){
    if(is_admin()){$manageable=db()->query("SELECT club_id,club_name FROM clubs WHERE status='Active' ORDER BY club_name")->fetchAll();}
    else{$marks=implode(',',array_fill(0,count(executive_roles()),'?'));$stmt=db()->prepare("SELECT c.club_id,c.club_name FROM club_membership cm JOIN clubs c ON c.club_id=cm.club_id WHERE cm.student_user_id=? AND cm.approval_status='Approved' AND cm.membership_status='Active' AND cm.member_role IN ($marks)");$stmt->execute(array_merge([$currentUserId],executive_roles()));$manageable=$stmt->fetchAll();}
}
$registered=[];
if($currentUserId){$stmt=db()->prepare('SELECT event_id,registration_status FROM event_registration WHERE student_user_id=?');$stmt->execute([$currentUserId]);foreach($stmt->fetchAll() as $row)$registered[(int)$row['event_id']]=$row['registration_status'];}
$events=db()->query("SELECT e.*,c.club_name,COUNT(CASE WHEN er.registration_status='Registered' THEN 1 END) registration_count FROM events e JOIN clubs c ON c.club_id=e.club_id LEFT JOIN event_registration er ON er.event_id=e.event_id GROUP BY e.event_id ORDER BY FIELD(e.status,'Upcoming','Ongoing','Draft','Completed','Cancelled'),e.event_date,e.start_time")->fetchAll();
$categories=array_values(array_unique(array_column($events,'event_category')));
$pageTitle='Events';require __DIR__.'/includes/header.php';
?>
<section class="page-shell">
<div class="page-head"><div><span class="eyebrow">Live campus calendar</span><h2>Don’t just scroll.<br><span class="accent-script">Show up.</span></h2><p>Search, filter, and claim your place without leaving the page.</p></div><?php if($manageable):?><a class="button button-primary" href="events.php?new=1">＋ Create event</a><?php endif;?></div>
<div class="filter-bar" data-filter-scope="events"><label class="search-field"><span>⌕</span><input type="search" data-live-search placeholder="Search event, club, or venue…"></label><div class="filter-chips"><button class="filter-chip active" data-filter="all">All</button><?php foreach($categories as $category):?><button class="filter-chip" data-filter="<?=e(strtolower($category))?>"><?=e($category)?></button><?php endforeach;?></div><span class="result-count"><strong data-result-count><?=count($events)?></strong> events</span></div>
<?php if($error):?><div class="form-error"><?=e($error)?></div><?php endif;?>
<?php if((isset($_GET['new'])&&$manageable)||$edit):?><form class="card editor-card" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="event_id" value="<?=e((string)($edit['event_id']??''))?>"><h3><?=$edit?'Edit event':'Create an event'?></h3><div class="field-grid"><div class="field"><label>Club</label><select name="club_id" required><?php foreach($manageable as $c):?><option value="<?=$c['club_id']?>" <?=(int)($edit['club_id']??0)===(int)$c['club_id']?'selected':''?>><?=e($c['club_name'])?></option><?php endforeach;?></select></div><div class="field"><label>Title</label><input name="title" required value="<?=e($edit['title']??'')?>"></div><div class="field"><label>Category</label><input name="event_category" required value="<?=e($edit['event_category']??'')?>"></div><div class="field"><label>Venue</label><input name="venue" required value="<?=e($edit['venue']??'')?>"></div><div class="field"><label>Event date</label><input name="event_date" type="date" required value="<?=e($edit['event_date']??'')?>"></div><div class="field"><label>Registration deadline</label><input name="registration_deadline" type="date" value="<?=e($edit['registration_deadline']??'')?>"></div><div class="field"><label>Start time</label><input name="start_time" type="time" value="<?=e($edit['start_time']??'')?>"></div><div class="field"><label>End time</label><input name="end_time" type="time" value="<?=e($edit['end_time']??'')?>"></div><div class="field"><label>Maximum participants</label><input name="maximum_participants" type="number" min="1" required value="<?=e((string)($edit['maximum_participants']??50))?>"></div><div class="field"><label>Status</label><select name="status"><?php foreach(['Draft','Upcoming','Ongoing','Completed','Cancelled'] as $st):?><option <?=$st===($edit['status']??'Draft')?'selected':''?>><?=e($st)?></option><?php endforeach;?></select></div></div><div class="field"><label>Description</label><textarea name="description" required><?=e($edit['description']??'')?></textarea></div><button class="button button-primary">Save event</button> <a class="button button-quiet" href="events.php">Cancel</a></form><?php endif;?>
<div class="grid dynamic-grid" data-filter-grid><?php foreach($events as $event):$percent=min(100,round(((int)$event['registration_count']/(int)$event['maximum_participants'])*100));$reg=$registered[(int)$event['event_id']]??null;?><article class="card event-card reveal" data-filter-item data-category="<?=e(strtolower($event['event_category']))?>" data-search="<?=e(strtolower($event['title'].' '.$event['club_name'].' '.$event['venue'].' '.$event['event_category']))?>"><div class="event-card-head"><div class="event-date-block"><strong><?=e(date('d',strtotime($event['event_date'])))?></strong><span><?=e(strtoupper(date('M',strtotime($event['event_date']))))?></span></div><span class="badge <?=$event['status']==='Cancelled'?'badge-danger':''?>"><?=e($event['status'])?></span></div><span class="card-tag"><?=e($event['event_category'])?></span><h3><?=e($event['title'])?></h3><p class="muted clamp"><?=e($event['description'])?></p><div class="detail-list"><span>⌁ <?=e($event['venue'])?></span><span>◷ <?=e(substr((string)$event['start_time'],0,5))?> · <?=e($event['club_name'])?></span></div><div class="capacity"><span style="width:<?=$percent?>%"></span></div><small class="muted"><?=(int)$event['registration_count']?> / <?=(int)$event['maximum_participants']?> places claimed</small><div class="card-footer"><?php if(user()&&!is_admin()&&!can_manage_club((int)$event['club_id'])):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="event_id" value="<?=$event['event_id']?>"><?php if($reg==='Registered'):?><button class="button button-quiet" name="action" value="cancel_registration" data-confirm="Cancel your registration?">Registered ✓ · Cancel</button><?php elseif($event['status']==='Upcoming'):?><button class="button button-primary" name="action" value="register">Claim my place</button><?php endif;?></form><?php elseif(!user()):?><a class="button button-primary" href="login.php">Sign in to register</a><?php endif;?><?php if(user()&&can_manage_club((int)$event['club_id'])):?><div class="actions"><a class="button button-quiet" href="events.php?edit=<?=$event['event_id']?>">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="event_id" value="<?=$event['event_id']?>"><?php if($event['status']!=='Cancelled'):?><button class="button button-danger" name="action" value="cancel" data-confirm="Cancel this event?">Cancel</button><?php endif;?><button class="button button-danger" name="action" value="delete" data-confirm="Permanently delete this event?">Delete</button></form></div><?php endif;?></div></article><?php endforeach;?></div><div class="empty filter-empty" hidden>No events match those filters. Try another category or search.</div>
</section>
<?php require __DIR__.'/includes/footer.php';?>
