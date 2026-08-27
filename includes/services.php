<?php

declare(strict_types=1);

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        exit('Administrator access is required.');
    }
}

function current_user_id(): int
{
    return (int) (user()['user_id'] ?? 0);
}

function upload_image(array $file, string $folder): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The image upload could not be completed.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Images must be 5 MB or smaller.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        throw new InvalidArgumentException('Upload a JPEG, PNG, or WebP image.');
    }

    $relativeDirectory = 'uploads/' . trim($folder, '/');
    $absoluteDirectory = dirname(__DIR__) . '/' . $relativeDirectory;

    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true)) {
        throw new RuntimeException('The upload directory could not be created.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = $absoluteDirectory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The uploaded image could not be stored.');
    }

    return $relativeDirectory . '/' . $filename;
}

function save_profile(int $userId, array $input, ?array $picture): void
{
    $fullName = trim($input['full_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $department = trim($input['department'] ?? '');
    $academicYear = trim($input['academic_year'] ?? '');

    if ($fullName === '' || mb_strlen($fullName) > 120) {
        throw new InvalidArgumentException('Enter a valid full name.');
    }

    $studentStatement = db()->prepare('SELECT 1 FROM students WHERE user_id = ?');
    $studentStatement->execute([$userId]);
    $isStudent = (bool) $studentStatement->fetchColumn();

    if ($isStudent && $department === '') {
        throw new InvalidArgumentException('Department is required.');
    }

    $picturePath = $picture ? upload_image($picture, 'profiles') : null;

    db()->beginTransaction();

    try {
        $sql = 'UPDATE users SET full_name = ?, phone = ?';
        $parameters = [$fullName, $phone ?: null];

        if ($picturePath !== null) {
            $sql .= ', profile_picture = ?';
            $parameters[] = $picturePath;
        }

        $sql .= ' WHERE user_id = ?';
        $parameters[] = $userId;
        db()->prepare($sql)->execute($parameters);

        if ($isStudent) {
            db()->prepare(
                'UPDATE students SET department = ?, academic_year = ? WHERE user_id = ?'
            )->execute([$department, $academicYear ?: null, $userId]);

            db()->prepare('DELETE FROM student_interest WHERE student_user_id = ?')->execute([$userId]);
            $interestStatement = db()->prepare(
                'INSERT IGNORE INTO student_interest (student_user_id, interest) VALUES (?, ?)'
            );

            $interests = array_unique(array_filter(array_map(
                'trim',
                explode(',', $input['interests'] ?? '')
            )));

            foreach (array_slice($interests, 0, 12) as $interest) {
                $interestStatement->execute([$userId, mb_substr($interest, 0, 80)]);
            }
        }

        db()->commit();
        $_SESSION['user']['full_name'] = $fullName;
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        throw $exception;
    }
}

function change_password(int $userId, string $currentPassword, string $newPassword): void
{
    $statement = db()->prepare('SELECT password_hash FROM users WHERE user_id = ?');
    $statement->execute([$userId]);
    $hash = $statement->fetchColumn();

    if (!$hash || !password_verify($currentPassword, $hash)) {
        throw new InvalidArgumentException('The current password is incorrect.');
    }

    validate_new_password($newPassword);

    db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([
        password_hash($newPassword, PASSWORD_DEFAULT),
        $userId,
    ]);
}

function create_reset_link(string $email): string
{
    $statement = db()->prepare("SELECT user_id FROM users WHERE email = ? AND status = 'Active'");
    $statement->execute([strtolower(trim($email))]);
    $userId = (int) $statement->fetchColumn();

    if (!$userId) {
        throw new RuntimeException('No active account matches that email address.');
    }

    $token = bin2hex(random_bytes(32));
    db()->prepare(
        'INSERT INTO password_reset_token (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
    )->execute([$userId, hash('sha256', $token)]);

    return 'reset-password.php?token=' . urlencode($token);
}

function reset_password(string $token, string $password): void
{
    validate_new_password($password);

    $statement = db()->prepare(
        'SELECT reset_id, user_id FROM password_reset_token
         WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW()
         LIMIT 1'
    );
    $statement->execute([hash('sha256', $token)]);
    $reset = $statement->fetch();

    if (!$reset) {
        throw new RuntimeException('This password reset link is invalid or has expired.');
    }

    db()->beginTransaction();

    try {
        db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $reset['user_id'],
        ]);
        db()->prepare('UPDATE password_reset_token SET used_at = NOW() WHERE reset_id = ?')->execute([
            $reset['reset_id'],
        ]);
        db()->commit();
    } catch (Throwable $exception) {
        db()->rollBack();
        throw $exception;
    }
}

function validate_new_password(string $password): void
{
    if (strlen($password) < 8) {
        throw new InvalidArgumentException(
            'The new password must contain at least 8 characters.'
        );
    }
}

function register_for_event(int $userId, int $eventId): array
{
    db()->beginTransaction();

    try {
        $student = db()->prepare('SELECT 1 FROM students WHERE user_id = ?');
        $student->execute([$userId]);

        if (!$student->fetchColumn()) {
            throw new RuntimeException('Only student accounts can register for events.');
        }

        $statement = db()->prepare(
            "SELECT e.*, COUNT(er.registration_id) AS taken
             FROM events e
             LEFT JOIN event_registration er
               ON er.event_id = e.event_id
              AND er.registration_status = 'Registered'
             WHERE e.event_id = ?
             GROUP BY e.event_id
             FOR UPDATE"
        );
        $statement->execute([$eventId]);
        $event = $statement->fetch();

        if (!$event || $event['status'] !== 'Upcoming') {
            throw new RuntimeException('This event is not accepting registrations.');
        }

        if ($event['registration_deadline'] && $event['registration_deadline'] < date('Y-m-d')) {
            throw new RuntimeException('The registration deadline has passed.');
        }

        if ((int) $event['taken'] >= (int) $event['maximum_participants']) {
            throw new RuntimeException('This event has reached capacity.');
        }

        db()->prepare(
            "INSERT INTO event_registration
                (student_user_id, event_id, registration_status, qr_token)
             VALUES (?, ?, 'Registered', NULL)
             ON DUPLICATE KEY UPDATE
                registration_status = 'Registered',
                qr_token = NULL,
                cancellation_reason = NULL,
                updated_at = CURRENT_TIMESTAMP"
        )->execute([$userId, $eventId]);

        notify_user(
            $userId,
            'Registration Confirmation',
            'Your registration for ' . $event['title'] . ' is confirmed.'
        );
        db()->commit();

        return [
            'state' => 'registered',
            'registration_count' => (int) $event['taken'] + 1,
            'capacity' => (int) $event['maximum_participants'],
        ];
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        throw $exception;
    }
}

function cancel_event_registration(int $userId, int $eventId): array
{
    $statement = db()->prepare(
        "UPDATE event_registration
         SET registration_status = 'Cancelled', cancellation_reason = 'Cancelled by student'
         WHERE event_id = ?
           AND student_user_id = ?
           AND registration_status = 'Registered'"
    );
    $statement->execute([$eventId, $userId]);

    if (!$statement->rowCount()) {
        throw new RuntimeException('No active registration was found.');
    }

    notify_user(
        $userId,
        'Registration Cancellation',
        'Your event registration was cancelled.'
    );

    $count = db()->prepare(
        "SELECT COUNT(*)
         FROM event_registration
         WHERE event_id = ? AND registration_status = 'Registered'"
    );
    $count->execute([$eventId]);

    return [
        'state' => 'cancelled',
        'registration_count' => (int) $count->fetchColumn(),
    ];
}

function update_membership(int $membershipId, string $action, ?string $role = null): array
{
    $statement = db()->prepare(
        'SELECT cm.club_id, cm.student_user_id, c.club_name
         FROM club_membership cm
         JOIN clubs c ON c.club_id = cm.club_id
         WHERE cm.membership_id = ?'
    );
    $statement->execute([$membershipId]);
    $membership = $statement->fetch();

    if (!$membership || !can_manage_club((int) $membership['club_id'])) {
        throw new DomainException('You are not authorized to manage this membership.');
    }

    $messages = [
        'approve' => [
            'Membership Approved',
            'Your membership in ' . $membership['club_name'] . ' was approved.',
        ],
        'reject' => [
            'Membership Rejected',
            'Your membership request for ' . $membership['club_name'] . ' was rejected.',
        ],
        'remove' => [
            'Membership Removed',
            'Your membership in ' . $membership['club_name'] . ' was removed.',
        ],
    ];

    if ($action === 'approve') {
        db()->prepare(
            "UPDATE club_membership
             SET approval_status = 'Approved', membership_status = 'Active'
             WHERE membership_id = ?"
        )->execute([$membershipId]);
    } elseif ($action === 'reject') {
        db()->prepare(
            "UPDATE club_membership SET approval_status = 'Rejected' WHERE membership_id = ?"
        )->execute([$membershipId]);
    } elseif ($action === 'remove') {
        db()->prepare(
            "UPDATE club_membership SET membership_status = 'Removed' WHERE membership_id = ?"
        )->execute([$membershipId]);
    } elseif ($action === 'role') {
        $validRoles = array_merge(['Member'], executive_roles());

        if ($role === null || !in_array($role, $validRoles, true)) {
            throw new InvalidArgumentException('Invalid member role.');
        }

        db()->prepare(
            'UPDATE club_membership SET member_role = ? WHERE membership_id = ?'
        )->execute([$role, $membershipId]);
    } else {
        throw new RuntimeException('Unknown membership action.');
    }

    if (isset($messages[$action])) {
        notify_user(
            (int) $membership['student_user_id'],
            $messages[$action][0],
            $messages[$action][1]
        );
    }

    return [
        'action' => $action,
        'membership_id' => $membershipId,
        'club_id' => (int) $membership['club_id'],
    ];
}

function notify_once(
    int $userId,
    string $type,
    string $message,
    string $sourceType,
    int $sourceId
): bool {
    $statement = db()->prepare(
        'INSERT IGNORE INTO notification (
            recipient_user_id, notification_type, message, source_type, source_id
         ) VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $userId,
        mb_substr($type, 0, 80),
        mb_substr($message, 0, 500),
        $sourceType,
        $sourceId,
    ]);

    return $statement->rowCount() === 1;
}

function send_event_reminders(int $eventId): int
{
    $statement = db()->prepare(
        "SELECT e.title, e.club_id, er.student_user_id
         FROM events e
         JOIN event_registration er ON er.event_id = e.event_id
         WHERE e.event_id = ?
           AND e.status = 'Upcoming'
           AND er.registration_status = 'Registered'"
    );
    $statement->execute([$eventId]);
    $registrations = $statement->fetchAll();

    if (!$registrations) {
        return 0;
    }

    if (!can_manage_club((int) $registrations[0]['club_id'])) {
        throw new DomainException('You cannot send reminders for this event.');
    }

    $sent = 0;

    foreach ($registrations as $registration) {
        $sent += (int) notify_once(
            (int) $registration['student_user_id'],
            'Event Reminder',
            'Reminder: ' . $registration['title'] . ' is coming up.',
            'event_reminder',
            $eventId
        );
    }

    return $sent;
}

function save_feedback(int $userId, int $registrationId, int $rating, string $review): int
{
    if ($rating < 1 || $rating > 5) {
        throw new InvalidArgumentException('Rating must be between 1 and 5.');
    }

    $statement = db()->prepare(
        "SELECT f.feedback_id, f.status
         FROM event_registration er
         LEFT JOIN feedback f ON f.registration_id = er.registration_id
         WHERE er.registration_id = ?
           AND er.student_user_id = ?
           AND er.registration_status = 'Attended'"
    );
    $statement->execute([$registrationId, $userId]);
    $existing = $statement->fetch();

    if ($existing === false) {
        throw new DomainException('Feedback is available only after Present attendance.');
    }

    if (!empty($existing['feedback_id'])) {
        if ($existing['status'] !== 'Visible') {
            throw new DomainException('Moderated feedback cannot be edited.');
        }

        db()->prepare(
            'UPDATE feedback SET rating = ?, review_text = ?, submitted_at = NOW() WHERE feedback_id = ?'
        )->execute([$rating, trim($review) ?: null, $existing['feedback_id']]);

        return (int) $existing['feedback_id'];
    }

    db()->prepare(
        "INSERT INTO feedback (registration_id, rating, review_text, status)
         VALUES (?, ?, ?, 'Visible')"
    )->execute([$registrationId, $rating, trim($review) ?: null]);

    return (int) db()->lastInsertId();
}

function recommended_events(int $userId, int $limit = 8): array
{
    $sql = "SELECT
                e.*,
                c.club_name,
                COUNT(DISTINCT er.registration_id) AS popularity,
                CASE WHEN MAX(si.interest IS NOT NULL) THEN 40 ELSE 0 END
                    + CASE WHEN MAX(prior.participation_count) > 0 THEN 20 ELSE 0 END
                    + LEAST(COUNT(DISTINCT er.registration_id), 20)
                    + CASE WHEN e.event_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) THEN 10 ELSE 0 END
                    AS recommendation_score,
                CASE
                    WHEN MAX(si.interest IS NOT NULL) THEN CONCAT('Matches your ', e.event_category, ' interest')
                    WHEN MAX(prior.participation_count) > 0 THEN 'Similar to events you attended'
                    WHEN COUNT(DISTINCT er.registration_id) >= 3 THEN 'Popular with students'
                    ELSE 'Upcoming and open for registration'
                END AS recommendation_reason
            FROM events e
            JOIN clubs c ON c.club_id = e.club_id AND c.status = 'Active'
            LEFT JOIN event_registration er
                ON er.event_id = e.event_id AND er.registration_status <> 'Cancelled'
            LEFT JOIN student_interest si
                ON si.student_user_id = ?
               AND LOWER(si.interest) = LOWER(e.event_category)
            LEFT JOIN (
                SELECT e2.event_category, COUNT(*) AS participation_count
                FROM event_registration er2
                JOIN events e2 ON e2.event_id = er2.event_id
                WHERE er2.student_user_id = ? AND er2.registration_status = 'Attended'
                GROUP BY e2.event_category
            ) prior ON LOWER(prior.event_category) = LOWER(e.event_category)
            LEFT JOIN event_registration mine
                ON mine.event_id = e.event_id AND mine.student_user_id = ?
            WHERE e.status = 'Upcoming'
              AND e.event_date >= CURDATE()
              AND (e.registration_deadline IS NULL OR e.registration_deadline >= CURDATE())
              AND mine.registration_id IS NULL
            GROUP BY e.event_id
            HAVING popularity < e.maximum_participants
            ORDER BY recommendation_score DESC, e.event_date ASC
            LIMIT " . max(1, min($limit, 20));
    $statement = db()->prepare($sql);
    $statement->execute([$userId, $userId, $userId]);

    return $statement->fetchAll();
}

function leaderboard(): array
{
    return db()->query(
        "SELECT
            c.club_id,
            c.club_name,
            COUNT(DISTINCT CASE WHEN e.status <> 'Cancelled' THEN e.event_id END) AS event_count,
            COUNT(DISTINCT CASE WHEN er.registration_status <> 'Cancelled' THEN er.registration_id END) AS registrations,
            COUNT(DISTINCT CASE WHEN er.registration_status = 'Attended' THEN er.registration_id END) AS attendees,
            ROUND(COALESCE(AVG(CASE WHEN f.status = 'Visible' THEN f.rating END), 0), 1) AS average_rating,
            (
                COUNT(DISTINCT CASE WHEN e.status <> 'Cancelled' THEN e.event_id END) * 10
                + COUNT(DISTINCT CASE WHEN er.registration_status <> 'Cancelled' THEN er.registration_id END) * 2
                + COUNT(DISTINCT CASE WHEN er.registration_status = 'Attended' THEN er.registration_id END) * 3
                + ROUND(COALESCE(AVG(CASE WHEN f.status = 'Visible' THEN f.rating END), 0) * 5)
            ) AS activity_score
         FROM clubs c
         LEFT JOIN events e ON e.club_id = c.club_id
         LEFT JOIN event_registration er ON er.event_id = e.event_id
         LEFT JOIN feedback f ON f.registration_id = er.registration_id
         WHERE c.status = 'Active'
         GROUP BY c.club_id
         ORDER BY activity_score DESC, c.club_name"
    )->fetchAll();
}
