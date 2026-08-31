<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (user()) {
    redirect('faisal/dashboard.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT user_id, full_name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $account = $stmt->fetch();
    if (!$account || !password_verify($password, $account['password_hash'])) {
        $error = 'The email or password is incorrect.';
    } elseif ($account['status'] !== 'Active') {
        $error = 'This account is not active. Please contact an administrator.';
    } else {
        unset($account['password_hash']);
        session_regenerate_id(true);
        $_SESSION['user'] = $account;
        flash('success', 'Welcome back, ' . $account['full_name'] . '.');
        redirect('faisal/dashboard.php');
    }
}
$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page"><div class="auth-visual"><img src="<?= e(app_url('mixed/assets/images/campus-study.jpg')) ?>" alt="Students collaborating on campus"><div class="auth-caption"><span>MEMBER ACCESS / 2026</span><h1>Your campus<br>is waiting.</h1></div></div><div class="form-shell">
    <span class="eyebrow">Welcome back</span><h2>Sign in to CampusHub</h2>
    <p class="muted">Manage your campus life from one place.</p>
    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="button button-primary" type="submit">Sign in</button>
    </form>
    <p class="auth-note">New here? <a href="<?= e(app_url('mixed/signup.php')) ?>"><strong>Create an account</strong></a><br><a href="<?= e(app_url('mixed/forgot-password.php')) ?>">Forgot your password?</a></p>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
