<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/common.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Phương thức không được hỗ trợ.');
}
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) redirect_products('ID sản phẩm không hợp lệ.', 'danger');
$stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
$stmt->execute(['id'=>$id]);
redirect_products($stmt->rowCount() ? 'Đã xóa sản phẩm.' : 'Không tìm thấy sản phẩm để xóa.', $stmt->rowCount() ? 'success' : 'warning');
