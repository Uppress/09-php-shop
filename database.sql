CREATE DATABASE IF NOT EXISTS swd_shop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE swd_shop;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(170) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL CHECK (price > 0),
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(20) NOT NULL UNIQUE,
  customer_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  status ENUM('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL CHECK (quantity > 0),
  PRIMARY KEY (order_id, product_id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

INSERT INTO categories (name, slug) VALUES
  ('Phones', 'phones'),
  ('Laptops', 'laptops'),
  ('Accessories', 'accessories')
ON DUPLICATE KEY UPDATE name = VALUES(name);
