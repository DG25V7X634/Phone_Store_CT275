# Phone Store — CT275 Công nghệ Web

Học kỳ 1, năm học 2026–2027. Website quản lý cửa hàng điện thoại sử dụng PHP 8.5+, MySQL 8 và Bootstrap 5.

## Cấu hình nhanh trên Windows (CMD)

1. Sao chép `.env.example` thành `.env`, sau đó sửa `DB_PASSWORD` thành mật khẩu MySQL trên máy của bạn. Không đưa `.env` lên GitHub.
2. Trong MySQL Workbench, chọn database `phone_store`, mở và chạy toàn bộ `database/updates_ct275.sql` **một lần**.
3. Mở CMD tại thư mục `public` và chạy:
   ```bat
   php -S 127.0.0.1:8000
   ```
4. Truy cập `http://127.0.0.1:8000/index.php`.

## Chức năng

- Tổng quan cửa hàng và thống kê cập nhật trực tiếp bằng Server-Sent Events (SSE): `live_events.php`.
- Quản lý sản phẩm: tìm kiếm, thêm, sửa, xóa; tải ảnh JPG/PNG/WebP tối đa 3 MB tại `product_image_upload.php`.
- Quản lý khách hàng: thêm, sửa, xóa, tìm kiếm; không cho xóa khách hàng đang có đơn.
- Quản lý đơn hàng: tạo đơn với khách hàng và sản phẩm, kiểm tra tồn kho, trừ tồn kho trong transaction; cập nhật trạng thái đơn.
- HTTP caching ở `products.php`: ETag và phản hồi `304 Not Modified` khi nội dung không đổi.
- PDO prepared statements, mã hóa đầu ra HTML, CSRF token cho các thao tác ghi.

## Tính năng nâng cao để trình bày

1. **Upload tệp:** `product_image_upload.php`; kiểm tra MIME thực tế bằng `finfo`, giới hạn kích thước và tên tệp ngẫu nhiên.
2. **HTTP caching:** mở Developer Tools → Network → tải lại `products.php`; kiểm tra header `ETag`, `Cache-Control`; lần yêu cầu có `If-None-Match` phù hợp có thể nhận `304`.
3. **SSE:** mở trang chủ và Developer Tools → Network → `live_events.php`; dữ liệu `stats` được gửi khi thống kê thay đổi, heartbeat mỗi 5 giây.

## Git và GitHub

Lịch sử Git cục bộ được khởi tạo trong thư mục dự án. Để đưa lên GitHub, tạo một repository trống rồi chạy:

```bat
git remote add origin https://github.com/USERNAME/REPOSITORY.git
git branch -M main
git push -u origin main
```

Thay `USERNAME/REPOSITORY` bằng repo của bạn. Kiểm tra `git status` trước khi push và tuyệt đối không commit `.env`.

## Thông tin học phần

- Tên dự án: Website quản lý cửa hàng điện thoại Phone Store
- MSSV: DG25V7X634
- Sinh viên: Đặng Thanh Vân
- Học phần: CT275 — Công nghệ Web
