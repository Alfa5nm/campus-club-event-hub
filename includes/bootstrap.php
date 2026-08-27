<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/services.php';
require_once __DIR__ . '/certificate.php';

// Keep time-bound notices accurate without requiring a scheduled task in XAMPP.
db()->exec("UPDATE announcement SET status='Expired' WHERE status='Active' AND expiry_date IS NOT NULL AND expiry_date<CURDATE()");
