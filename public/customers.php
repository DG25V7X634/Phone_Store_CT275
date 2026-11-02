<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/common.php';

$errors = [];
$editing = null;

/*
 * CSDL thực tế của dự án:
 * customers(id, name, phone, email, address, created_at)
 * orders(id, customer_id, order_date, total_amount, status, created_at)
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string)($_POST['action'] ?? 'save');

    // Xóa khách hàng
    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $_SESSION['flash'] = [
                'message' => 'Mã khách hàng không hợp lệ.',
                'type' => 'danger'
            ];
        } else {
            try {
                $st = $pdo->prepare('DELETE FROM customers WHERE id = ?');
                $st->execute([$id]);

                $_SESSION['flash'] = $st->rowCount()
                    ? ['message' => 'Đã xóa khách hàng.', 'type' => 'success']
                    : ['message' => 'Không tìm thấy khách hàng để xóa.', 'type' => 'warning'];
            } catch (PDOException $e) {
                // Không xóa khách hàng đang được tham chiếu bởi đơn hàng.
                $_SESSION['flash'] = [
                    'message' => 'Không thể xóa khách hàng đã có đơn hàng.',
                    'type' => 'warning'
                ];
            }
        }

        header('Location: customers.php');
        exit;
    }

    // Thêm hoặc cập nhật khách hàng
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['full_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));

    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($name, 'UTF-8')
        : strlen($name);

    if ($name === '' || $nameLength > 150) {
        $errors[] = 'Họ tên bắt buộc, tối đa 150 ký tự.';
    }

    if ($phone === '' || strlen($phone) > 20) {
        $errors[] = 'Số điện thoại bắt buộc, tối đa 20 ký tự.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (strlen($email) > 150) {
        $errors[] = 'Email không được vượt quá 150 ký tự.';
    }

    if (strlen($address) > 255) {
        $errors[] = 'Địa chỉ không được vượt quá 255 ký tự.';
    }

    if (!$errors) {
        try {
            if ($id > 0) {
                $st = $pdo->prepare(
                    'UPDATE customers
                     SET name = ?, phone = ?, email = ?, address = ?
                     WHERE id = ?'
                );
                $st->execute([
                    $name,
                    $phone,
                    $email !== '' ? $email : null,
                    $address !== '' ? $address : null,
                    $id
                ]);
                $msg = 'Đã cập nhật khách hàng.';
            } else {
                $st = $pdo->prepare(
                    'INSERT INTO customers (name, phone, email, address)
                     VALUES (?, ?, ?, ?)'
                );
                $st->execute([
                    $name,
                    $phone,
                    $email !== '' ? $email : null,
                    $address !== '' ? $address : null
                ]);
                $msg = 'Đã thêm khách hàng.';
            }

            $_SESSION['flash'] = [
                'message' => $msg,
                'type' => 'success'
            ];

            header('Location: customers.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000'
                ? 'Số điện thoại này đã tồn tại hoặc dữ liệu đang được sử dụng.'
                : 'Không thể lưu khách hàng. Vui lòng kiểm tra lại dữ liệu.';
        }
    }

    $editing = [
        'id' => $id,
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'address' => $address
    ];
}

// Lấy dữ liệu khách hàng để sửa
if (!$editing && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId && $editId > 0) {
        $st = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
        $st->execute([$editId]);
        $editing = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

// Tìm kiếm và đếm số đơn hàng theo bảng orders thực tế
$q = trim((string)($_GET['q'] ?? ''));

$sql = '
    SELECT
        c.*,
        (
            SELECT COUNT(*)
            FROM orders o
            WHERE o.customer_id = c.id
        ) AS order_count
    FROM customers c
    WHERE c.name LIKE ?
       OR c.phone LIKE ?
       OR COALESCE(c.email, \'\') LIKE ?
    ORDER BY c.id DESC
';

$st = $pdo->prepare($sql);
$like = '%' . $q . '%';
$st->execute([$like, $like, $like]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý khách hàng - Phone Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">PHONE STORE</a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light btn-sm" href="orders.php">Đơn hàng</a>
            <a class="btn btn-outline-light btn-sm" href="index.php">Trang chủ</a>
        </div>
    </div>
</nav>

<main class="container py-4">
    <h1 class="h3 mb-3">Quản lý khách hàng</h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <?= $editing ? 'Cập nhật khách hàng' : 'Thêm khách hàng' ?>
                </div>

                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= e($editing['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label class="form-label">Họ tên *</label>
                            <input
                                required
                                maxlength="150"
                                class="form-control"
                                name="full_name"
                                value="<?= e($editing['name'] ?? '') ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại *</label>
                            <input
                                required
                                maxlength="20"
                                class="form-control"
                                name="phone"
                                value="<?= e($editing['phone'] ?? '') ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input
                                type="email"
                                maxlength="150"
                                class="form-control"
                                name="email"
                                value="<?= e($editing['email'] ?? '') ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input
                                maxlength="255"
                                class="form-control"
                                name="address"
                                value="<?= e($editing['address'] ?? '') ?>"
                            >
                        </div>

                        <button class="btn btn-primary">
                            <?= $editing ? 'Cập nhật khách hàng' : 'Lưu khách hàng' ?>
                        </button>

                        <?php if ($editing): ?>
                            <a class="btn btn-outline-secondary" href="customers.php">Hủy</a>
                        <?php endif; ?>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-lg-8">
            <form class="input-group mb-3" method="get">
                <input
                    class="form-control"
                    name="q"
                    value="<?= e($q) ?>"
                    placeholder="Tìm tên, điện thoại, email"
                >
                <button class="btn btn-dark" type="submit">Tìm</button>
                <?php if ($q !== ''): ?>
                    <a class="btn btn-outline-secondary" href="customers.php">Xóa lọc</a>
                <?php endif; ?>
            </form>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Liên hệ</th>
                                <th>Đơn</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= (int)$row['id'] ?></td>
                                    <td>
                                        <?= e($row['name']) ?>
                                        <div class="small text-muted">
                                            <?= e($row['address'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= e($row['phone']) ?>
                                        <div class="small text-muted">
                                            <?= e($row['email'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td><?= (int)$row['order_count'] ?></td>
                                    <td class="text-nowrap">
                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="customers.php?edit=<?= (int)$row['id'] ?>"
                                        >Sửa</a>

                                        <form
                                            method="post"
                                            class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa khách hàng này?')"
                                        >
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted p-4">
                                        Chưa có khách hàng.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
