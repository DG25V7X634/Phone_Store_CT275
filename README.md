# Phone Store — CT275 Công nghệ Web

Website quản lý cửa hàng điện thoại sử dụng PHP 8.5 (có thể chạy PHP 8.2+), MySQL 8 và Bootstrap 5. PHP cần bật `pdo_mysql` và `fileinfo`; `mbstring` được dùng nếu có.

## Cấu hình trên Windows

1. Sao chép `.env.example` thành `.env`, sửa `DB_PASSWORD` theo mật khẩu MySQL trên máy. Không chia sẻ hoặc đưa `.env` lên GitHub.
2. Chạy toàn bộ `database/updates_ct275.sql` bằng MySQL Workbench hoặc phpMyAdmin. Script tạo database `phone_store` và mô hình đúng 05 bảng: `categories`, `products`, `customers`, `orders`, `order_items`.
3. Mở CMD tại thư mục `public`, chạy:
   ```bat
   php -S 127.0.0.1:8000
   ```
4. Truy cập `http://127.0.0.1:8000/index.php`.

## Mô hình dữ liệu 05 bảng

- `categories`: danh mục sản phẩm.
- `products`: sản phẩm; `category_id` là khóa ngoại tùy chọn đến `categories.id`.
- `customers`: khách hàng.
- `orders`: đơn hàng.
- `order_items`: chi tiết mặt hàng trong đơn.

Không sử dụng bảng `ps_*`; không tạo `order_details` song song với `order_items`. Giao diện PHP hiện tại chưa có màn hình CRUD danh mục; bảng `categories` và cột `products.category_id` là phần nền dữ liệu, `category_id` cho phép NULL nên không làm gián đoạn các chức năng hiện có.

## Chức năng hiện tại

- Trang tổng quan và thống kê cập nhật trực tiếp qua Server-Sent Events (SSE).
- Quản lý sản phẩm: tìm kiếm, thêm, sửa, xóa, upload ảnh.
- Quản lý khách hàng: thêm, sửa, xóa, tìm kiếm.
- Quản lý đơn hàng: tạo đơn, kiểm tra tồn kho, trừ tồn kho, cập nhật trạng thái và hoàn kho khi hủy đơn.
- PDO prepared statements, mã hóa đầu ra HTML và CSRF token cho thao tác ghi.

## Lưu ý kiểm thử

Cần chạy SQL và kiểm tra thực tế trên MySQL tại máy. `php -l` chỉ kiểm tra cú pháp PHP, không thay thế kiểm thử tích hợp. Sao lưu database trước khi chạy cập nhật trên CSDL cũ.
