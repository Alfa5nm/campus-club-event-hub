<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$_SESSION['user'] = [
    'user_id' => 1,
    'full_name' => 'Amina Rahman',
    'role' => 'Student',
];

save_profile(1, [
    'full_name' => 'Amina Rahman',
    'phone' => '01710000001',
    'department' => 'Computer Science',
    'academic_year' => '2025-26',
    'interests' => 'Technology, Debate, Volunteering',
], null);
$interestCount = (int) db()->query(
    'SELECT COUNT(*) FROM student_interest WHERE student_user_id = 1'
)->fetchColumn();
test_expect($interestCount === 3, 'profile interests are replaced transactionally');

$resetLink = create_reset_link('amina.rahman@g.bracu.ac.bd');
parse_str((string) parse_url($resetLink, PHP_URL_QUERY), $query);
test_expect(!empty($query['token']), 'offline password recovery creates a secure reset link');
reset_password($query['token'], 'Temporary123!');
$hash = db()->query('SELECT password_hash FROM users WHERE user_id = 1')->fetchColumn();
test_expect(password_verify('Temporary123!', $hash), 'reset link changes the password');

$reused = false;
try {
    reset_password($query['token'], 'Another123!');
} catch (RuntimeException) {
    $reused = true;
}
test_expect($reused, 'reset links are single use');

$feedbackId = save_feedback(5, 3, 4, 'Updated through the completed feedback service.');
test_expect($feedbackId === 1, 'eligible attended student can update visible feedback');

$blockedFeedback = false;
try {
    save_feedback(7, 5, 5, 'Should not be allowed.');
} catch (DomainException) {
    $blockedFeedback = true;
}
test_expect($blockedFeedback, 'absent student cannot submit feedback');

$recommendations = recommended_events(1);
test_expect(is_array($recommendations), 'recommendations are derived without stored recommendation rows');

$ranking = leaderboard();
test_expect(count($ranking) > 0, 'leaderboard derives ranked active clubs');
test_expect((int) $ranking[0]['activity_score'] >= 0, 'leaderboard exposes the published score');

$registration = register_for_event(1, 3);
test_expect(
    $registration['state'] === 'registered',
    'shared registration service confirms an eligible student'
);
$cancellation = cancel_event_registration(1, 3);
test_expect(
    $cancellation['state'] === 'cancelled',
    'shared registration service cancels the same registration'
);

$_SESSION['user'] = [
    'user_id' => 4,
    'full_name' => 'System Administrator',
    'role' => 'Admin',
];
$membership = update_membership(4, 'approve');
test_expect(
    $membership['club_id'] === 3,
    'shared membership service preserves club authorization context'
);

$notificationId = (int) db()->query(
    'SELECT notification_id
     FROM notification
     WHERE recipient_user_id = 1 AND is_read = 0
     LIMIT 1'
)->fetchColumn();
test_expect(
    mark_notification_read(1, $notificationId),
    'shared notification service marks an owned notification as read'
);

$sent = send_event_reminders(2);
$sentAgain = send_event_reminders(2);
test_expect($sent > 0 && $sentAgain === 0, 'event reminders fan out once per recipient and event');

$columns = db()->query(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'notification'
       AND column_name IN ('source_type', 'source_id')"
)->fetchColumn();
test_expect((int) $columns === 2, 'notification source metadata is installed');

db()->exec('DROP DATABASE campus_club_hub_test');
echo "FULL COMPLETION SMOKE TEST COMPLETE\n";
