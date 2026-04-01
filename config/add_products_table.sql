-- ============================================================
-- Pomegranate Website – Products Table Migration
-- INF1005 Web Systems and Technologies
-- Run after database_setup.sql
-- ============================================================
-- Usage: mysql -u root -p pomegranate_db < add_products_table.sql
-- ============================================================

USE pomegranate_db;

CREATE TABLE IF NOT EXISTS products (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255)   NOT NULL,
    description    TEXT           DEFAULT NULL,
    price          DECIMAL(10,2)  NOT NULL,
    old_price      DECIMAL(10,2)  DEFAULT NULL,          -- original price for sale badges
    image_url      VARCHAR(500)   DEFAULT NULL,
    category       VARCHAR(100)   DEFAULT NULL,
    badge          VARCHAR(50)    DEFAULT NULL,           -- e.g. "New", "Sale", "Best Seller"
    model          VARCHAR(100)   DEFAULT NULL,           -- e.g. "Argus Pro", "Guinevere SE"
    stock_quantity INT            NOT NULL DEFAULT 0,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- Seed sample products for demo/testing
-- ============================================================
INSERT INTO products (name, description, price, old_price, image_url, category, badge, model, stock_quantity)
VALUES
    ('Pomegranate Argus',    'Flagship smartphone with triple camera system and 6.7" AMOLED display.',  1299.99, NULL,    'assets/argus.jpg',      'Smartphones', 'New',         'Argus',        15),
    ('Pomegranate Guinevere','Mid-range powerhouse with 5000mAh battery and 6.4" display.',             849.00,  999.00,  'assets/guinevere.jpg',  'Smartphones', 'Sale',        'Guinevere',    8),
    ('Pomegranate Esmeralda','Compact flagship with 6.1" Super Retina display and ceramic build.',      1099.00, NULL,    'assets/esmeralda.jpg',  'Smartphones', 'Best Seller', 'Esmeralda',    12),
    ('Pomegranate Sora',     'Entry-level smartphone with long battery life and durable design.',        399.00,  NULL,    'assets/sora.jpg',       'Smartphones', NULL,          'Sora',         20),
    ('Pomegranate Thamuz',   'Gaming-focused phone with 144Hz display and advanced cooling system.',   999.00,  1199.00, 'assets/thamuz.jpg',     'Smartphones', 'Sale',        'Thamuz',       3),
    ('Pomegranate Wireless Buds', 'True wireless earbuds with active noise cancellation.',              199.99,  NULL,    NULL,                    'Accessories', 'New',         NULL,           2)
ON DUPLICATE KEY UPDATE name = VALUES(name);
