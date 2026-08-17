<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $studentId = trim($_POST['student_id'] ?? ''); $department = trim($_POST['department'] ?? '');
    $year = trim($_POST['academic_year'] ?? ''); $password = $_POST['password'] ?? '';
    $emailDomainValid = filter_var($email, FILTER_VALIDATE_EMAIL) && str_ends_with($email, '@g.bracu.ac.bd');
    if ($name === '' || !$emailDomainValid || $studentId === '' || $department === '') {
        $error = !$emailDomainValid
            ? 'Registration requires a valid @g.bracu.ac.bd email address.'
            : 'Please complete all required fields with valid information.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } else {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare("INSERT INTO users (full_name,email,password_hash,role,status) VALUES (?,?,?,'Student','Active')");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int) db()->lastInsertId();
            db()->prepare('INSERT INTO students (user_id,student_number,department,academic_year) VALUES (?,?,?,?)')->execute([$userId,$studentId,$department,$year ?: null]);
            $interests = array_filter(array_map('trim', explode(',', $_POST['interests'] ?? '')));
            $interestStmt = db()->prepare('INSERT IGNORE INTO student_interest (student_user_id, interest) VALUES (?,?)');
            foreach ($interests as $interest) $interestStmt->execute([$userId, $interest]);
            db()->commit();
            flash('success', 'Account created. You can now sign in.'); redirect('login.php');
        } catch (PDOException $exception) {
            if (db()->inTransaction()) db()->rollBack();
            $error = $exception->getCode() === '23000' ? 'That email or student ID is already registered.' : 'We could not create the account. Please try again.';
        }
    }
}
$pageTitle = 'Create account'; require __DIR__ . '/includes/header.php';
?>
<section class="auth-page"><div class="auth-visual"><img src="assets/images/campus-walk.jpg" alt="Students walking through campus"><div class="auth-caption"><span>JOIN THE DIRECTORY</span><h1>Start with<br>hello.</h1></div></div><div class="form-shell wide">
    <span class="eyebrow">Start exploring</span><h2>Create your student account</h2>
    <p class="muted">Use your campus details. Interests can be updated later.</p>
    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="field-grid">
            <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
            <div class="field"><label for="email">BRAC University email</label><input id="email" name="email" type="email" inputmode="email" pattern="[A-Za-z0-9._%+\-]+@g\.bracu\.ac\.bd" placeholder="name@g.bracu.ac.bd" title="Use your @g.bracu.ac.bd email address" required value="<?= e($_POST['email'] ?? '') ?>"><small class="muted">Only @g.bracu.ac.bd accounts can register.</small></div>
            <div class="field"><label for="student_id">Student ID</label><input id="student_id" name="student_id" required value="<?= e($_POST['student_id'] ?? '') ?>"></div>
            <div class="field"><label for="department">Department</label><input id="department" name="department" required value="<?= e($_POST['department'] ?? '') ?>"></div>
            <div class="field"><label for="academic_year">Academic year</label><input id="academic_year" name="academic_year" placeholder="e.g. 2025–26" value="<?= e($_POST['academic_year'] ?? '') ?>"></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" minlength="8" required></div>
        </div>
        <div class="field"><label for="interests">Interests</label><input id="interests" name="interests" placeholder="technology, music, volunteering" value="<?= e($_POST['interests'] ?? '') ?>"><small class="muted">Separate multiple interests with commas.</small></div>
        <button class="button button-primary" type="submit">Create account</button>
    </form>
    <p class="auth-note">Already registered? <a href="login.php"><strong>Sign in</strong></a></p>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
