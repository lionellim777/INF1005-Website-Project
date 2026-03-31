-- ============================================================
-- Pomegranate E-Commerce Database Setup
-- INF1005 Web Systems and Technologies
-- ============================================================

CREATE DATABASE IF NOT EXISTS pomegranate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pomegranate;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'employee', 'admin') DEFAULT 'customer',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- ============================================================
-- PRODUCTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    category_id INT,
    image VARCHAR(255) DEFAULT 'assets/cat.jpg',
    featured TINYINT(1) DEFAULT 0,
    created_by INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- CART TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================================
-- ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- ============================================================
-- SEED DATA
-- NOTE: All seed passwords are "Password1!"
-- Hash generated with: password_hash('Password1!', PASSWORD_DEFAULT)
-- ============================================================
INSERT INTO categories (name, slug, description) VALUES
('Smartphones', 'smartphones', 'Cutting-edge smartphones'),
('Laptops', 'laptops', 'High-performance laptops'),
('Accessories', 'accessories', 'Premium tech accessories'),
('Wearables', 'wearables', 'Smart wearable devices')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed users (password = "Password1!" for all)
INSERT INTO users (username, email, password_hash, role, full_name, is_active) VALUES
('admin', 'admin@pomegranate.com', '$2y$10$3P9pYhOQCQhEdCTRRiHNAeelFMCN0X.B2VfFVxEfK.TIHYJa4J5a2', 'admin', 'System Admin', 1),
('employee1', 'employee@pomegranate.com', '$2y$10$3P9pYhOQCQhEdCTRRiHNAeelFMCN0X.B2VfFVxEfK.TIHYJa4J5a2', 'employee', 'Alex Chen', 1),
('johndoe', 'john@example.com', '$2y$10$3P9pYhOQCQhEdCTRRiHNAeelFMCN0X.B2VfFVxEfK.TIHYJa4J5a2', 'customer', 'John Doe', 1),
('janedoe', 'jane@example.com', '$2y$10$3P9pYhOQCQhEdCTRRiHNAeelFMCN0X.B2VfFVxEfK.TIHYJa4J5a2', 'customer', 'Jane Doe', 1)
ON DUPLICATE KEY UPDATE username=VALUES(username);

-- Seed products
INSERT INTO products (name, description, price, sale_price, stock, category_id, image, featured, created_by) VALUES
('NeoPulse X1', 'The flagship smartphone with 200MP camera, 6.8" AMOLED display, and 5G connectivity. Powered by the latest Snapdragon chip.', 1299.00, 1099.00, 45, 1, 'assets/phone.jpg', 1, 1),
('NeoPulse X1 Pro', 'Professional-grade smartphone with titanium frame, satellite connectivity, and 7000mAh battery that lasts 3 days.', 1599.00, NULL, 20, 1, 'assets/phone-berries.jpg', 1, 1),
('NeoPulse A5', 'Mid-range powerhouse with flagship features at an accessible price point. 120Hz display and 108MP camera.', 699.00, 599.00, 80, 1, 'assets/phone-blue.jpg', 0, 1),
('UltraBook Pro 16', '16" OLED display, Intel Core Ultra 9, 64GB RAM, 2TB NVMe SSD. The ultimate workstation laptop.', 2499.00, NULL, 15, 2, 'assets/cat.jpg', 1, 1),
('SlimAir 13', 'Ultra-thin 13" laptop at just 890g. Perfect for professionals on the move with all-day battery life.', 1199.00, 999.00, 30, 2, 'assets/cat.jpg', 0, 1),
('ArcWatch Ultra', 'Smartwatch with ECG, blood glucose monitoring, and 14-day battery. Premium titanium case.', 599.00, NULL, 60, 4, 'assets/cat.jpg', 1, 1),
('SoundPods Pro', 'True wireless earbuds with 40dB ANC, spatial audio, and 36-hour total battery life.', 299.00, 249.00, 120, 3, 'assets/cat.jpg', 0, 1),
('MagCharge 3-in-1', 'Wireless charging pad for phone, watch, and earbuds simultaneously. 30W fast charging.', 89.00, NULL, 200, 3, 'assets/cat.jpg', 0, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed sample orders
INSERT INTO orders (user_id, total, status, shipping_address) VALUES
(3, 1348.00, 'delivered', '123 Tech Street, Singapore 123456'),
(3, 599.00, 'shipped', '123 Tech Street, Singapore 123456'),
(4, 2498.00, 'processing', '456 Innovation Ave, Singapore 654321'),
(4, 299.00, 'pending', '456 Innovation Ave, Singapore 654321'),
(3, 89.00, 'delivered', '123 Tech Street, Singapore 123456');

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 1099.00),
(1, 8, 1, 249.00),
(2, 6, 1, 599.00),
(3, 4, 1, 2499.00),
(4, 7, 1, 249.00),
(5, 8, 1, 89.00);
