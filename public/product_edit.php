<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/common.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('ID sản phẩm không hợp lệ.'); }
$stmt = $pdo->prepare('SELECT id, name, brand, price, quantity, description FROM products WHERE id = :id');
$stmt->execute(['id'=>$id]); $product = $stmt->fetch();
if (!$product) { http_response_code(404); exit('Không tìm thấy sản phẩm.'); }
$errors = [];
$name = trim((string)($_POST['name'] ?? $product['name']));
$brand = trim((string)($_POST['brand'] ?? $product['brand']));
$price = trim((string)($_POST['price'] ?? $product['price']));
$quantity = trim((string)($_POST['quantity'] ?? $product['quantity']));
$description = trim((string)($_POST['description'] ?? $product['description']));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 verify_csrf();
if ($name === '' || (function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name)) > 150) {
    $errors[] = 'Tên sản phẩm là bắt buộc và không quá 150 ký tự.';
}

 if ($brand === '' || (function_exists('mb_strlen') ? mb_strlen($brand, 'UTF-8') : strlen($brand)) > 100) {
    $errors[] = 'Thương hiệu là bắt buộc và không quá 100 ký tự.';
}
 if (!is_numeric($price) || (float)$price<0) $errors[]='Giá phải là số không âm.';
 if (filter_var($quantity,FILTER_VALIDATE_INT)===false || (int)$quantity<0) $errors[]='Số lượng phải là số nguyên không âm.';
 if (!$errors) {
  $update=$pdo->prepare('UPDATE products SET name=:name, brand=:brand, price=:price, quantity=:quantity, description=:description WHERE id=:id');
  $update->execute(['name'=>$name,'brand'=>$brand,'price'=>$price,'quantity'=>(int)$quantity,'description'=>$description,'id'=>$id]);
  redirect_products('Đã cập nhật sản phẩm thành công.');
 }
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sửa sản phẩm</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-4"><div class="card shadow-sm mx-auto" style="max-width:760px"><div class="card-header"><h1 class="h4 mb-0">Sửa sản phẩm #<?= (int)$id ?></h1></div><div class="card-body">
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$id ?>"><div class="mb-3"><label class="form-label">Tên sản phẩm</label><input required maxlength="150" class="form-control" name="name" value="<?= e($name) ?>"></div><div class="mb-3"><label class="form-label">Thương hiệu</label><input required maxlength="100" class="form-control" name="brand" value="<?= e($brand) ?>"></div><div class="row"><div class="col-md-6 mb-3"><label class="form-label">Giá (VNĐ)</label><input required min="0" step="0.01" type="number" class="form-control" name="price" value="<?= e($price) ?>"></div><div class="col-md-6 mb-3"><label class="form-label">Số lượng</label><input required min="0" step="1" type="number" class="form-control" name="quantity" value="<?= e($quantity) ?>"></div></div><div class="mb-3"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="3"><?= e($description) ?></textarea></div><button class="btn btn-primary">Cập nhật</button> <a class="btn btn-outline-secondary" href="products.php">Quay lại</a></form></div></div></main></body></html>
