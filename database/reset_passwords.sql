-- ================================================================================
-- Reset Demo Account Passwords
-- Run this in phpMyAdmin SQL tab
-- ================================================================================

-- Delete existing demo accounts
DELETE FROM users WHERE username IN ('admin', 'customer1');

-- Insert admin account with password: Admin@123
INSERT INTO users (username, email, password_hash, role, mfa_enabled) VALUES
('admin', 'admin@betainvestments.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0);

-- Insert customer account with password: Customer@123
INSERT INTO users (username, email, password_hash, role, mfa_enabled) VALUES
('customer1', 'customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 0);

-- Verify accounts created
SELECT user_id, username, email, role, mfa_enabled FROM users WHERE username IN ('admin', 'customer1');
