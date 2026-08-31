<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';

$clubId = (int) ($_GET['club_id'] ?? $_POST['club_id'] ?? 0);
$statement = db()->prepare('SELECT * FROM clubs WHERE club_id = ?');
$statement->execute([$clubId]);
$club = $statement->fetch();

if (!$club) {
    http_response_code(404);
    exit('Club not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();

    if (!can_manage_club($clubId)) {
        http_response_code(403);
        exit('You cannot manage this gallery.');
    }

    try {
        $action = $_POST['action'] ?? 'add';

        if ($action === 'remove') {
            db()->prepare(
                "UPDATE club_gallery SET status = 'Removed' WHERE photo_id = ? AND club_id = ?"
            )->execute([(int) ($_POST['photo_id'] ?? 0), $clubId]);
            flash('success', 'Gallery photo removed.');
        } elseif ($action === 'caption') {
            db()->prepare(
                'UPDATE club_gallery SET caption = ? WHERE photo_id = ? AND club_id = ?'
            )->execute([
                trim($_POST['caption'] ?? '') ?: null,
                (int) ($_POST['photo_id'] ?? 0),
                $clubId,
            ]);
            flash('success', 'Gallery caption updated.');
        } else {
            $path = upload_image($_FILES['photo'] ?? [], 'gallery');

            if (!$path) {
                throw new InvalidArgumentException('Choose a gallery image.');
            }

            db()->prepare(
                "INSERT INTO club_gallery (club_id, photo_path, caption, status)
                 VALUES (?, ?, ?, 'Active')"
            )->execute([$clubId, $path, trim($_POST['caption'] ?? '') ?: null]);
            flash('success', 'Gallery photo added.');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('diha/gallery.php?club_id=' . $clubId);
}

$statement = db()->prepare(
    "SELECT * FROM club_gallery WHERE club_id = ? AND status = 'Active' ORDER BY uploaded_at DESC"
);
$statement->execute([$clubId]);
$photos = $statement->fetchAll();

$pageTitle = $club['club_name'] . ' gallery';
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div><span class="eyebrow">CLUB GALLERY</span><h2><?= e($club['club_name']) ?></h2><p>Moments and activities from the club community.</p></div>
        <a class="button button-quiet" href="<?= e(app_url('diha/clubs.php')) ?>">Back to clubs</a>
    </div>

    <?php if (user() && can_manage_club($clubId)): ?>
        <form class="card editor-card" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="club_id" value="<?= $clubId ?>">
            <div class="field-grid">
                <div class="field"><label>Photo</label><input name="photo" type="file" accept="image/jpeg,image/png,image/webp" required></div>
                <div class="field"><label>Caption</label><input name="caption" maxlength="255"></div>
            </div>
            <button class="button button-primary">Add photo</button>
        </form>
    <?php endif; ?>

    <div class="grid gallery-grid">
        <?php foreach ($photos as $photo): ?>
            <figure class="card gallery-item">
                <img src="<?= e(media_url($photo['photo_path'])) ?>" alt="<?= e($photo['caption'] ?? $club['club_name']) ?>">
                <figcaption><?= e($photo['caption'] ?? 'Club activity') ?></figcaption>
                <?php if (user() && can_manage_club($clubId)): ?>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="club_id" value="<?= $clubId ?>">
                        <input type="hidden" name="photo_id" value="<?= $photo['photo_id'] ?>">
                        <label class="field">Caption<input name="caption" maxlength="255" value="<?= e($photo['caption']) ?>"></label>
                        <button class="text-link" name="action" value="caption">Save caption</button>
                        <button class="text-link" name="action" value="remove" data-confirm="Remove this photo?">Remove</button>
                    </form>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
        <?php if (!$photos): ?><div class="empty">No gallery photos have been added.</div><?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
