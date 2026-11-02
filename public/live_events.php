<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');

set_time_limit(0);
ignore_user_abort(false);

$started = microtime(true);
$previous = null;

while (!connection_aborted() && (microtime(true) - $started) < 55) {
    try {
        // Dùng đúng tên bảng theo orders.php của dự án: products, customers, orders.
        $data = [
            'products'  => (int) $pdo->query('SELECT COUNT(*) FROM `products`')->fetchColumn(),
            'customers' => (int) $pdo->query('SELECT COUNT(*) FROM `customers`')->fetchColumn(),
            'orders'    => (int) $pdo->query('SELECT COUNT(*) FROM `orders`')->fetchColumn(),
            'time'      => date(DATE_ATOM),
        ];

        // Không phát lại số liệu nếu không đổi; gửi heartbeat để giữ kết nối.
        $snapshot = json_encode(
            [
                'products'  => $data['products'],
                'customers' => $data['customers'],
                'orders'    => $data['orders'],
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($snapshot !== $previous) {
            echo "event: stats\n";
            echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
            $previous = $snapshot;
        } else {
            echo ": heartbeat\n\n";
        }
    } catch (Throwable $e) {
        error_log('live_events.php: ' . $e->getMessage());
        echo "event: app-error\n";
        echo 'data: ' . json_encode(
            ['message' => 'Không thể cập nhật thống kê. Vui lòng kiểm tra kết nối cơ sở dữ liệu và các bảng products, customers, orders.'],
            JSON_UNESCAPED_UNICODE
        ) . "\n\n";
    }

    if (ob_get_level() > 0) {
        @ob_flush();
    }
    flush();
    sleep(5);
}
