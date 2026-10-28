<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}
function redirect_products(string $message, string $type = 'success'): never {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: products.php');
    exit;
}
