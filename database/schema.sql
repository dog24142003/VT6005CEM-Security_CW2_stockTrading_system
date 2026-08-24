-- ================================================================================
-- Stock Trading System - Database Schema
-- VT6005CEM CW2 - Beta Investments
-- ================================================================================

-- Create database
CREATE DATABASE IF NOT EXISTS stock_trading_system
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE stock_trading_system;

-- ================================================================================
-- Table 1: users
-- Stores user accounts with authentication information
-- ================================================================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',

    -- Multi-factor authentication
    mfa_secret VARCHAR(32) DEFAULT NULL,
    mfa_enabled BOOLEAN DEFAULT 0,

    -- Account security
    account_locked BOOLEAN DEFAULT 0,
    failed_login_attempts INT DEFAULT 0,
    last_failed_login DATETIME DEFAULT NULL,

    -- Session tracking
    last_login DATETIME DEFAULT NULL,
    last_ip VARCHAR(45) DEFAULT NULL,

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ================================================================================
-- Table 2: stocks
-- Stores stock information for trading
-- ================================================================================
CREATE TABLE stocks (
    stock_id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(10) UNIQUE NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    previous_close DECIMAL(10,2) DEFAULT NULL,
    change_percent DECIMAL(5,2) DEFAULT 0.00,
    volume BIGINT DEFAULT 0,
    market_cap VARCHAR(20) DEFAULT NULL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_symbol (symbol)
) ENGINE=InnoDB;

-- ================================================================================
-- Table 3: user_portfolios
-- Tracks user stock holdings
-- ================================================================================
CREATE TABLE user_portfolios (
    portfolio_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    stock_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    average_price DECIMAL(10,2) NOT NULL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(stock_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_stock (user_id, stock_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- ================================================================================
-- Table 4: transactions
-- Records all buy/sell transactions
-- ================================================================================
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    stock_id INT NOT NULL,
    transaction_type ENUM('buy', 'sell') NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(stock_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_stock_id (stock_id),
    INDEX idx_transaction_date (transaction_date)
) ENGINE=InnoDB;

-- ================================================================================
-- Table 5: security_logs
-- Audit trail for security events
-- ================================================================================
CREATE TABLE security_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ================================================================================
-- Table 6: sessions
-- Secure session management
-- ================================================================================
CREATE TABLE sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- ================================================================================
-- Table 7: csrf_tokens
-- CSRF protection tokens
-- ================================================================================
CREATE TABLE csrf_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- ================================================================================
-- Insert Sample Data
-- ================================================================================

-- Sample admin user (password: Admin@123)
INSERT INTO users (username, email, password_hash, role, mfa_enabled) VALUES
('admin', 'admin@betainvestments.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0);

-- Sample customer user (password: Customer@123)
INSERT INTO users (username, email, password_hash, role, mfa_enabled) VALUES
('customer1', 'customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 0);

-- Sample stocks (Hong Kong & US markets)
INSERT INTO stocks (symbol, company_name, current_price, previous_close, change_percent, volume, market_cap) VALUES
('0700.HK', 'Tencent Holdings Limited', 385.00, 380.00, 1.32, 12500000, 'HK$ 3.7T'),
('0388.HK', 'Hong Kong Exchanges and Clearing', 285.50, 288.00, -0.87, 3200000, 'HK$ 360B'),
('0941.HK', 'China Mobile Limited', 78.50, 77.80, 0.90, 8900000, 'HK$ 1.6T'),
('AAPL', 'Apple Inc.', 175.50, 174.20, 0.75, 52000000, 'USD 2.8T'),
('GOOGL', 'Alphabet Inc.', 140.25, 138.90, 0.97, 28000000, 'USD 1.8T'),
('MSFT', 'Microsoft Corporation', 380.00, 378.50, 0.40, 23000000, 'USD 2.9T'),
('TSLA', 'Tesla Inc.', 245.80, 248.30, -1.01, 98000000, 'USD 780B'),
('AMZN', 'Amazon.com Inc.', 145.60, 144.20, 0.97, 45000000, 'USD 1.5T'),
('META', 'Meta Platforms Inc.', 325.40, 320.10, 1.66, 18000000, 'USD 850B'),
('NVDA', 'NVIDIA Corporation', 485.20, 478.50, 1.40, 35000000, 'USD 1.2T');

-- Sample security log entry
INSERT INTO security_logs (user_id, event_type, event_description, ip_address) VALUES
(1, 'account_created', 'Admin account created during database initialization', '127.0.0.1');

-- ================================================================================
-- Create Views for Reporting
-- ================================================================================

-- View: User transaction history with stock details
CREATE VIEW v_user_transaction_history AS
SELECT
    t.transaction_id,
    t.user_id,
    u.username,
    s.symbol,
    s.company_name,
    t.transaction_type,
    t.quantity,
    t.price,
    t.total_amount,
    t.transaction_date
FROM transactions t
JOIN users u ON t.user_id = u.user_id
JOIN stocks s ON t.stock_id = s.stock_id
ORDER BY t.transaction_date DESC;

-- View: User portfolio summary
CREATE VIEW v_user_portfolio_summary AS
SELECT
    up.user_id,
    u.username,
    s.stock_id,
    s.symbol,
    s.company_name,
    up.quantity,
    up.average_price,
    s.current_price,
    (s.current_price - up.average_price) AS price_change,
    ((s.current_price - up.average_price) / up.average_price * 100) AS return_percent,
    (up.quantity * s.current_price) AS current_value,
    (up.quantity * up.average_price) AS cost_basis,
    (up.quantity * (s.current_price - up.average_price)) AS profit_loss
FROM user_portfolios up
JOIN users u ON up.user_id = u.user_id
JOIN stocks s ON up.stock_id = s.stock_id
WHERE up.quantity > 0;

-- View: Security event summary
CREATE VIEW v_security_events_summary AS
SELECT
    sl.log_id,
    sl.user_id,
    u.username,
    sl.event_type,
    sl.event_description,
    sl.ip_address,
    sl.created_at
FROM security_logs sl
LEFT JOIN users u ON sl.user_id = u.user_id
ORDER BY sl.created_at DESC;

-- ================================================================================
-- Stored Procedures
-- ================================================================================

-- Procedure: Clean expired sessions
DELIMITER //
CREATE PROCEDURE sp_clean_expired_sessions()
BEGIN
    DELETE FROM sessions WHERE expires_at < NOW();
END //
DELIMITER ;

-- Procedure: Clean expired CSRF tokens
DELIMITER //
CREATE PROCEDURE sp_clean_expired_csrf_tokens()
BEGIN
    DELETE FROM csrf_tokens WHERE expires_at < NOW();
END //
DELIMITER ;

-- Procedure: Reset failed login attempts
DELIMITER //
CREATE PROCEDURE sp_reset_failed_login_attempts()
BEGIN
    UPDATE users
    SET failed_login_attempts = 0, account_locked = 0
    WHERE last_failed_login < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    AND account_locked = 1;
END //
DELIMITER ;

-- ================================================================================
-- Events for automatic cleanup
-- ================================================================================

SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS evt_clean_sessions
ON SCHEDULE EVERY 1 HOUR
DO CALL sp_clean_expired_sessions();

CREATE EVENT IF NOT EXISTS evt_clean_csrf_tokens
ON SCHEDULE EVERY 1 HOUR
DO CALL sp_clean_expired_csrf_tokens();

CREATE EVENT IF NOT EXISTS evt_reset_failed_logins
ON SCHEDULE EVERY 1 HOUR
DO CALL sp_reset_failed_login_attempts();

-- ================================================================================
-- Database Setup Complete
-- ================================================================================

SELECT 'Database schema created successfully!' AS Status;
