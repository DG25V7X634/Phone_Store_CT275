<?php
$products = [
    [
        'name' => 'iPhone 16',
        'brand' => 'Apple',
        'price' => 21990000,
        'quantity' => 10
    ],
    [
        'name' => 'Samsung Galaxy S25',
        'brand' => 'Samsung',
        'price' => 19990000,
        'quantity' => 8
    ],
    [
        'name' => 'Xiaomi 15',
        'brand' => 'Xiaomi',
        'price' => 15990000,
        'quantity' => 12
    ],
];
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
        <span class="text-white">Quản lý cửa hàng điện thoại</span>
    </div>
</nav>

<div class="container py-5">
    <div class="p-4 mb-4 bg-white rounded shadow-sm">
        <h1 class="h3">Tổng quan cửa hàng</h1>
        <p class="text-muted mb-0">Hệ thống quản lý sản phẩm điện thoại</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Tổng số sản phẩm mẫu</div>
                    <div class="fs-3 fw-bold"><?= count($products) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Thương hiệu</div>
                    <div class="fs-3 fw-bold">3</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Trạng thái</div>
                    <div class="fs-4 fw-bold text-success">Đang hoạt động</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Danh sách điện thoại</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>STT</th>
                        <th>Tên sản phẩm</th>
                        <th>Thương hiệu</th>
                        <th>Giá bán</th>
                        <th>Số lượng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($product['brand'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format($product['price'], 0, ',', '.') ?> đ</td>
                        <td><?= $product['quantity'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-center text-muted mt-4">Dự án CT275 – Công nghệ Web</p>
</div>
</body>
</html>
```
