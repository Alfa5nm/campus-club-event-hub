<?php
require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
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
        redirect('diha/events.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($action === 'register') {
        try {
            register_for_event($currentUserId, $eventId);
            flash('success', 'Your place is confirmed. The event is now on your dashboard.');
        } catch (Throwable $exception) {
            flash('error', $exception instanceof RuntimeException ? $exception->getMessage() : 'Registration could not be completed.');
        }
        redirect('diha/events.php');
    }
    if ($action === 'cancel_registration') {
        try {
            cancel_event_registration($currentUserId, $eventId);
            flash('success', 'Your registration was cancelled.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('diha/events.php');
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
        redirect('diha/events.php');
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
            redirect('diha/events.php');
        }
    }
}

$manageable = managed_clubs();
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
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">Live campus calendar</span>
            <h2>Don’t just scroll.<br><span class="accent-script">Show up.</span></h2>
            <p>Search, filter, and claim your place without leaving the page.</p>
        </div>
        <?php if ($manageable): ?>
            <a class="button button-primary" href="<?= e(app_url('diha/events.php')) ?>?new=1">＋ Create event</a>
        <?php endif; ?>
    </div>

    <div class="filter-bar" data-filter-scope="events">
        <label class="search-field">
            <span>⌕</span>
            <input type="search" data-live-search placeholder="Search event, club, or venue…">
        </label>
        <div class="filter-chips">
            <button class="filter-chip active" data-filter="all">All</button>
            <?php foreach ($categories as $category): ?>
                <button class="filter-chip" data-filter="<?= e(strtolower($category)) ?>">
                    <?= e($category) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <span class="result-count"><strong data-result-count><?= count($events) ?></strong> events</span>
        <div class="view-toggle" aria-label="Event view">
            <button class="active" data-view="grid" title="Grid view" aria-label="Grid view">▦</button>
            <button data-view="list" title="List view" aria-label="List view">☷</button>
        </div>
    </div>

    <?php if ((isset($_GET['new']) && $manageable) || $edit): ?>
        <form class="card editor-card" method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="event_id" value="<?= e((string) ($edit['event_id'] ?? '')) ?>">
            <h3><?= $edit ? 'Edit event' : 'Create an event' ?></h3>
            <p class="muted">Only the essentials. New events publish immediately with sensible defaults.</p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <div class="field-grid">
                <div class="field">
                    <label>Club</label>
                    <select name="club_id" required>
                        <?php foreach ($manageable as $club): ?>
                            <option value="<?= $club['club_id'] ?>" <?= (int) ($edit['club_id'] ?? 0) === (int) $club['club_id'] ? 'selected' : '' ?>>
                                <?= e($club['club_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Event title</label>
                    <input name="title" required maxlength="180" value="<?= e($edit['title'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Event date</label>
                    <input name="event_date" type="date" required value="<?= e($edit['event_date'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Venue</label>
                    <input name="venue" required maxlength="180" value="<?= e($edit['venue'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Maximum participants</label>
                    <input name="maximum_participants" type="number" min="1" required value="<?= e((string) ($edit['maximum_participants'] ?? 50)) ?>">
                </div>
            </div>
            <button class="button button-primary"><?= $edit ? 'Save changes' : 'Create & publish' ?></button>
            <a class="button button-quiet" href="<?= e(app_url('diha/events.php')) ?>">Cancel</a>
        </form>
    <?php endif; ?>

    <div class="grid dynamic-grid" data-filter-grid data-event-view>
        <?php foreach ($events as $index => $event): ?>
            <?php
            $percent = min(
                100,
                round(((int) $event['registration_count'] / (int) $event['maximum_participants']) * 100)
            );
            $registrationStatus = $registered[(int) $event['event_id']] ?? null;
            ?>
            <article
                class="card event-card reveal"
                data-filter-item
                data-category="<?= e(strtolower($event['event_category'])) ?>"
                data-search="<?= e(strtolower($event['title'] . ' ' . $event['club_name'] . ' ' . $event['venue'] . ' ' . $event['event_category'])) ?>"
            >
                <div class="event-cover">
                    <img src="<?= e(media_url($event['poster'] ?: $eventImages[$index % count($eventImages)])) ?>" alt="">
                    <span class="cover-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="card-content">
                    <div class="event-card-head">
                        <div class="event-date-block">
                            <strong><?= e(date('d', strtotime($event['event_date']))) ?></strong>
                            <span><?= e(strtoupper(date('M', strtotime($event['event_date'])))) ?></span>
                        </div>
                        <span class="badge <?= $event['status'] === 'Cancelled' ? 'badge-danger' : '' ?>">
                            <?= e($event['status']) ?>
                        </span>
                    </div>
                    <span class="card-tag"><?= e($event['event_category']) ?></span>
                    <h3><?= e($event['title']) ?></h3>
                    <p class="muted clamp"><?= e($event['description']) ?></p>
                    <div class="detail-list">
                        <span>⌁ <?= e($event['venue']) ?></span>
                        <span>◷ <?= e(substr((string) $event['start_time'], 0, 5)) ?> · <?= e($event['club_name']) ?></span>
                    </div>
                    <div class="capacity"><span style="width: <?= $percent ?>%"></span></div>
                    <small class="muted">
                        <span data-registration-count><?= (int) $event['registration_count'] ?></span>
                        / <?= (int) $event['maximum_participants'] ?> places claimed
                    </small>
                    <div class="card-footer">
                        <?php if (user() && !is_admin() && !can_manage_club((int) $event['club_id'])): ?>
                            <form method="post" data-ajax="<?= e(app_url('rifat/api/event-registration.php')) ?>">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                <?php if ($registrationStatus === 'Registered'): ?>
                                    <button class="button button-quiet" name="action" value="cancel_registration" data-confirm="Cancel your registration?">
                                        Registered ✓ · Cancel
                                    </button>
                                <?php elseif ($event['status'] === 'Upcoming'): ?>
                                    <button class="button button-primary" name="action" value="register">Claim my place</button>
                                <?php endif; ?>
                            </form>
                        <?php elseif (!user()): ?>
                            <a class="button button-primary" href="<?= e(app_url('mixed/login.php')) ?>">Sign in to register</a>
                        <?php endif; ?>

                        <?php if (user() && can_manage_club((int) $event['club_id'])): ?>
                            <div class="actions">
                                <a class="button button-quiet" href="<?= e(app_url('diha/events.php')) ?>?edit=<?= $event['event_id'] ?>">Edit</a>
                                <?php if ($event['status'] !== 'Cancelled'): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                        <button class="button button-danger" name="action" value="cancel" data-confirm="Cancel this event?">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="empty filter-empty" hidden>No events match those filters. Try another category or search.</div>
<?php if ($manageable): ?>
    <section class="management-rail">
        <div class="section-head"><div><span class="eyebrow">MANAGEMENT TOOLS</span><h3>Rosters and event media</h3></div></div>
        <div class="agenda">
            <?php foreach ($events as $managedEvent): ?>
                <?php if (can_manage_club((int) $managedEvent['club_id'])): ?>
                    <article>
                        <div><strong><?= e($managedEvent['title']) ?></strong><small><?= e($managedEvent['club_name']) ?></small></div>
                        <div class="actions"><a class="button button-quiet" href="<?= e(app_url('rifat/roster.php')) ?>?event_id=<?= $managedEvent['event_id'] ?>">Roster</a><a class="button button-quiet" href="<?= e(app_url('diha/event-media.php')) ?>?event_id=<?= $managedEvent['event_id'] ?>">Poster</a></div>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
