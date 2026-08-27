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
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
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

    if (!csrf_is_valid($token)) {
        http_response_code(419);
        exit('The form expired. Please refresh the page and try again.');
    }
}

function stream_csv(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'wb');
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, array_values($row));
    }

    fclose($output);
    exit;
}

function csrf_is_valid(string $token): bool
{
    return $token !== '' && hash_equals($_SESSION['csrf'] ?? '', $token);
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

    $roleMarks = implode(',', array_fill(0, count(executive_roles()), '?'));
    $statement = db()->prepare(
        "SELECT 1
         FROM club_membership
         WHERE student_user_id = ?
           AND club_id = ?
           AND approval_status = 'Approved'
           AND membership_status = 'Active'
           AND member_role IN ($roleMarks)"
    );
    $statement->execute(array_merge(
        [(int) user()['user_id'], $clubId],
        executive_roles()
    ));

    return (bool) $statement->fetchColumn();
}

function active_page(string $file): string
{
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}

function managed_clubs(bool $activeOnly = true): array
{
    if (!user()) {
        return [];
    }

    if (is_admin()) {
        $statusFilter = $activeOnly ? "WHERE status = 'Active'" : '';

        return db()->query(
            "SELECT club_id, club_name FROM clubs $statusFilter ORDER BY club_name"
        )->fetchAll();
    }

    $roleMarks = implode(',', array_fill(0, count(executive_roles()), '?'));
    $statement = db()->prepare(
        "SELECT c.club_id, c.club_name
         FROM club_membership cm
         JOIN clubs c ON c.club_id = cm.club_id
         WHERE cm.student_user_id = ?
           AND cm.approval_status = 'Approved'
           AND cm.membership_status = 'Active'
           AND cm.member_role IN ($roleMarks)
           " . ($activeOnly ? "AND c.status = 'Active'" : '') . "
         ORDER BY c.club_name"
    );
    $statement->execute(array_merge([(int) user()['user_id']], executive_roles()));

    return $statement->fetchAll();
}

function notify_user(int $userId, string $type, string $message): void
{
    $statement = db()->prepare(
        'INSERT INTO notification (recipient_user_id, notification_type, message)
         VALUES (?, ?, ?)'
    );
    $statement->execute([
        $userId,
        mb_substr($type, 0, 80),
        mb_substr($message, 0, 500),
    ]);
}

function unread_notification_count(): int
{
    if (!user()) {
        return 0;
    }

    $statement = db()->prepare(
        'SELECT COUNT(*) FROM notification WHERE recipient_user_id = ? AND is_read = 0'
    );
    $statement->execute([(int) user()['user_id']]);

    return (int) $statement->fetchColumn();
}

function mark_notification_read(int $userId, int $notificationId): bool
{
    $statement = db()->prepare(
        'UPDATE notification
         SET is_read = 1
         WHERE notification_id = ? AND recipient_user_id = ?'
    );
    $statement->execute([$notificationId, $userId]);

    return $statement->rowCount() === 1;
}

function mark_all_notifications_read(int $userId): void
{
    db()->prepare(
        'UPDATE notification SET is_read = 1 WHERE recipient_user_id = ?'
    )->execute([$userId]);
}

function save_announcement(array $input): array
{
    $announcementId = (int) ($input['announcement_id'] ?? 0);
    $clubId = ($input['club_id'] ?? '') === '' ? null : (int) $input['club_id'];
    $message = trim($input['message'] ?? '');
    $type = $clubId === null ? 'System Notice' : 'Club Notice';
    $plainMessage = preg_replace('/\s+/u', ' ', strip_tags($message));
    $title = mb_substr($plainMessage, 0, 72);
    $title = rtrim($title, " \t\n\r\0\x0B,.;:-");

    if ($message === '' || $title === '') {
        throw new InvalidArgumentException('Enter an announcement message.');
    }

    if ($clubId === null && !is_admin()) {
        throw new DomainException('Only administrators can publish system-wide announcements.');
    }

    if ($clubId !== null && !can_manage_club($clubId)) {
        throw new DomainException('You cannot publish for that club.');
    }

    db()->beginTransaction();

    try {
        $previous = null;

        if ($announcementId) {
            $statement = db()->prepare('SELECT * FROM announcement WHERE announcement_id = ? FOR UPDATE');
            $statement->execute([$announcementId]);
            $previous = $statement->fetch();

            if (!$previous) {
                throw new RuntimeException('Announcement not found.');
            }

            $previousClubId = $previous['club_id'] === null ? null : (int) $previous['club_id'];

            if (($previousClubId === null && !is_admin()) || ($previousClubId !== null && !can_manage_club($previousClubId))) {
                throw new DomainException('You cannot edit this announcement.');
            }

            db()->prepare(
                "UPDATE announcement
                 SET club_id = ?, title = ?, message = ?, announcement_type = ?,
                     expiry_date = NULL, status = 'Active'
                 WHERE announcement_id = ?"
            )->execute([$clubId, $title, $message, $type, $announcementId]);
        } else {
            db()->prepare(
                "INSERT INTO announcement
                    (publisher_user_id, club_id, title, message, announcement_type, expiry_date, status)
                 VALUES (?, ?, ?, ?, ?, NULL, 'Active')"
            )->execute([(int) user()['user_id'], $clubId, $title, $message, $type]);
            $announcementId = (int) db()->lastInsertId();
        }

        $recipientCount = 0;
        $alreadyNotified = $previous['notified_at'] ?? null;

        if (!$alreadyNotified) {
            if ($clubId === null) {
                $recipients = db()->query(
                    "SELECT user_id FROM users WHERE status = 'Active'"
                )->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $statement = db()->prepare(
                    "SELECT student_user_id
                     FROM club_membership
                     WHERE club_id = ?
                       AND approval_status = 'Approved'
                       AND membership_status = 'Active'"
                );
                $statement->execute([$clubId]);
                $recipients = $statement->fetchAll(PDO::FETCH_COLUMN);
            }

            foreach (array_unique(array_map('intval', $recipients)) as $recipientId) {
                notify_user($recipientId, $type, $title . ': ' . $message);
                $recipientCount++;
            }

            db()->prepare(
                'UPDATE announcement SET notified_at = NOW() WHERE announcement_id = ?'
            )->execute([$announcementId]);
        }

        db()->commit();

        return [
            'announcement_id' => $announcementId,
            'status' => 'Active',
            'recipient_count' => $recipientCount,
        ];
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        throw $exception;
    }
}

function remove_announcement(int $announcementId): void
{
    $statement = db()->prepare(
        'SELECT club_id FROM announcement WHERE announcement_id = ?'
    );
    $statement->execute([$announcementId]);
    $clubId = $statement->fetchColumn();

    if ($clubId === false) {
        throw new RuntimeException('Announcement not found.');
    }

    if ($clubId === null && !is_admin()) {
        throw new DomainException('You cannot remove this announcement.');
    }

    if ($clubId !== null && !can_manage_club((int) $clubId)) {
        throw new DomainException('You cannot remove this announcement.');
    }

    db()->prepare(
        "UPDATE announcement SET status = 'Removed' WHERE announcement_id = ?"
    )->execute([$announcementId]);
}
