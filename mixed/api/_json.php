<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(bool $ok, string $message, array $data = [], int $status = 200): never
{
    if ($status === 419) {
        header('HTTP/1.1 419 Authentication Timeout');
    } else {
        http_response_code($status);
    }

    echo json_encode(['ok' => $ok, 'message' => $message, 'data' => $data], JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed.', [], 405);
}
if (!user()) {
    json_response(
        false,
        'Please sign in to continue.',
        ['redirect' => app_url('mixed/login.php')],
        401
    );
}
$token = $_POST['csrf'] ?? '';
if (!csrf_is_valid($token)) {
    json_response(false, 'Your session token expired. Refresh the page and try again.', [], 419);
}
