<?php
/**
 * Generate Password Hashes for Demo Accounts
 * Run this file once to get correct password hashes
 */

// Password: Admin@123
$admin_password = 'Admin@123';
$admin_hash = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]);

// Password: Customer@123
$customer_password = 'Customer@123';
$customer_hash = password_hash($customer_password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Copy these SQL statements and run in phpMyAdmin:\n\n";

echo "-- Update admin password\n";
echo "UPDATE users SET password_hash = '$admin_hash' WHERE username = 'admin';\n\n";

echo "-- Update customer1 password\n";
echo "UPDATE users SET password_hash = '$customer_hash' WHERE username = 'customer1';\n\n";

echo "\n\nOr create new accounts:\n\n";

echo "-- Delete old accounts\n";
echo "DELETE FROM users WHERE username IN ('admin', 'customer1');\n\n";

echo "-- Insert admin account\n";
echo "INSERT INTO users (username, email, password_hash, role, mfa_enabled) VALUES\n";
echo "('admin', 'admin@betainvestments.com', '$admin_hash', 'admin', 0);\n\n";

echo "-- Insert customer account\n";
echo "INSERT INTO users (username, email, password_hash, role, mfa_enabled) VALUES\n";
echo "('customer1', 'customer1@example.com', '$customer_hash', 'customer', 0);\n";
?>
