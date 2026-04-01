-- ============================================================
-- Pomegranate Website - Database Setup
-- INF1005 Web Systems and Technologies
-- ============================================================
-- Run this script once to create the database and tables.
-- Usage: mysql -u root -p < database_setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS pomegranate_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pomegranate_db;

-- ============================================================
-- 1. ROLES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    role_id     INT AUTO_INCREMENT PRIMARY KEY,
    role_name   VARCHAR(50)  NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed the four required roles
INSERT INTO roles (role_id, role_name, description) VALUES
    (1, 'guest',       'Temporary guest with read-only access'),
    (2, 'user',        'Registered logged-in user'),
    (3, 'employee',    'Employee with limited edit privileges'),
    (4, 'admin',       'Site administrator with full access')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

-- ============================================================
-- 2. USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    first_name     VARCHAR(100)  NOT NULL,
    last_name      VARCHAR(100)  NOT NULL,
    email          VARCHAR(255)  NOT NULL UNIQUE,
    phone          VARCHAR(20)   DEFAULT NULL,
    password_hash  VARCHAR(255)  NOT NULL,
    role_id        INT           NOT NULL DEFAULT 2,
    avatar_url     VARCHAR(500)  DEFAULT NULL,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    email_verified TINYINT(1)    NOT NULL DEFAULT 0,
    last_login     DATETIME      DEFAULT NULL,
    failed_logins  INT           NOT NULL DEFAULT 0,
    locked_until   DATETIME      DEFAULT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- ============================================================
-- 3. USER SESSIONS TABLE (for session management)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id   VARCHAR(128)  PRIMARY KEY,
    user_id      INT           NOT NULL,
    ip_address   VARCHAR(45)   NOT NULL,
    user_agent   VARCHAR(512)  DEFAULT NULL,
    device_label VARCHAR(100)  DEFAULT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at   DATETIME      NOT NULL,
    is_active    TINYINT(1)    NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. PASSWORD RESET TOKENS
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  DATETIME     NOT NULL,
    used        TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. PURCHASE HISTORY (for profile dashboard)
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    order_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT            NOT NULL,
    total_amount DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    status       ENUM('pending','processing','shipped','delivered','cancelled')
                                NOT NULL DEFAULT 'pending',
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    item_id      INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT           NOT NULL,
    product_name VARCHAR(255)  NOT NULL,
    quantity     INT           NOT NULL DEFAULT 1,
    unit_price   DECIMAL(10,2) NOT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. ACTIVITY LOG (audit trail)
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_log (
    log_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    details     TEXT         DEFAULT NULL,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 7. SEED DEFAULT ADMIN ACCOUNT
--    Email: admin@pomegranate.com  |  Password: Admin@1234
-- ============================================================
INSERT INTO users (first_name, last_name, email, password_hash, role_id, is_active, email_verified)
VALUES (
    'Site', 'Admin', 'admin@pomegranate.com',
    '$2b$12$WCPUTspgnsK59gFYDYkigOOvCDhdplriSjQTIrlmzclzQTx0eA34q',
    4, 1, 1
) ON DUPLICATE KEY UPDATE
    first_name     = VALUES(first_name),
    last_name      = VALUES(last_name),
    password_hash  = VALUES(password_hash),
    role_id        = VALUES(role_id),
    is_active      = VALUES(is_active),
    email_verified = VALUES(email_verified),
    failed_logins  = 0,
    locked_until   = NULL;

-- Seed a test employee account
-- Email: employee@pomegranate.com  |  Password: Employee@1234
INSERT INTO users (first_name, last_name, email, password_hash, role_id, is_active, email_verified)
VALUES (
    'Jane', 'Staff', 'employee@pomegranate.com',
    '$2b$12$Q3UXBU1wS5iNJeHEZ0Z3xOv4DFEtKuJgszK6aTi1JZ1QGskoqYjZ.',
    3, 1, 1
) ON DUPLICATE KEY UPDATE
    first_name     = VALUES(first_name),
    last_name      = VALUES(last_name),
    password_hash  = VALUES(password_hash),
    role_id        = VALUES(role_id),
    is_active      = VALUES(is_active),
    email_verified = VALUES(email_verified),
    failed_logins  = 0,
    locked_until   = NULL;

-- Seed a test regular user account
-- Email: user@pomegranate.com  |  Password: User@1234
INSERT INTO users (first_name, last_name, email, password_hash, role_id, is_active, email_verified)
VALUES (
    'John', 'Doe', 'user@pomegranate.com',
    '$2b$12$ino28wavFU76uoep7GZ2gepq.Mdo0jul1dve5u.rfLYmQEqB0BSUO',
    2, 1, 1
) ON DUPLICATE KEY UPDATE
    first_name     = VALUES(first_name),
    last_name      = VALUES(last_name),
    password_hash  = VALUES(password_hash),
    role_id        = VALUES(role_id),
    is_active      = VALUES(is_active),
    email_verified = VALUES(email_verified),
    failed_logins  = 0,
    locked_until   = NULL;

-- Seed sample orders for the admin account for demo
INSERT INTO orders (user_id, total_amount, status, created_at) VALUES
    (1, 1299.99, 'delivered', '2025-01-15 10:30:00'),
    (1,  849.00, 'shipped',   '2025-02-20 14:15:00'),
    (1,  199.99, 'processing','2025-03-10 09:00:00'),
    (3,  549.00, 'delivered', '2025-02-01 11:00:00'),
    (3, 1299.99, 'shipped',   '2025-03-05 16:45:00');

INSERT INTO order_items (order_id, product_name, quantity, unit_price) VALUES
    (1, 'Pomegranate Phone Pro',     1, 1299.99),
    (2, 'Pomegranate Tablet Air',    1,  849.00),
    (3, 'Pomegranate Wireless Buds', 1,  199.99),
    (4, 'Pomegranate Watch SE',      1,  549.00),
    (5, 'Pomegranate Phone Pro',     1, 1299.99);

-- ============================================================
-- 8. ADDITIONAL DUMMY ACCOUNTS FOR LOGIN TESTING
-- ============================================================
-- Email: qa.admin@pomegranate.com      | Password: AdminTest@123   | Role: admin
-- Email: qa.employee@pomegranate.com   | Password: EmployeeTest@123| Role: employee
-- Email: qa.user1@pomegranate.com      | Password: UserTest@123    | Role: user
-- Email: qa.user2@pomegranate.com      | Password: UserTest2@123   | Role: user
-- Email: qa.user3@pomegranate.com      | Password: UserTest3@123   | Role: user
INSERT INTO users (first_name, last_name, email, phone, password_hash, role_id, is_active, email_verified)
VALUES
    ('QA', 'Admin',    'qa.admin@pomegranate.com',    '90000001', '$2y$12$ZpV6KilC/oiz.NwYDchNB..4nlvuE/PblabLjmpepLX4s23FO6xQu', 4, 1, 1),
    ('QA', 'Employee', 'qa.employee@pomegranate.com', '90000002', '$2y$12$diHxnM8BtcSriZXGPkVvcedGI45MCte.1oPwSEwer8F0v5QTqF56a', 3, 1, 1),
    ('Test', 'User One',   'qa.user1@pomegranate.com', '90000003', '$2y$12$30uZPG5mOecjUiwnplnqieoB8BKBkvFrP.9ACh.hquJnPmVGmDvMC', 2, 1, 1),
    ('Test', 'User Two',   'qa.user2@pomegranate.com', '90000004', '$2y$12$KbgHqm3bJOIy.GKXZUY2e.49VRX3cIvvpksxwTXOgEqqBvgowG.ye', 2, 1, 1),
    ('Test', 'User Three', 'qa.user3@pomegranate.com', '90000005', '$2y$12$vxg3mZnduWOsKG8wsdHEcOHCFqcMy0OWyZoyv3YFFv.HDqHSdDyxy', 2, 1, 1)
ON DUPLICATE KEY UPDATE
    first_name     = VALUES(first_name),
    last_name      = VALUES(last_name),
    phone          = VALUES(phone),
    password_hash  = VALUES(password_hash),
    role_id        = VALUES(role_id),
    is_active      = VALUES(is_active),
    email_verified = VALUES(email_verified),
    failed_logins  = 0,
    locked_until   = NULL;
