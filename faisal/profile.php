<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
require_login();

$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        if (($_POST['action'] ?? '') === 'password') {
            change_password(
                $userId,
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? ''
            );
            flash('success', 'Your password was updated.');
        } else {
            save_profile($userId, $_POST, $_FILES['profile_picture'] ?? null);
            flash('success', 'Your profile was updated.');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('faisal/profile.php');
}

$statement = db()->prepare(
    'SELECT u.*, s.student_number, s.department, s.academic_year
     FROM users u
     LEFT JOIN students s ON s.user_id = u.user_id
     WHERE u.user_id = ?'
);
$statement->execute([$userId]);
$profile = $statement->fetch();

$statement = db()->prepare(
    'SELECT interest FROM student_interest WHERE student_user_id = ? ORDER BY interest'
);
$statement->execute([$userId]);
$interests = implode(', ', $statement->fetchAll(PDO::FETCH_COLUMN));

$pageTitle = 'My profile';
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">ACCOUNT SETTINGS</span>
            <h2>Your profile,<br><span class="accent-script">kept current.</span></h2>
            <p>Update the details used for memberships, recommendations, and certificates.</p>
        </div>
    </div>

    <div class="dashboard-layout">
        <form class="card editor-card" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="profile">
            <h3>Profile details</h3>

            <div class="field-grid">
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" required value="<?= e($profile['full_name']) ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="<?= e($profile['phone']) ?>">
                </div>
                <?php if ($profile['student_number']): ?><div class="field">
                    <label for="department">Department</label>
                    <input id="department" name="department" required value="<?= e($profile['department']) ?>">
                </div>
                <div class="field">
                    <label for="academic_year">Academic year</label>
                    <input id="academic_year" name="academic_year" value="<?= e($profile['academic_year']) ?>">
                </div><?php endif; ?>
            </div>

            <?php if ($profile['student_number']): ?><div class="field">
                <label for="interests">Interests</label>
                <input id="interests" name="interests" value="<?= e($interests) ?>">
                <small>Separate interests with commas.</small>
            </div><?php endif; ?>

            <div class="field">
                <label for="profile_picture">Profile picture</label>
                <input id="profile_picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp">
                <small>JPEG, PNG, or WebP. Maximum 5 MB.</small>
            </div>

            <button class="button button-primary">Save profile</button>
        </form>

        <form class="card editor-card" method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="password">
            <h3>Change password</h3>

            <div class="field">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" required>
            </div>
            <div class="field">
                <label for="new_password">New password</label>
                <input id="new_password" name="new_password" type="password" minlength="8" required>
            </div>

            <button class="button button-secondary">Update password</button>
        </form>
    </div>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
