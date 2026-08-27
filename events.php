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
    require_login();
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($action === 'register') {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare("SELECT e.*, COUNT(er.registration_id) AS taken FROM events e LEFT JOIN event_registration er ON er.event_id=e.event_id AND er.registration_status='Registered' WHERE e.event_id=? GROUP BY e.event_id FOR UPDATE");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            $isStudent = db()->prepare('SELECT 1 FROM students WHERE user_id=?');
            $isStudent->execute([$currentUserId]);
            if (!$isStudent->fetchColumn()) {
                throw new RuntimeException('Only student accounts can register for events.');
            }
            if (!$event || $event['status'] !== 'Upcoming') {
                throw new RuntimeException('This event is not accepting registrations.');
            }
            if ($event['registration_deadline'] && $event['registration_deadline'] < date('Y-m-d')) {
                throw new RuntimeException('The registration deadline has passed.');
            }
            if ((int)$event['taken'] >= (int)$event['maximum_participants']) {
                throw new RuntimeException('This event has reached capacity.');
            }
            db()->prepare("INSERT INTO event_registration (student_user_id,event_id,registration_status,qr_token) VALUES (?,?,'Registered',NULL) ON DUPLICATE KEY UPDATE registration_status='Registered',qr_token=NULL,cancellation_reason=NULL,updated_at=CURRENT_TIMESTAMP")->execute([$currentUserId,$eventId]);
            notify_user($currentUserId, 'Registration Confirmation', 'Your registration for ' . $event['title'] . ' is confirmed.');
            db()->commit();
            flash('success', 'Your place is confirmed. The event is now on your dashboard.');
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Registration could not be completed.');
        }
        redirect('events.php');
    }
    if ($action === 'cancel_registration') {
        db()->prepare("UPDATE event_registration SET registration_status='Cancelled',cancellation_reason='Cancelled by student' WHERE event_id=? AND student_user_id=? AND registration_status='Registered'")->execute([$eventId,$currentUserId]);
        notify_user($currentUserId, 'Registration Cancellation', 'Your event registration was cancelled.');
        flash('success', 'Your registration was cancelled.');
        redirect('events.php');
    }
    if ($action === 'cancel') {
        $stmt = db()->prepare('SELECT club_id FROM events WHERE event_id=?');
        $stmt->execute([$eventId]);
        $clubId = (int)$stmt->fetchColumn();
        if (!$clubId || !can_manage_club($clubId)) {
            http_response_code(403);
            exit('Not authorized.');
        }
        db()->prepare("UPDATE events SET status='Cancelled' WHERE event_id=?")->execute([$eventId]);

        $recipients = db()->prepare(
            "SELECT student_user_id
             FROM event_registration
             WHERE event_id = ? AND registration_status <> 'Cancelled'"
        );
        $recipients->execute([$eventId]);

        foreach ($recipients->fetchAll(PDO::FETCH_COLUMN) as $recipientId) {
            notify_once(
                (int) $recipientId,
                'Event Cancellation',
                'An event you registered for has been cancelled.',
                'event_cancellation',
                $eventId
            );
        }

        flash('success', 'Event cancelled.');
        redirect('events.php');
    }
    if ($action === 'save') {
        $clubId = (int)($_POST['club_id'] ?? 0);
        if (!can_manage_club($clubId)) {
            http_response_code(403);
            exit('Not authorized.');
        }
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['event_date'] ?? '';
        $venue = trim($_POST['venue'] ?? '');
        $capacity = (int)($_POST['maximum_participants'] ?? 0);
        if ($title === '' || !$date || $venue === '' || $capacity < 1) {
            $error = 'Enter a title, date, venue, and capacity greater than zero.';
        } else {
            if ($eventId) {
                $stmt = db()->prepare('SELECT club_id FROM events WHERE event_id=?');
                $stmt->execute([$eventId]);
                if (!can_manage_club((int)$stmt->fetchColumn())) {
                    http_response_code(403);
                    exit('Not authorized.');
                }db()->prepare('UPDATE events SET club_id=?,created_by_user_id=?,title=?,event_date=?,venue=?,maximum_participants=? WHERE event_id=?')->execute([$clubId,$currentUserId,$title,$date,$venue,$capacity,$eventId]);
                flash('success', 'Event updated.');
            } else {
                db()->prepare("INSERT INTO events (club_id,created_by_user_id,title,description,event_category,event_date,start_time,end_time,venue,maximum_participants,registration_deadline,status) VALUES (?,?,?,'Event details will be shared soon.','General',?,'09:00:00',NULL,?,?,?,'Upcoming')")->execute([$clubId,$currentUserId,$title,$date,$venue,$capacity,$date]);
                flash('success', 'Event created and published.');
            }
            redirect('events.php');
        }
    }
}

$manageable = [];
if (user()) {
    if (is_admin()) {
        $manageable = db()->query("SELECT club_id,club_name FROM clubs WHERE status='Active' ORDER BY club_name")->fetchAll();
    } else {
        $marks = implode(',', array_fill(0, count(executive_roles()), '?'));
        $stmt = db()->prepare("SELECT c.club_id,c.club_name FROM club_membership cm JOIN clubs c ON c.club_id=cm.club_id WHERE cm.student_user_id=? AND cm.approval_status='Approved' AND cm.membership_status='Active' AND cm.member_role IN ($marks)");
        $stmt->execute(array_merge([$currentUserId], executive_roles()));
        $manageable = $stmt->fetchAll();
    }
}
$registered = [];
if ($currentUserId) {
    $stmt = db()->prepare('SELECT event_id,registration_status FROM event_registration WHERE student_user_id=?');
    $stmt->execute([$currentUserId]);
    foreach ($stmt->fetchAll() as $row) {
        $registered[(int)$row['event_id']] = $row['registration_status'];
    }
}
$events = db()->query("SELECT e.*,c.club_name,COUNT(CASE WHEN er.registration_status='Registered' THEN 1 END) registration_count FROM events e JOIN clubs c ON c.club_id=e.club_id LEFT JOIN event_registration er ON er.event_id=e.event_id GROUP BY e.event_id ORDER BY FIELD(e.status,'Upcoming','Ongoing','Draft','Completed','Cancelled'),e.event_date,e.start_time")->fetchAll();
$categories = array_values(array_unique(array_column($events, 'event_category')));
$eventImages = ['assets/images/club-collaboration.jpg','assets/images/campus-study.jpg','assets/images/campus-walk.jpg'];
$pageTitle = 'Events';
require __DIR__.'/includes/header.php';
?>
<section class="page-shell">
<div class="page-head"><div><span class="eyebrow">Live campus calendar</span><h2>Don’t just scroll.<br><span class="accent-script">Show up.</span></h2><p>Search, filter, and claim your place without leaving the page.</p></div><?php if ($manageable):?><a class="button button-primary" href="events.php?new=1">＋ Create event</a><?php endif;?></div>
<div class="filter-bar" data-filter-scope="events"><label class="search-field"><span>⌕</span><input type="search" data-live-search placeholder="Search event, club, or venue…"></label><div class="filter-chips"><button class="filter-chip active" data-filter="all">All</button><?php foreach ($categories as $category):?><button class="filter-chip" data-filter="<?=e(strtolower($category))?>"><?=e($category)?></button><?php endforeach;?></div><span class="result-count"><strong data-result-count><?=count($events)?></strong> events</span><div class="view-toggle" aria-label="Event view"><button class="active" data-view="grid" title="Grid view" aria-label="Grid view">▦</button><button data-view="list" title="List view" aria-label="List view">☷</button></div></div>
<?php if ((isset($_GET['new']) && $manageable) || $edit):?><form class="card editor-card" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="event_id" value="<?=e((string)($edit['event_id'] ?? ''))?>"><h3><?=$edit ? 'Edit event' : 'Create an event'?></h3><p class="muted">Only the essentials. New events publish immediately with sensible defaults.</p><?php if ($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?><div class="field-grid"><div class="field"><label>Club</label><select name="club_id" required><?php foreach ($manageable as $c):?><option value="<?=$c['club_id']?>" <?=(int)($edit['club_id'] ?? 0) === (int)$c['club_id'] ? 'selected' : ''?>><?=e($c['club_name'])?></option><?php endforeach;?></select></div><div class="field"><label>Event title</label><input name="title" required maxlength="180" value="<?=e($edit['title'] ?? '')?>"></div><div class="field"><label>Event date</label><input name="event_date" type="date" required value="<?=e($edit['event_date'] ?? '')?>"></div><div class="field"><label>Venue</label><input name="venue" required maxlength="180" value="<?=e($edit['venue'] ?? '')?>"></div><div class="field"><label>Maximum participants</label><input name="maximum_participants" type="number" min="1" required value="<?=e((string)($edit['maximum_participants'] ?? 50))?>"></div></div><button class="button button-primary"><?=$edit ? 'Save changes' : 'Create & publish'?></button> <a class="button button-quiet" href="events.php">Cancel</a></form><?php endif;?>
<div class="grid dynamic-grid" data-filter-grid data-event-view><?php foreach ($events as $i => $event):$percent = min(100, round(((int)$event['registration_count'] / (int)$event['maximum_participants']) * 100));
    $reg = $registered[(int)$event['event_id']] ?? null;?><article class="card event-card reveal" data-filter-item data-category="<?=e(strtolower($event['event_category']))?>" data-search="<?=e(strtolower($event['title'].' '.$event['club_name'].' '.$event['venue'].' '.$event['event_category']))?>"><div class="event-cover"><img src="<?=e($eventImages[$i % count($eventImages)])?>" alt=""><span class="cover-index">0<?=$i + 1?></span></div><div class="card-content"><div class="event-card-head"><div class="event-date-block"><strong><?=e(date('d', strtotime($event['event_date'])))?></strong><span><?=e(strtoupper(date('M', strtotime($event['event_date']))))?></span></div><span class="badge <?=$event['status'] === 'Cancelled' ? 'badge-danger' : ''?>"><?=e($event['status'])?></span></div><span class="card-tag"><?=e($event['event_category'])?></span><h3><?=e($event['title'])?></h3><p class="muted clamp"><?=e($event['description'])?></p><div class="detail-list"><span>⌁ <?=e($event['venue'])?></span><span>◷ <?=e(substr((string)$event['start_time'], 0, 5))?> · <?=e($event['club_name'])?></span></div><div class="capacity"><span style="width:<?=$percent?>%"></span></div><small class="muted"><span data-registration-count><?=(int)$event['registration_count']?></span> / <?=(int)$event['maximum_participants']?> places claimed</small><div class="card-footer"><?php if (user() && !is_admin() && !can_manage_club((int)$event['club_id'])):?><form method="post" data-ajax="api/event-registration.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="event_id" value="<?=$event['event_id']?>"><?php if ($reg === 'Registered'):?><button class="button button-quiet" name="action" value="cancel_registration" data-confirm="Cancel your registration?">Registered ✓ · Cancel</button><?php elseif ($event['status'] === 'Upcoming'):?><button class="button button-primary" name="action" value="register">Claim my place</button><?php endif;?></form><?php elseif (!user()):?><a class="button button-primary" href="login.php">Sign in to register</a><?php endif;?><?php if (user() && can_manage_club((int)$event['club_id'])):?><div class="actions"><a class="button button-quiet" href="events.php?edit=<?=$event['event_id']?>">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="event_id" value="<?=$event['event_id']?>"><?php if ($event['status'] !== 'Cancelled'):?><button class="button button-danger" name="action" value="cancel" data-confirm="Cancel this event?">Cancel</button><?php endif;?><button class="button button-danger" name="action" value="delete" data-confirm="Permanently delete this event?">Delete</button></form></div><?php endif;?></div></div></article><?php endforeach;?></div><div class="empty filter-empty" hidden>No events match those filters. Try another category or search.</div>
<?php if ($manageable): ?>
    <section class="management-rail">
        <div class="section-head"><div><span class="eyebrow">MANAGEMENT TOOLS</span><h3>Rosters and event media</h3></div></div>
        <div class="agenda">
            <?php foreach ($events as $managedEvent): ?>
                <?php if (can_manage_club((int) $managedEvent['club_id'])): ?>
                    <article>
                        <div><strong><?= e($managedEvent['title']) ?></strong><small><?= e($managedEvent['club_name']) ?></small></div>
                        <div class="actions"><a class="button button-quiet" href="roster.php?event_id=<?= $managedEvent['event_id'] ?>">Roster</a><a class="button button-quiet" href="event-media.php?event_id=<?= $managedEvent['event_id'] ?>">Poster</a></div>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
</section>
<?php require __DIR__.'/includes/footer.php';?>
