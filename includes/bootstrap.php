<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/certificate.php';

// Keep time-bound notices accurate without requiring a scheduled task in XAMPP.
db()->exec("UPDATE announcement SET status='Expired' WHERE status='Active' AND expiry_date IS NOT NULL AND expiry_date<CURDATE()");
