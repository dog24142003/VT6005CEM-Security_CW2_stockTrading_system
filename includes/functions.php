<?php
/**
 * Helper Functions
 * VT6005CEM CW2 - Stock Trading System
 */

/**
 * Get all stocks
 */
function get_all_stocks() {
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM stocks ORDER BY symbol ASC");
    return $stmt->fetchAll();
}

/**
 * Search stocks by symbol or company name
 */
function search_stocks($keyword) {
    global $pdo;

    $keyword = "%$keyword%";
    $stmt = $pdo->prepare("SELECT * FROM stocks WHERE symbol LIKE ? OR company_name LIKE ? ORDER BY symbol ASC");
    $stmt->execute([$keyword, $keyword]);
    return $stmt->fetchAll();
}

/**
 * Get stock by ID
 */
function get_stock_by_id($stock_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM stocks WHERE stock_id = ?");
    $stmt->execute([$stock_id]);
    return $stmt->fetch();
}

/**
 * Get stock by symbol
 */
function get_stock_by_symbol($symbol) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM stocks WHERE symbol = ?");
    $stmt->execute([$symbol]);
    return $stmt->fetch();
}

/**
 * Execute stock transaction (buy/sell)
 */
function execute_transaction($user_id, $stock_id, $type, $quantity, $price) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        $total_amount = $quantity * $price;

        // Insert transaction record
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, stock_id, transaction_type, quantity, price, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $stock_id, $type, $quantity, $price, $total_amount]);

        // Update portfolio
        if ($type === 'buy') {
            // Check if user already owns this stock
            $stmt = $pdo->prepare("SELECT * FROM user_portfolios WHERE user_id = ? AND stock_id = ?");
            $stmt->execute([$user_id, $stock_id]);
            $portfolio = $stmt->fetch();

            if ($portfolio) {
                // Update existing holding
                $new_quantity = $portfolio['quantity'] + $quantity;
                $new_average = (($portfolio['average_price'] * $portfolio['quantity']) + ($price * $quantity)) / $new_quantity;

                $stmt = $pdo->prepare("UPDATE user_portfolios SET quantity = ?, average_price = ? WHERE user_id = ? AND stock_id = ?");
                $stmt->execute([$new_quantity, $new_average, $user_id, $stock_id]);
            } else {
                // Create new holding
                $stmt = $pdo->prepare("INSERT INTO user_portfolios (user_id, stock_id, quantity, average_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $stock_id, $quantity, $price]);
            }
        } else { // sell
            $stmt = $pdo->prepare("SELECT * FROM user_portfolios WHERE user_id = ? AND stock_id = ?");
            $stmt->execute([$user_id, $stock_id]);
            $portfolio = $stmt->fetch();

            if (!$portfolio || $portfolio['quantity'] < $quantity) {
                throw new Exception("Insufficient shares to sell");
            }

            $new_quantity = $portfolio['quantity'] - $quantity;

            if ($new_quantity > 0) {
                $stmt = $pdo->prepare("UPDATE user_portfolios SET quantity = ? WHERE user_id = ? AND stock_id = ?");
                $stmt->execute([$new_quantity, $user_id, $stock_id]);
            } else {
                // Remove from portfolio if all sold
                $stmt = $pdo->prepare("DELETE FROM user_portfolios WHERE user_id = ? AND stock_id = ?");
                $stmt->execute([$user_id, $stock_id]);
            }
        }

        $pdo->commit();

        // Log transaction
        $stock = get_stock_by_id($stock_id);
        log_security_event('transaction', "$type $quantity shares of {$stock['symbol']} at $price", $user_id);

        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Transaction failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's portfolio
 */
function get_user_portfolio($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM v_user_portfolio_summary WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get user's transaction history
 */
function get_user_transactions($user_id, $limit = 50) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM v_user_transaction_history WHERE user_id = ? LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get all users (admin only)
 */
function get_all_users() {
    global $pdo;

    $stmt = $pdo->query("SELECT user_id, username, email, role, mfa_enabled, account_locked, last_login, created_at FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Create new user
 */
function create_user($username, $email, $password, $role = 'customer') {
    global $pdo;

    try {
        // Validate inputs
        if (!validate_username($username)) {
            throw new Exception("Invalid username format");
        }

        if (!validate_email($email)) {
            throw new Exception("Invalid email format");
        }

        if (!validate_password($password)) {
            throw new Exception("Password must be at least 8 characters with uppercase, lowercase, number and special character");
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception("Username already exists");
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists");
        }

        // Hash password
        $password_hash = hash_password($password);

        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash, $role]);

        $user_id = $pdo->lastInsertId();

        // Log event
        log_security_event('user_created', "New user registered: $username", $user_id);

        return $user_id;
    } catch (Exception $e) {
        error_log("User creation failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Update user role (admin only)
 */
function update_user_role($user_id, $role) {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    return $stmt->execute([$role, $user_id]);
}

/**
 * Delete user (admin only)
 */
function delete_user($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * Get security logs (admin only)
 */
function get_security_logs($limit = 100) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM v_security_events_summary LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get user's security logs
 */
function get_user_security_logs($user_id, $limit = 50) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM security_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Format currency
 */
function format_currency($amount, $currency = 'HKD') {
    if ($currency === 'HKD') {
        return 'HK$ ' . number_format($amount, 2);
    } else {
        return 'USD $ ' . number_format($amount, 2);
    }
}

/**
 * Format percentage
 */
function format_percentage($value) {
    $sign = $value >= 0 ? '+' : '';
    return $sign . number_format($value, 2) . '%';
}

/**
 * Format date/time
 */
function format_datetime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}

/**
 * Check if stock exists
 */
function stock_exists($stock_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stocks WHERE stock_id = ?");
    $stmt->execute([$stock_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Validate transaction quantity
 */
function validate_quantity($quantity) {
    return is_numeric($quantity) && $quantity > 0 && $quantity == floor($quantity);
}
