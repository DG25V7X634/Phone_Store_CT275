<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/common.php';

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT id, name, brand, price, quantity FROM products';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE name LIKE :name_q OR brand LIKE :brand_q';
    $searchTerm = '%' . $q . '%';
    $params['name_q'] = $searchTerm;
    $params['brand_q'] = $searchTerm;
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$flashType = is_array($flash) && in_array(($flash['type'] ?? ''), ['success', 'danger', 'warning', 'info'], true)
    ? $flash['type']
    : 'info';
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý sản phẩm - Phone Store</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="index.php">PHONE STORE</a><a class="btn btn-outline-light btn-sm" href="index.php">Trang chủ</a></div></nav>
<main class="container py-4"><div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3">Quản lý sản phẩm</h1><a class="btn btn-primary" href="product_create.php">+ Thêm sản phẩm</a></div>
<?php if ($flash): ?><div class="alert alert-<?= e($flashType) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="row g-2 mb-3" method="get"><div class="col-sm-8 col-md-6"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên hoặc thương hiệu"></div><div class="col-auto"><button class="btn btn-dark">Tìm kiếm</button> <a class="btn btn-outline-secondary" href="products.php">Xóa lọc</a></div></form>
<div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-striped table-hover mb-0"><thead class="table-dark"><tr><th>ID</th><th>Tên sản phẩm</th><th>Thương hiệu</th><th class="text-end">Giá (VNĐ)</th><th class="text-end">Số lượng</th><th>Thao tác</th></tr></thead><tbody>
<?php if (!$products): ?><tr><td colspan="6" class="text-center py-4">Không tìm thấy sản phẩm.</td></tr><?php endif; ?>
<?php foreach ($products as $p): ?><tr><td><?= (int)$p['id'] ?></td><td><?= e($p['name']) ?></td><td><?= e($p['brand']) ?></td><td class="text-end"><?= number_format((float)$p['price'], 0, ',', '.') ?></td><td class="text-end"><?= (int)$p['quantity'] ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="product_edit.php?id=<?= (int)$p['id'] ?>">Sửa</a>
<form class="d-inline" method="post" action="product_delete.php" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div><p class="text-muted small mt-3">Tổng số kết quả: <?= count($products) ?></p></main></body></html>
