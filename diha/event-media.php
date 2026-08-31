<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
require_login();

$eventId = (int) ($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$statement = db()->prepare(
    'SELECT e.event_id, e.club_id, e.title, e.poster, c.club_name
     FROM events e JOIN clubs c ON c.club_id=e.club_id WHERE e.event_id=?'
);
$statement->execute([$eventId]);
$event = $statement->fetch();

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

if (!can_manage_club((int) $event['club_id'])) {
    http_response_code(403);
    exit('You cannot manage this event.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $poster = upload_image($_FILES['poster'] ?? [], 'events');

        if (!$poster) {
            throw new InvalidArgumentException('Choose an event poster.');
        }

        db()->prepare('UPDATE events SET poster=? WHERE event_id=?')->execute([$poster, $eventId]);
        flash('success', 'Event poster updated.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('diha/event-media.php?event_id=' . $eventId);
}

$pageTitle = 'Event poster';
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell narrow-shell">
    <div class="page-head"><div><span class="eyebrow">EVENT MEDIA</span><h2><?= e($event['title']) ?></h2><p><?= e($event['club_name']) ?></p></div></div>
    <?php if ($event['poster']): ?><img class="media-preview" src="<?= e(media_url($event['poster'])) ?>" alt="Current event poster"><?php endif; ?>
    <form class="card editor-card" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="event_id" value="<?= $eventId ?>">
        <div class="field"><label>Poster image</label><input name="poster" type="file" accept="image/jpeg,image/png,image/webp" required></div>
        <button class="button button-primary">Upload poster</button> <a class="button button-quiet" href="<?= e(app_url('diha/events.php')) ?>">Back</a>
    </form>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
