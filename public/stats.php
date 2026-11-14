<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $data = [
        'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'customers' => (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
        'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    ];
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('stats.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Không thể tải thống kê.'], JSON_UNESCAPED_UNICODE);
}
