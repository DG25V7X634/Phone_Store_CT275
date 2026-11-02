<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/common.php';

$errors = [];
$flash = null;

/**
 * Phone Store - orders.php
 * Dùng đúng bảng khách hàng "customers" và đơn hàng "orders"
 * theo cấu trúc đang được customers.php sử dụng.
 */
function table_columns(PDO $pdo, string $table): array {
    $safe = str_replace('`', '``', $table);
    $rows = $pdo->query("SHOW COLUMNS FROM `{$safe}`")->fetchAll(PDO::FETCH_ASSOC);
    return array_column($rows, 'Field');
}
function first_column(array $columns, array $candidates): ?string {
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) return $candidate;
    }
    return null;
}

$customerTable = 'customers';
$orderTable = 'orders';
$productTable = 'products';

$customerColumns = table_columns($pdo, $customerTable);
$orderColumns = table_columns($pdo, $orderTable);
$productColumns = table_columns($pdo, $productTable);

$orderItemTable = null;
foreach (['order_items', 'order_details', 'ps_order_items'] as $candidate) {
    try {
        $pdo->query("SHOW COLUMNS FROM `{$candidate}`");
        $orderItemTable = $candidate;
        break;
    } catch (Throwable $e) {
        // Thử tên bảng chi tiết đơn hàng kế tiếp.
    }
}
$itemColumns = $orderItemTable ? table_columns($pdo, $orderItemTable) : [];

$customerNameCol = first_column($customerColumns, ['name', 'full_name', 'customer_name']);
$customerPhoneCol = first_column($customerColumns, ['phone', 'phone_number']);
$orderCustomerCol = first_column($orderColumns, ['customer_id']);
$orderTotalCol = first_column($orderColumns, ['total_amount', 'total', 'amount']);
$orderStatusCol = first_column($orderColumns, ['status']);
$orderDateCol = first_column($orderColumns, ['order_date', 'created_at']);

$itemOrderCol = first_column($itemColumns, ['order_id']);
$itemProductCol = first_column($itemColumns, ['product_id']);
$itemQtyCol = first_column($itemColumns, ['quantity', 'qty']);
$itemPriceCol = first_column($itemColumns, ['unit_price', 'price']);

$productNameCol = first_column($productColumns, ['name', 'product_name']);
$productPriceCol = first_column($productColumns, ['price', 'unit_price']);
$productStockCol = first_column($productColumns, ['quantity', 'stock', 'stock_quantity']);

if (!$customerNameCol || !$customerPhoneCol || !$orderCustomerCol || !$orderTotalCol || !$orderStatusCol) {
    http_response_code(500);
    exit('Cấu trúc bảng customers hoặc orders không đúng như dự án đang sử dụng. Vui lòng gửi file SQL để đối chiếu.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'create');

    if ($action === 'status') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = (string)($_POST['status'] ?? '');
        $allowedStatuses = ['Mới', 'Đang xử lý', 'Đã giao', 'Đã hủy'];

        if (!$id || $id < 1 || !in_array($status, $allowedStatuses, true)) {
            $_SESSION['flash'] = ['message' => 'Mã đơn hàng hoặc trạng thái không hợp lệ.', 'type' => 'danger'];
            header('Location: orders.php'); exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id, `{$orderStatusCol}` AS status FROM `{$orderTable}` WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $current = $stmt->fetch();
            if (!$current) throw new RuntimeException('Không tìm thấy đơn hàng.');

            if ($orderItemTable && $itemOrderCol && $itemProductCol && $itemQtyCol && $productStockCol) {
                $itemsStmt = $pdo->prepare("SELECT `{$itemProductCol}` AS product_id, `{$itemQtyCol}` AS quantity FROM `{$orderItemTable}` WHERE `{$itemOrderCol}` = ?");
                $itemsStmt->execute([$id]);
                $items = $itemsStmt->fetchAll();

                foreach ($items as $item) {
                    $qty = (int)$item['quantity'];
                    $pid = (int)$item['product_id'];
                    if ($current['status'] !== 'Đã hủy' && $status === 'Đã hủy') {
                        $stock = $pdo->prepare("UPDATE `{$productTable}` SET `{$productStockCol}` = `{$productStockCol}` + ? WHERE id = ?");
                        $stock->execute([$qty, $pid]);
                    } elseif ($current['status'] === 'Đã hủy' && $status !== 'Đã hủy') {
                        $stock = $pdo->prepare("UPDATE `{$productTable}` SET `{$productStockCol}` = `{$productStockCol}` - ? WHERE id = ? AND `{$productStockCol}` >= ?");
                        $stock->execute([$qty, $pid, $qty]);
                        if ($stock->rowCount() !== 1) throw new RuntimeException('Không đủ tồn kho để mở lại đơn đã hủy.');
                    }
                }
            }

            $update = $pdo->prepare("UPDATE `{$orderTable}` SET `{$orderStatusCol}` = ? WHERE id = ?");
            $update->execute([$status, $id]);
            $pdo->commit();
            $_SESSION['flash'] = ['message' => 'Đã cập nhật trạng thái đơn hàng.', 'type' => 'success'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = ['message' => $e instanceof RuntimeException ? $e->getMessage() : 'Không thể cập nhật trạng thái đơn hàng.', 'type' => 'danger'];
        }
        header('Location: orders.php'); exit;
    }

    $customerId = (int)($_POST['customer_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $note = trim((string)($_POST['note'] ?? ''));

    if ($customerId < 1) $errors[] = 'Hãy chọn khách hàng.';
    if ($productId < 1) $errors[] = 'Hãy chọn sản phẩm.';
    if ($qty === false || $qty < 1 || $qty > 1000) $errors[] = 'Số lượng phải từ 1 đến 1000.';
    if (mb_strlen($note, 'UTF-8') > 500) $errors[] = 'Ghi chú không được vượt quá 500 ký tự.';
    if (!$orderItemTable || !$itemOrderCol || !$itemProductCol || !$itemQtyCol || !$itemPriceCol) {
        $errors[] = 'Không tìm thấy bảng chi tiết đơn hàng phù hợp (order_items/order_details). Chưa tạo đơn để tránh mất dữ liệu.';
    }
    if (!$productNameCol || !$productPriceCol || !$productStockCol) {
        $errors[] = 'Cấu trúc bảng products không đủ các cột tên, giá và tồn kho.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $c = $pdo->prepare("SELECT id FROM `{$customerTable}` WHERE id = ?");
            $c->execute([$customerId]);
            if (!$c->fetch()) throw new RuntimeException('Khách hàng không tồn tại.');

            $p = $pdo->prepare("SELECT id, `{$productPriceCol}` AS price, `{$productStockCol}` AS quantity FROM `{$productTable}` WHERE id = ? FOR UPDATE");
            $p->execute([$productId]);
            $product = $p->fetch();
            if (!$product) throw new RuntimeException('Sản phẩm không tồn tại.');
            if ((int)$product['quantity'] < (int)$qty) throw new RuntimeException('Số lượng tồn kho không đủ.');

            $total = (float)$product['price'] * (int)$qty;
            $insertColumns = [$orderCustomerCol, $orderTotalCol, $orderStatusCol];
            $insertValues = [$customerId, $total, 'Mới'];

            // Bổ sung subtotal nếu CSDL có cột bắt buộc này.
            if (in_array('subtotal', $orderColumns, true)) {
                $insertColumns[] = 'subtotal';
                $insertValues[] = $total;
            }
            if (in_array('note', $orderColumns, true)) {
                $insertColumns[] = 'note';
                $insertValues[] = $note !== '' ? $note : null;
            }
            $quoted = array_map(fn($col) => "`{$col}`", $insertColumns);
            $marks = implode(',', array_fill(0, count($insertColumns), '?'));
            $ins = $pdo->prepare("INSERT INTO `{$orderTable}` (" . implode(',', $quoted) . ") VALUES ({$marks})");
            $ins->execute($insertValues);
            $orderId = (int)$pdo->lastInsertId();

            // Một số CSDL yêu cầu subtotal ở bảng chi tiết đơn hàng.
            $itemInsertColumns = [$itemOrderCol, $itemProductCol, $itemQtyCol, $itemPriceCol];
            $itemInsertValues = [$orderId, $productId, (int)$qty, $product['price']];
            if (in_array('subtotal', $itemColumns, true)) {
                $itemInsertColumns[] = 'subtotal';
                $itemInsertValues[] = $total;
            }
            $itemQuoted = array_map(fn($col) => "`{$col}`", $itemInsertColumns);
            $itemMarks = implode(',', array_fill(0, count($itemInsertColumns), '?'));
            $itemInsert = $pdo->prepare(
                "INSERT INTO `{$orderItemTable}` (" . implode(',', $itemQuoted) . ") VALUES ({$itemMarks})"
            );
            $itemInsert->execute($itemInsertValues);

            $stock = $pdo->prepare("UPDATE `{$productTable}` SET `{$productStockCol}` = `{$productStockCol}` - ? WHERE id = ?");
            $stock->execute([(int)$qty, $productId]);

            $pdo->commit();
            $_SESSION['flash'] = ['message' => 'Đã tạo đơn hàng #' . $orderId . ' thành công.', 'type' => 'success'];
            header('Location: orders.php'); exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Không thể tạo đơn hàng. Hãy kiểm tra cấu trúc bảng đơn hàng và chi tiết đơn hàng.';
        }
    }
}

$customersSql = "SELECT id, `{$customerNameCol}` AS full_name, `{$customerPhoneCol}` AS phone FROM `{$customerTable}` ORDER BY `{$customerNameCol}`";
$customers = $pdo->query($customersSql)->fetchAll(PDO::FETCH_ASSOC);

$products = [];
if ($productNameCol && $productPriceCol && $productStockCol) {
    $products = $pdo->query("SELECT id, `{$productNameCol}` AS name, `{$productPriceCol}` AS price, `{$productStockCol}` AS quantity FROM `{$productTable}` ORDER BY `{$productNameCol}`")->fetchAll(PDO::FETCH_ASSOC);
}

$q = trim((string)($_GET['q'] ?? ''));
$dateSelect = $orderDateCol ? "o.`{$orderDateCol}`" : "o.id";
$itemCountSelect = ($orderItemTable && $itemOrderCol)
    ? "(SELECT COUNT(*) FROM `{$orderItemTable}` i WHERE i.`{$itemOrderCol}` = o.id)"
    : "0";

$sql = "SELECT o.*, c.`{$customerNameCol}` AS full_name, c.`{$customerPhoneCol}` AS phone, {$dateSelect} AS display_date, {$itemCountSelect} AS item_count
        FROM `{$orderTable}` o
        JOIN `{$customerTable}` c ON c.id = o.`{$orderCustomerCol}`
        WHERE CAST(o.id AS CHAR) LIKE ? OR c.`{$customerNameCol}` LIKE ?
        ORDER BY o.id DESC";
$st = $pdo->prepare($sql);
$like = '%' . $q . '%';
$st->execute([$like, $like]);
$orders = $st->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý đơn hàng - Phone Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">PHONE STORE</a>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-light btn-sm" href="customers.php">Khách hàng</a>
      <a class="btn btn-outline-light btn-sm" href="index.php">Trang chủ</a>
    </div>
  </div>
</nav>
<main class="container py-4">
<h1 class="h3 mb-3">Quản lý đơn hàng</h1>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<section class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Tạo đơn hàng</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="col-md-4">
        <label class="form-label">Khách hàng *</label>
        <select required name="customer_id" class="form-select">
          <option value="">-- Chọn khách hàng --</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['full_name'] . ' — ' . $c['phone']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text"><a href="customers.php">Thêm khách hàng mới</a></div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Sản phẩm *</label>
        <select required name="product_id" class="form-select">
          <option value="">-- Chọn sản phẩm --</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['quantity'] < 1 ? 'disabled' : '') ?>>
              <?= e($p['name']) ?> — <?= number_format((float)$p['price'], 0, ',', '.') ?> đ (tồn: <?= (int)$p['quantity'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Số lượng *</label>
        <input required type="number" min="1" max="1000" name="quantity" value="1" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Ghi chú</label>
        <input maxlength="500" name="note" class="form-control">
      </div>
      <div class="col-12"><button class="btn btn-primary" <?= (!$customers || !$products ? 'disabled' : '') ?>>Tạo đơn hàng</button></div>
    </form>
    <?php if (!$customers): ?><div class="small text-danger mt-2">Chưa có khách hàng. Hãy thêm khách hàng trước khi đặt đơn.</div><?php endif; ?>
  </div>
</section>

<form class="input-group mb-3" method="get">
  <input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Tìm theo mã đơn hoặc tên khách">
  <button class="btn btn-dark">Tìm</button>
</form>

<section class="card shadow-sm">
<div class="table-responsive">
<table class="table table-striped align-middle mb-0">
<thead class="table-dark"><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày tạo</th><th>Tổng tiền</th><th>Sản phẩm</th><th>Trạng thái</th></tr></thead>
<tbody>
<?php foreach ($orders as $o): ?>
<tr>
  <td>#<?= (int)$o['id'] ?></td>
  <td><?= e($o['full_name']) ?><div class="small text-muted"><?= e($o['phone']) ?></div></td>
  <td><?= e($o['display_date']) ?></td>
  <td><?= number_format((float)$o[$orderTotalCol], 0, ',', '.') ?> đ</td>
  <td><?= (int)$o['item_count'] ?></td>
  <td>
    <form method="post" class="d-flex gap-1">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="status">
      <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
      <select name="status" class="form-select form-select-sm">
        <?php foreach (['Mới', 'Đang xử lý', 'Đã giao', 'Đã hủy'] as $s): ?>
          <option <?= $s === $o[$orderStatusCol] ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-outline-primary">Lưu</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$orders): ?><tr><td colspan="6" class="text-center text-muted p-4">Chưa có đơn hàng.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</section>
<p class="small text-muted mt-3">Tạo đơn sẽ trừ tồn kho; hủy đơn sẽ hoàn kho. Trạng thái có thể cập nhật sau khi tạo.</p>
</main>
</body>
</html>
