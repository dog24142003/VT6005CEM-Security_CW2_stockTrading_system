<?php
/**
 * Security Functions
 * VT6005CEM CW2 - Stock Trading System
 */

/**
 * Security Feature 1: Password Hashing
 * Uses bcrypt algorithm (PASSWORD_BCRYPT)
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Security Feature 2: CSRF Token Generation and Validation
 */
function generate_csrf_token() {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // If token already exists in session and not expired, reuse it
    if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        if (time() - $_SESSION['csrf_token_time'] < 3600) {
            return $_SESSION['csrf_token'];
        }
    }

    // Generate random token
    $token = bin2hex(random_bytes(32));
    $user_id = $_SESSION['user_id'];
    $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    // Store in database
    $stmt = $pdo->prepare("INSERT INTO csrf_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $token, $expires_at]);

    // Store in session
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();

    return $token;
}

function verify_csrf_token($token) {
    global $pdo;

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Check session token first (faster)
    if ($token !== $_SESSION['csrf_token']) {
        return false;
    }

    // Verify in database
    $stmt = $pdo->prepare("SELECT token FROM csrf_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['user_id'], $token]);

    return $stmt->rowCount() > 0;
}

/**
 * Security Feature 3: XSS Prevention - Output Encoding
 */
function escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Security Feature 4: Input Validation
 */
function validate_username($username) {
    // Only alphanumeric and underscore, 3-50 characters
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_password($password) {
    // At least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Security Feature 5: Session Management
 */
function start_secure_session() {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);  // Requires HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);

    session_start();

    // Regenerate session ID to prevent fixation
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        destroy_session();
        header('Location: login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function destroy_session() {
    global $pdo;

    // Delete from database
    if (isset($_SESSION['session_db_id'])) {
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->execute([$_SESSION['session_db_id']]);
    }

    // Destroy session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Security Feature 6: Security Logging
 */
function log_security_event($event_type, $event_description, $user_id = null) {
    global $pdo;

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, event_type, event_description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $event_type, $event_description, $ip_address, $user_agent]);
}

/**
 * Security Feature 7: Account Lockout After Failed Attempts
 */
function check_account_locked($username) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT account_locked, failed_login_attempts, last_failed_login FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    // Check if account is locked
    if ($user['account_locked']) {
        // Check if lockout time has passed
        $lockout_time = strtotime($user['last_failed_login']) + LOCKOUT_TIME;
        if (time() > $lockout_time) {
            // Reset lockout
            reset_failed_attempts($username);
            return false;
        }
        return true;
    }

    return false;
}

function increment_failed_attempts($username) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT failed_login_attempts FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $attempts = $user['failed_login_attempts'] + 1;
        $locked = ($attempts >= MAX_LOGIN_ATTEMPTS) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ?, account_locked = ?, last_failed_login = NOW() WHERE username = ?");
        $stmt->execute([$attempts, $locked, $username]);

        if ($locked) {
            log_security_event('account_locked', "Account locked after $attempts failed login attempts", null);
        }
    }
}

function reset_failed_attempts($username) {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, account_locked = 0 WHERE username = ?");
    $stmt->execute([$username]);
}

/**
 * Security Feature 11: Rate Limiting
 * Prevents brute-force attacks by limiting requests per IP address
 */
function check_rate_limit($endpoint, $max_attempts = 10, $time_window = 60) {
    global $pdo;

    $ip_address = get_client_ip();

    // Clean up old records (older than time window)
    $cleanup_time = date('Y-m-d H:i:s', time() - $time_window);
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE endpoint = ? AND last_attempt < ?");
    $stmt->execute([$endpoint, $cleanup_time]);

    // Check if IP is currently blocked
    $stmt = $pdo->prepare("SELECT blocked_until FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND blocked_until > NOW()");
    $stmt->execute([$ip_address, $endpoint]);
    $blocked = $stmt->fetch();

    if ($blocked) {
        $blocked_until = strtotime($blocked['blocked_until']);
        $remaining = $blocked_until - time();
        log_security_event('rate_limit_blocked', "Rate limit blocked for endpoint: $endpoint (IP: $ip_address)", null);
        return [
            'allowed' => false,
            'blocked_until' => $blocked['blocked_until'],
            'remaining_seconds' => $remaining
        ];
    }

    // Check current attempts in time window
    $stmt = $pdo->prepare("SELECT attempts, first_attempt FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND last_attempt >= ?");
    $stmt->execute([$ip_address, $endpoint, $cleanup_time]);
    $record = $stmt->fetch();

    if ($record) {
        $attempts = $record['attempts'] + 1;

        if ($attempts > $max_attempts) {
            // Block this IP for 15 minutes
            $blocked_until = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = ?, blocked_until = ?, last_attempt = NOW() WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$attempts, $blocked_until, $ip_address, $endpoint]);

            log_security_event('rate_limit_exceeded', "Rate limit exceeded for endpoint: $endpoint (IP: $ip_address, Attempts: $attempts)", null);

            return [
                'allowed' => false,
                'blocked_until' => $blocked_until,
                'remaining_seconds' => 900
            ];
        } else {
            // Increment attempts
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = ?, last_attempt = NOW() WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$attempts, $ip_address, $endpoint]);
        }
    } else {
        // First attempt in time window
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, attempts, first_attempt, last_attempt) VALUES (?, ?, 1, NOW(), NOW())");
        $stmt->execute([$ip_address, $endpoint]);
    }

    return [
        'allowed' => true,
        'attempts' => $record ? $attempts : 1,
        'max_attempts' => $max_attempts
    ];
}

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Security Feature 8: Role-Based Access Control
 */
function check_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }
}

function check_admin() {
    check_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Security Feature 9: Secure Headers
 */
function set_security_headers() {
    // HSTS - Force HTTPS
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

    // CSP - Content Security Policy (Allow Google Charts API for QR code)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https://chart.googleapis.com https://api.qrserver.com;");

    // X-Frame-Options - Prevent clickjacking
    header("X-Frame-Options: DENY");

    // X-Content-Type-Options - Prevent MIME sniffing
    header("X-Content-Type-Options: nosniff");

    // X-XSS-Protection
    header("X-XSS-Protection: 1; mode=block");

    // Referrer-Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Permissions-Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

/**
 * Security Feature 10: SQL Injection Prevention Helper
 * Note: Always use prepared statements (already implemented in queries)
 */
function get_user_by_username($username) {
    global $pdo;

    // Using prepared statement - SQL injection prevention
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function get_user_by_id($user_id) {
    global $pdo;

    // Using prepared statement - SQL injection prevention
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Generate random session ID
 */
function generate_session_id() {
    return bin2hex(random_bytes(64));
}

/**
 * Store session in database
 */
function store_session($user_id) {
    global $pdo;

    $session_id = generate_session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $expires_at = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    $stmt = $pdo->prepare("INSERT INTO sessions (session_id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$session_id, $user_id, $ip_address, $user_agent, $expires_at]);

    $_SESSION['session_db_id'] = $session_id;

    return $session_id;
}

/**
 * Get client IP address (handles proxy)
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
