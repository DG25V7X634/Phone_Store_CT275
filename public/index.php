<?php
declare(strict_types=1);

// Kết nối cơ sở dữ liệu qua tệp cấu hình có sẵn.
require_once __DIR__ . '/config/database.php';

// Lấy danh sách sản phẩm từ MySQL.
try {
    $stmt = $pdo->query(
        'SELECT id, name, brand, price, quantity
         FROM products
         ORDER BY id ASC'
    );
    $products = $stmt->fetchAll();

    $totalProducts = count($products);
    $totalBrands = count(array_unique(array_column($products, 'brand')));
} catch (PDOException $e) {
    // Ghi chi tiết lỗi vào log, không hiển thị thông tin nhạy cảm ra trình duyệt.
    error_log($e->getMessage());
    $products = [];
    $totalProducts = 0;
    $totalBrands = 0;
    $loadError = 'Không thể tải danh sách sản phẩm. Vui lòng kiểm tra cơ sở dữ liệu.';
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý cửa hàng điện thoại</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">PHONE STORE</a>
        <div class="d-flex align-items-center gap-2"><a class="btn btn-outline-light btn-sm" href="products.php">Sản phẩm</a><a class="btn btn-outline-light btn-sm" href="customers.php">Khách hàng</a><a class="btn btn-outline-light btn-sm" href="orders.php">Đơn hàng</a><span class="text-white d-none d-lg-inline">Phone Store</span></div>
    </div>
</nav>

<main class="container py-5">
    <section class="p-4 mb-4 bg-white rounded shadow-sm">
        <h1 class="h3">Tổng quan cửa hàng</h1>
        <p class="text-muted mb-0">Hệ thống quản lý sản phẩm điện thoại</p>
    </section>

    <?php if (isset($loadError)): ?>
        <div class="alert alert-danger" role="alert"><?= e($loadError) ?></div>
    <?php endif; ?>

    <section class="row g-3 mb-4" aria-label="Thống kê cửa hàng">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Tổng số sản phẩm <span class="badge text-bg-success" id="live-status">Live</span></div>
                    <div class="fs-3 fw-bold" id="product-count"><?= $totalProducts ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Số thương hiệu</div>
                    <div class="fs-3 fw-bold"><?= $totalBrands ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Khách hàng / Đơn hàng</div>
                    <div class="fs-4 fw-bold"><span id="customer-count">—</span> / <span id="order-count">—</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Danh sách điện thoại</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">STT</th>
                        <th scope="col">Tên sản phẩm</th>
                        <th scope="col">Thương hiệu</th>
                        <th scope="col" class="text-end">Giá bán</th>
                        <th scope="col" class="text-end">Số lượng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products === []): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Chưa có sản phẩm để hiển thị.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($product['name'] ?? '') ?></td>
                                <td><?= e($product['brand'] ?? '') ?></td>
                                <td class="text-end">
                                    <?= number_format((float) ($product['price'] ?? 0), 0, ',', '.') ?> đ
                                </td>
                                <td class="text-end"><?= (int) ($product['quantity'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p class="text-center text-muted mt-4">Dự án CT275 – Công nghệ Web · Thống kê cập nhật trực tiếp qua Server-Sent Events (SSE).</p>
<script>
(() => {
  const status = document.getElementById('live-status');
  if (!window.EventSource) { status.textContent = 'Không hỗ trợ SSE'; status.className = 'badge text-bg-warning'; return; }
  const stream = new EventSource('live_events.php');
  stream.addEventListener('stats', event => {
    const data = JSON.parse(event.data);
    document.getElementById('product-count').textContent = data.products;
    document.getElementById('customer-count').textContent = data.customers;
    document.getElementById('order-count').textContent = data.orders;
    status.textContent = 'Live'; status.className = 'badge text-bg-success';
  });
  stream.onerror = () => { status.textContent = 'Đang kết nối lại'; status.className = 'badge text-bg-warning'; };
})();
</script>
</main>
</body>
</html>
