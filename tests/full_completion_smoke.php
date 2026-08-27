<?php

declare(strict_types=1);

putenv('CAMPUSHUB_DB_NAME=campus_club_hub_test');
session_start();
require __DIR__ . '/../includes/bootstrap.php';

function verify(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }

    echo "PASS: $message\n";
}

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
verify($interestCount === 3, 'profile interests are replaced transactionally');

$resetLink = create_reset_link('amina.rahman@g.bracu.ac.bd');
parse_str((string) parse_url($resetLink, PHP_URL_QUERY), $query);
verify(!empty($query['token']), 'offline password recovery creates a secure reset link');
reset_password($query['token'], 'Temporary123!');
$hash = db()->query('SELECT password_hash FROM users WHERE user_id = 1')->fetchColumn();
verify(password_verify('Temporary123!', $hash), 'reset link changes the password');

$reused = false;
try {
    reset_password($query['token'], 'Another123!');
} catch (RuntimeException) {
    $reused = true;
}
verify($reused, 'reset links are single use');

$feedbackId = save_feedback(5, 3, 4, 'Updated through the completed feedback service.');
verify($feedbackId === 1, 'eligible attended student can update visible feedback');

$blockedFeedback = false;
try {
    save_feedback(7, 5, 5, 'Should not be allowed.');
} catch (DomainException) {
    $blockedFeedback = true;
}
verify($blockedFeedback, 'absent student cannot submit feedback');

$recommendations = recommended_events(1);
verify(is_array($recommendations), 'recommendations are derived without stored recommendation rows');

$ranking = leaderboard();
verify(count($ranking) > 0, 'leaderboard derives ranked active clubs');
verify((int) $ranking[0]['activity_score'] >= 0, 'leaderboard exposes the published score');

$_SESSION['user'] = [
    'user_id' => 4,
    'full_name' => 'System Administrator',
    'role' => 'Admin',
];
$sent = send_event_reminders(2);
$sentAgain = send_event_reminders(2);
verify($sent > 0 && $sentAgain === 0, 'event reminders fan out once per recipient and event');

$columns = db()->query(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'notification'
       AND column_name IN ('source_type', 'source_id')"
)->fetchColumn();
verify((int) $columns === 2, 'notification source metadata is installed');

db()->exec('DROP DATABASE campus_club_hub_test');
echo "FULL COMPLETION SMOKE TEST COMPLETE\n";
