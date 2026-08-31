<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';

$error = '';
$editClub = null;

if (isset($_GET['edit'])) {
    require_login();
    $clubId = (int) $_GET['edit'];

    if (!can_manage_club($clubId)) {
        flash('error', 'You are not authorized to manage that club.');
        redirect('diha/clubs.php');
    }

    $statement = db()->prepare('SELECT * FROM clubs WHERE club_id = ?');
    $statement->execute([$clubId]);
    $editClub = $statement->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'request_join') {
        try {
            db()->prepare(
                "INSERT INTO club_membership
                    (student_user_id, club_id, member_role, approval_status, membership_status)
                 VALUES (?, ?, 'Member', 'Pending', 'Active')"
            )->execute([current_user_id(), (int) ($_POST['club_id'] ?? 0)]);
            flash('success', 'Your membership request was sent.');
        } catch (PDOException $exception) {
            flash('error', 'You already have a membership or pending request for this club.');
        }

        redirect('diha/clubs.php');
    }

    if ($action === 'save') {
        $clubId = (int) ($_POST['club_id'] ?? 0);

        if (($clubId && !can_manage_club($clubId)) || (!$clubId && !is_admin())) {
            http_response_code(403);
            exit('Not authorized.');
        }

        $name = trim($_POST['club_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $contact = trim($_POST['contact_information'] ?? '');
        $status = $_POST['status'] ?? 'Pending';

        if ($name === '' || $description === '' || $category === '') {
            $error = 'Club name, description, and category are required.';
        } else {
            try {
                $logo = upload_image($_FILES['logo'] ?? [], 'clubs');
                $sql = $clubId
                    ? 'UPDATE clubs SET club_name=?, description=?, category=?, contact_information=?, status=?'
                    : 'INSERT INTO clubs (club_name, description, category, contact_information, status, logo) VALUES (?, ?, ?, ?, ?, ?)';
                $parameters = [$name, $description, $category, $contact ?: null, $status];

                if ($clubId) {
                    if ($logo) {
                        $sql .= ', logo=?';
                        $parameters[] = $logo;
                    }

                    $sql .= ' WHERE club_id=?';
                    $parameters[] = $clubId;
                } else {
                    $parameters[] = $logo;
                }

                db()->prepare($sql)->execute($parameters);
                flash('success', $clubId ? 'Club profile updated.' : 'Club created.');
                redirect('diha/clubs.php');
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}

$clubs = db()->query(
    "SELECT c.*,
        COUNT(DISTINCT CASE WHEN cm.approval_status='Approved' AND cm.membership_status='Active' THEN cm.membership_id END) AS member_count,
        COUNT(DISTINCT e.event_id) AS event_count
     FROM clubs c
     LEFT JOIN club_membership cm ON cm.club_id=c.club_id
     LEFT JOIN events e ON e.club_id=c.club_id
     GROUP BY c.club_id
     ORDER BY FIELD(c.status,'Active','Pending','Suspended'), c.club_name"
)->fetchAll();
$categories = array_values(array_unique(array_column($clubs, 'category')));
$fallbackImages = ['assets/images/club-collaboration.jpg', 'assets/images/campus-study.jpg', 'assets/images/campus-walk.jpg'];
$membershipClubIds = [];

if (user()) {
    $statement = db()->prepare('SELECT club_id FROM club_membership WHERE student_user_id=?');
    $statement->execute([current_user_id()]);
    $membershipClubIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

$pageTitle = 'Clubs';
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div><span class="eyebrow">Campus communities</span><h2>Find your people.<br><span class="accent-script">Build something real.</span></h2><p>Explore organizations or manage the clubs assigned to you.</p></div>
        <?php if (is_admin()): ?><a class="button button-primary" href="<?= e(app_url('diha/clubs.php')) ?>?new=1">＋ Create club</a><?php endif; ?>
    </div>

    <div class="filter-bar" data-filter-scope="clubs">
        <label class="search-field"><span>⌕</span><input type="search" data-live-search placeholder="Search clubs or interests…"></label>
        <div class="filter-chips"><button class="filter-chip active" data-filter="all">All</button><?php foreach ($categories as $category): ?><button class="filter-chip" data-filter="<?= e(strtolower($category)) ?>"><?= e($category) ?></button><?php endforeach; ?></div>
        <span class="result-count"><strong data-result-count><?= count($clubs) ?></strong> clubs</span>
    </div>

    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ((isset($_GET['new']) && is_admin()) || $editClub): ?>
        <form class="card editor-card" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="club_id" value="<?= e((string) ($editClub['club_id'] ?? '')) ?>">
            <h3><?= $editClub ? 'Edit club profile' : 'Create a club' ?></h3>
            <div class="field-grid">
                <div class="field"><label>Club name</label><input name="club_name" required value="<?= e($editClub['club_name'] ?? '') ?>"></div>
                <div class="field"><label>Category</label><input name="category" required value="<?= e($editClub['category'] ?? '') ?>"></div>
                <div class="field"><label>Contact information</label><input name="contact_information" value="<?= e($editClub['contact_information'] ?? '') ?>"></div>
                <div class="field"><label>Status</label><select name="status"><?php foreach (['Pending', 'Active', 'Suspended'] as $status): ?><option <?= $status === ($editClub['status'] ?? 'Pending') ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>Club logo</label><input name="logo" type="file" accept="image/jpeg,image/png,image/webp"></div>
            </div>
            <div class="field"><label>Description</label><textarea name="description" required><?= e($editClub['description'] ?? '') ?></textarea></div>
            <button class="button button-primary">Save club</button> <a class="button button-quiet" href="<?= e(app_url('diha/clubs.php')) ?>">Cancel</a>
        </form>
    <?php endif; ?>

    <div class="grid dynamic-grid" data-filter-grid>
        <?php foreach ($clubs as $index => $club): ?>
            <article class="card club-card reveal" data-filter-item data-category="<?= e(strtolower($club['category'])) ?>" data-search="<?= e(strtolower($club['club_name'].' '.$club['description'].' '.$club['category'])) ?>">
                <div class="club-cover"><img src="<?= e(media_url($club['logo'] ?: $fallbackImages[$index % count($fallbackImages)])) ?>" alt="<?= e($club['club_name']) ?>"><span class="cover-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span></div>
                <div class="card-content">
                    <span class="card-tag"><?= e($club['category']) ?></span><h3><?= e($club['club_name']) ?></h3><p class="muted clamp"><?= e($club['description']) ?></p>
                    <div class="club-metrics"><span><strong><?= (int) $club['member_count'] ?></strong> members</span><span><strong><?= (int) $club['event_count'] ?></strong> events</span></div>
                    <div class="card-footer"><span class="badge" data-membership-state><?= e($club['status']) ?></span><div class="actions">
                        <a class="button button-quiet" href="<?= e(app_url('diha/gallery.php')) ?>?club_id=<?= $club['club_id'] ?>">Gallery</a>
                        <?php if (user() && !in_array((int) $club['club_id'], $membershipClubIds, true) && $club['status'] === 'Active'): ?><form method="post" data-ajax="<?= e(app_url('diha/api/membership.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="request_join"><input type="hidden" name="club_id" value="<?= $club['club_id'] ?>"><button class="button button-secondary">Request to join</button></form><?php endif; ?>
                        <?php if (user() && can_manage_club((int) $club['club_id'])): ?><a class="button button-quiet" href="<?= e(app_url('diha/clubs.php')) ?>?edit=<?= $club['club_id'] ?>">Edit</a><?php endif; ?>
                    </div></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="empty filter-empty" hidden>No clubs match those filters.</div>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
