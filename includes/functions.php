<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!user()) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function is_admin(): bool
{
    return (user()['role'] ?? '') === 'Admin';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('The form expired. Please go back, refresh the page, and try again.');
    }
}

function executive_roles(): array
{
    return ['Executive', 'President', 'Vice President', 'Secretary', 'Treasurer'];
}

function can_manage_club(int $clubId): bool
{
    if (is_admin()) {
        return true;
    }
    if (!user()) {
        return false;
    }
    $marks = implode(',', array_fill(0, count(executive_roles()), '?'));
    $sql = "SELECT 1 FROM club_membership WHERE student_user_id = ? AND club_id = ? AND approval_status = 'Approved' AND membership_status = 'Active' AND member_role IN ($marks)";
    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge([(int) user()['user_id'], $clubId], executive_roles()));
    return (bool) $stmt->fetchColumn();
}

function active_page(string $file): string
{
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}
