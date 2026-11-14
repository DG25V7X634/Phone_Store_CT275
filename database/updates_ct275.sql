-- PHONE STORE CT275 - MO HINH CSDL CHUAN 05 BANG
-- Tables: categories, products, customers, orders, order_items
-- No ps_* tables. order_items is the only order-detail table.
-- Safe for repeated execution; back up an existing database before migration.

CREATE DATABASE IF NOT EXISTS phone_store
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phone_store;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- category_id is nullable so the current product-management screen continues to work.
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  brand VARCHAR(100) NOT NULL,
  price DECIMAL(15,2) NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 0,
  description TEXT NULL,
  image_path VARCHAR(255) NULL,
  category_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_products_name (name),
  INDEX idx_products_brand (brand),
  INDEX idx_products_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(150) NULL,
  address VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customers_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Mới','Đang xử lý','Đã giao','Đã hủy') NOT NULL DEFAULT 'Mới',
  total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  note VARCHAR(500) NULL,
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(15,2) NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure the category relationship exists even when upgrading an older products table.
-- The stored procedure checks existing metadata before adding the column/constraint.
DROP PROCEDURE IF EXISTS ensure_products_category_relation;
DELIMITER //
CREATE PROCEDURE ensure_products_category_relation()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND COLUMN_NAME = 'category_id'
  ) THEN
    ALTER TABLE products ADD COLUMN category_id INT NULL;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND INDEX_NAME = 'idx_products_category'
  ) THEN
    ALTER TABLE products ADD INDEX idx_products_category (category_id);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND CONSTRAINT_NAME = 'fk_products_category'
  ) THEN
    ALTER TABLE products
      ADD CONSTRAINT fk_products_category
      FOREIGN KEY (category_id) REFERENCES categories(id)
      ON UPDATE CASCADE ON DELETE SET NULL;
  END IF;
END//
DELIMITER ;
CALL ensure_products_category_relation();
DROP PROCEDURE ensure_products_category_relation;

-- Verification: exactly five application tables.
SHOW TABLES;
