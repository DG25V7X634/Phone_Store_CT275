# Báo cáo rà soát và đồng bộ Phone Store – CT275

## 1. Kết luận về tiền tố ps_

Mô hình chuẩn không sử dụng bảng có tiền tố `ps_`. Không tạo hoặc truy vấn `ps_*`; không dùng song song `order_details` và `order_items`. Tên bảng chi tiết đơn hàng thống nhất là `order_items`.

Nếu CSDL MySQL cũ còn bảng `ps_*`, gói này không tự xóa chúng để tránh mất dữ liệu. Chỉ xóa thủ công sau khi sao lưu và xác nhận không còn dữ liệu cần giữ.

## 2. Mô hình CSDL chuẩn gồm đúng 05 bảng

| Bảng | Vai trò |
|---|---|
| `categories` | Danh mục sản phẩm |
| `products` | Sản phẩm, tồn kho, giá và ảnh; `category_id` liên kết danh mục |
| `customers` | Khách hàng |
| `orders` | Đơn hàng và trạng thái |
| `order_items` | Các sản phẩm thuộc từng đơn hàng |

Quan hệ: `products.category_id` → `categories.id`; `orders.customer_id` → `customers.id`; `order_items.order_id` → `orders.id`; `order_items.product_id` → `products.id`.

Lưu ý phạm vi: giao diện PHP hiện tại thao tác trực tiếp với bốn bảng `products`, `customers`, `orders`, `order_items`. Bảng `categories` và quan hệ `products.category_id` là phần dữ liệu nền của mô hình 05 bảng; giao diện CRUD danh mục chưa được triển khai. `category_id` cho phép NULL để các màn hình hiện có tiếp tục hoạt động.

## 3. Thành phần đã đồng bộ

- `database/updates_ct275.sql`: tạo đủ 05 bảng; có kiểm tra bổ sung quan hệ danh mục cho CSDL cũ.
- `public/orders.php`: sử dụng duy nhất `order_items`, không dò tên bảng PS hoặc `order_details`.
- `public/product_delete.php`: xử lý trường hợp sản phẩm đã được tham chiếu trong đơn hàng.
- `README.md`: hướng dẫn cài đặt và kiểm thử theo mô hình 05 bảng.
- Không đóng gói `.env` chứa mật khẩu thật.

## 4. Kiểm tra kỹ thuật

- Toàn bộ tệp PHP trong ZIP đã chạy qua `php -l` không có lỗi cú pháp (môi trường kiểm tra PHP 8.4).
- Đã kiểm tra gói ZIP và nội dung SQL.
- Đây chưa phải kiểm thử tích hợp MySQL trên máy người dùng. Cần chạy SQL và kiểm tra chức năng trên PHP 8.5/MySQL tại máy trước khi nộp.

## 5. Checklist kiểm thử tại máy

1. Sao lưu CSDL cũ.
2. Chạy `database/updates_ct275.sql` trong MySQL Workbench hoặc phpMyAdmin.
3. Xác nhận có đúng 05 bảng: categories, products, customers, orders, order_items.
4. Mở trang chủ, kiểm tra thêm/sửa/xóa sản phẩm.
5. Kiểm tra thêm/sửa/xóa khách hàng.
6. Tạo đơn hàng; xác nhận tồn kho giảm.
7. Hủy đơn; xác nhận tồn kho được hoàn lại.
8. Mở lại đơn đã hủy; xác nhận hệ thống kiểm tra tồn kho.
9. Không xóa bảng `ps_*` cũ nếu chưa sao lưu và kiểm tra dữ liệu.
