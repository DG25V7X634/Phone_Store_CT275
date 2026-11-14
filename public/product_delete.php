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
try {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $deleted = $stmt->rowCount() === 1;
    redirect_products(
        $deleted ? 'Đã xóa sản phẩm.' : 'Không tìm thấy sản phẩm để xóa.',
        $deleted ? 'success' : 'warning'
    );
} catch (PDOException $e) {
    // Không xóa sản phẩm đã được sử dụng trong đơn hàng để giữ lịch sử giao dịch.
    if ($e->getCode() === '23000') {
        redirect_products('Không thể xóa sản phẩm vì sản phẩm đã xuất hiện trong đơn hàng. Hãy giữ sản phẩm để bảo toàn lịch sử.', 'warning');
    }

    error_log('Product deletion failed: ' . $e->getMessage());
    redirect_products('Không thể xóa sản phẩm do lỗi cơ sở dữ liệu.', 'danger');
}
