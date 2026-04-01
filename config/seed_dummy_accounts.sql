-- ============================================================
-- Pomegranate Website - Dummy Login Accounts
-- ============================================================
-- Usage:
--   mysql -u root -p pomegranate_db < seed_dummy_accounts.sql
-- ============================================================

USE pomegranate_db;

-- Email: qa.admin@pomegranate.com      | Password: AdminTest@123    | Role: admin
-- Email: qa.employee@pomegranate.com   | Password: EmployeeTest@123 | Role: employee
-- Email: qa.user1@pomegranate.com      | Password: UserTest@123     | Role: user
-- Email: qa.user2@pomegranate.com      | Password: UserTest2@123    | Role: user
-- Email: qa.user3@pomegranate.com      | Password: UserTest3@123    | Role: user
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
