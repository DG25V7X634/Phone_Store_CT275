-- Chạy một lần trong MySQL Workbench sau khi chọn database phone_store.
USE phone_store;

-- Ảnh sản phẩm
ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL;

-- Các bảng nghiệp vụ riêng của bài tập, đặt tên ps_* để không phá bảng cũ.
CREATE TABLE IF NOT EXISTS ps_customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(30) NOT NULL,
  address VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ps_customers_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ps_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Mới','Đang xử lý','Đã giao','Đã hủy') NOT NULL DEFAULT 'Mới',
  total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  note VARCHAR(500) NULL,
  CONSTRAINT fk_ps_orders_customer FOREIGN KEY (customer_id) REFERENCES ps_customers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ps_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(15,2) NOT NULL,
  CONSTRAINT fk_ps_items_order FOREIGN KEY (order_id) REFERENCES ps_orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ps_items_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
