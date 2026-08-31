<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$resetLink = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $resetLink = create_reset_link($_POST['email'] ?? '');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Reset password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page">
    <div class="auth-visual">
        <img src="<?= e(app_url('mixed/assets/images/campus-study.jpg')) ?>" alt="Students working together">
        <div class="auth-caption"><span>OFFLINE RECOVERY</span><h1>Find your<br>way back.</h1></div>
    </div>
    <div class="form-shell">
        <span class="eyebrow">Password recovery</span>
        <h2>Create a local reset link</h2>
        <p class="muted">For this offline XAMPP project, the time-limited link appears here instead of being emailed.</p>

        <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

        <?php if ($resetLink): ?>
            <div class="alert alert-success">
                <strong>Reset link created.</strong><br>
                <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="field">
                <label for="email">Account email</label>
                <input id="email" name="email" type="email" required>
            </div>
            <button class="button button-primary">Generate reset link</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
