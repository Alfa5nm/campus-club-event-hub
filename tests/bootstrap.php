<?php

declare(strict_types=1);

putenv('CAMPUSHUB_DB_NAME=campus_club_hub_test');
session_start();
require __DIR__ . '/../includes/bootstrap.php';

function test_expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }

    echo "PASS: $message\n";
}
