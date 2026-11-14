<?php
declare(strict_types=1);

// Tao bo sung duy nhat bang order_items neu chua co.
// Khong xoa/sua san pham, khach hang hay don hang dang ton tai.
$root = dirname(__DIR__);
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "Thieu file .env. Hay chay CHAY_WEBSITE.bat de tao cau hinh.\n");
    exit(1);
}
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim(trim($value), "\"'"));
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_NAME') ?: 'phone_store';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') !== false ? (string)getenv('DB_PASSWORD') : '';
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['products', 'customers', 'orders'] as $required) {
        if (!in_array($required, $tables, true)) {
            throw new RuntimeException("Thieu bang {$required}. CSDL phone_store chua co du cau truc nen. Gui anh loi de duoc huong dan.");
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(15,2) NOT NULL,
        INDEX idx_order_items_order (order_id),
        INDEX idx_order_items_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('categories', $tables, true)) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $appTables = array_values(array_intersect(['categories','products','customers','orders','order_items'], $tables));
    if (count($appTables) !== 5) throw new RuntimeException('Chua du 5 bang chuan.');
    echo "OK: Da kiem tra du 5 bang categories, products, customers, orders, order_items.\n";
    echo "Khong xoa du lieu cu. Co the mo website.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "LOI CSDL: " . $e->getMessage() . "\n");
    exit(1);
}
