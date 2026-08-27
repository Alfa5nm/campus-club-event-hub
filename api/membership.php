<?php

declare(strict_types=1);

require_once __DIR__ . '/_json.php';

$userId = current_user_id();
$action = $_POST['action'] ?? '';

if ($action === 'request_join') {
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $student = db()->prepare('SELECT 1 FROM students WHERE user_id = ?');
    $student->execute([$userId]);

    if (!$student->fetchColumn()) {
        json_response(false, 'Only student accounts can join clubs.', [], 403);
    }

    try {
        db()->prepare(
            "INSERT INTO club_membership
                (student_user_id, club_id, member_role, approval_status, membership_status)
             VALUES (?, ?, 'Member', 'Pending', 'Active')"
        )->execute([$userId, $clubId]);
        json_response(true, 'Membership request sent.', ['state' => 'Pending']);
    } catch (PDOException $exception) {
        json_response(
            false,
            'You already have a membership or pending request for this club.',
            [],
            409
        );
    }
}

$membershipId = (int) ($_POST['membership_id'] ?? 0);

try {
    $data = update_membership(
        $membershipId,
        $action,
        isset($_POST['member_role']) ? (string) $_POST['member_role'] : null
    );
    json_response(true, 'Membership updated.', $data);
} catch (DomainException $exception) {
    json_response(false, $exception->getMessage(), [], 403);
} catch (InvalidArgumentException $exception) {
    json_response(false, $exception->getMessage(), [], 422);
} catch (RuntimeException $exception) {
    json_response(false, $exception->getMessage(), [], 400);
}
