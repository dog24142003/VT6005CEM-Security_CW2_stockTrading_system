<?php
/**
 * Login Page
 * VT6005CEM CW2 - Stock Trading System
 * Security Features: CSRF Protection, Account Lockout, Security Logging, Prepared Statements
 */

require_once '../includes/config.php';
require_once '../includes/security.php';

// Set security headers
set_security_headers();

// Start secure session
start_secure_session();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check if account is locked
        if (check_account_locked($username)) {
            $error = 'Account is locked due to multiple failed login attempts. Please try again in 1 hour.';
            log_security_event('login_attempt_locked', "Login attempt on locked account: $username", null);
        } else {
            // Get user from database (using prepared statement - SQL Injection Prevention)
            $user = get_user_by_username($username);

            if ($user && verify_password($password, $user['password_hash'])) {
                // Password correct - reset failed attempts
                reset_failed_attempts($username);

                // Check if MFA is enabled
                if ($user['mfa_enabled']) {
                    // Store temporary user info for MFA verification
                    $_SESSION['mfa_user_id'] = $user['user_id'];
                    $_SESSION['mfa_username'] = $user['username'];

                    log_security_event('login_mfa_required', "Login successful, MFA verification required: $username", $user['user_id']);

                    header('Location: verify_mfa.php');
                    exit;
                } else {
                    // No MFA - login directly
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['initiated'] = true;

                    // Store session in database
                    store_session($user['user_id']);

                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE user_id = ?");
                    $stmt->execute([get_client_ip(), $user['user_id']]);

                    log_security_event('login_success', "Successful login: $username", $user['user_id']);

                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                // Invalid credentials
                if ($user) {
                    increment_failed_attempts($username);
                }

                $error = 'Invalid username or password.';
                log_security_event('login_failed', "Failed login attempt for username: $username", null);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Beta Investments Trading System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <header style="text-align: center; margin-bottom: 30px;">
                <h1>🔐 Beta Investments</h1>
                <p>Secure Stock Trading System</p>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo escape_html($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo escape_html($success); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3>Login</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text"
                               id="username"
                               name="username"
                               required
                               autocomplete="username"
                               value="<?php echo isset($username) ? escape_html($username) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Login</button>
                </form>

                <p style="text-align: center; margin-top: 20px;">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
            </div>

            <div class="alert alert-info" style="margin-top: 20px;">
                <strong>Demo Accounts:</strong><br>
                Admin: <code>admin</code> / <code>Admin@123</code><br>
                Customer: <code>customer1</code> / <code>Customer@123</code>
            </div>

            <footer>
                <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
                <p style="font-size: 12px; margin-top: 10px;">
                    🔒 Secured with: HTTPS, Password Hashing, Account Lockout, Security Logging
                </p>
            </footer>
        </div>
    </div>
</body>
</html>
