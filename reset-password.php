<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        reset_password($token, $_POST['password'] ?? '');
        flash('success', 'Password reset complete. You can now sign in.');
        redirect('login.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Choose a new password';
require __DIR__ . '/includes/header.php';
?>
<section class="page-shell narrow-shell">
    <form class="card editor-card" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <span class="eyebrow">SECURE RESET</span>
        <h2>Choose a new password</h2>
        <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <div class="field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" minlength="8" required>
        </div>
        <button class="button button-primary">Reset password</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
