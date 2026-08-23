<?php
/**
 * Settings Page
 * VT6005CEM CW2 - Stock Trading System
 * Security Features: MFA Setup/Disable, CSRF Protection
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';
require_once '../includes/mfa.php';

set_security_headers();
start_secure_session();
check_login();

$user = get_user_by_id($_SESSION['user_id']);
$error = '';
$success = '';
$mfa_setup_mode = false;
$mfa_secret = '';
$qr_code_url = '';

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Handle MFA Enable Request
if (isset($_POST['enable_mfa'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        // Generate MFA secret
        $mfa_secret = generate_mfa_secret();
        $qr_code_url = get_mfa_qr_code($user['username'], $mfa_secret);
        $_SESSION['temp_mfa_secret'] = $mfa_secret;
        $mfa_setup_mode = true;
    }
}

// Handle MFA Verification and Enable
if (isset($_POST['verify_mfa'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $code = sanitize_input($_POST['code'] ?? '');
        $secret = $_SESSION['temp_mfa_secret'] ?? '';

        if (empty($secret)) {
            $error = 'MFA setup session expired. Please start again.';
        } elseif (verify_mfa_code($secret, $code)) {
            // Enable MFA
            if (enable_mfa($user['user_id'], $secret)) {
                unset($_SESSION['temp_mfa_secret']);
                $success = 'Multi-Factor Authentication enabled successfully!';
                log_security_event('mfa_enabled', 'User enabled MFA', $user['user_id']);
                $user = get_user_by_id($_SESSION['user_id']); // Refresh user data
            } else {
                $error = 'Failed to enable MFA. Please try again.';
            }
        } else {
            $error = 'Invalid code. Please try again.';
            $mfa_setup_mode = true;
            $mfa_secret = $secret;
            $qr_code_url = get_mfa_qr_code($user['username'], $mfa_secret);
        }
    }
}

// Handle MFA Disable
if (isset($_POST['disable_mfa'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        if (disable_mfa($user['user_id'])) {
            $success = 'Multi-Factor Authentication disabled.';
            log_security_event('mfa_disabled', 'User disabled MFA', $user['user_id']);
            $user = get_user_by_id($_SESSION['user_id']); // Refresh user data
        } else {
            $error = 'Failed to disable MFA.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Beta Investments</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Beta Investments Trading System</h1>
            <div class="user-info">
                Welcome, <strong><?php echo escape_html($user['username']); ?></strong>
            </div>
        </header>

        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="stocks.php">Browse Stocks</a></li>
                <li><a href="trade.php">Trade</a></li>
                <li><a href="portfolio.php">My Portfolio</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="settings.php" class="active">Settings</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

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

        <!-- Account Information -->
        <div class="card">
            <h3>👤 Account Information</h3>
            <div class="stock-info">
                <div class="stock-info-item">
                    <label>Username</label>
                    <value><?php echo escape_html($user['username']); ?></value>
                </div>
                <div class="stock-info-item">
                    <label>Email</label>
                    <value style="font-size: 14px;"><?php echo escape_html($user['email']); ?></value>
                </div>
                <div class="stock-info-item">
                    <label>Role</label>
                    <value><?php echo escape_html(ucfirst($user['role'])); ?></value>
                </div>
                <div class="stock-info-item">
                    <label>Member Since</label>
                    <value style="font-size: 14px;"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></value>
                </div>
            </div>
        </div>

        <!-- MFA Settings -->
        <?php if ($mfa_setup_mode): ?>
        <!-- MFA Setup Mode -->
        <div class="card">
            <h3>🔐 Set Up Multi-Factor Authentication</h3>
            <div class="mfa-setup">
                <p><strong>Step 1:</strong> Scan this QR code with Google Authenticator</p>
                <img src="<?php echo $qr_code_url; ?>" alt="MFA QR Code">

                <p><strong>Step 2:</strong> Or manually enter this secret key:</p>
                <div class="mfa-secret"><?php echo escape_html($mfa_secret); ?></div>

                <p><strong>Step 3:</strong> Enter the 6-digit code from your app:</p>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf_token); ?>">

                    <div class="form-group">
                        <input type="text"
                               name="code"
                               placeholder="000000"
                               required
                               pattern="\d{6}"
                               maxlength="6"
                               autocomplete="off"
                               style="text-align: center; font-size: 24px; letter-spacing: 5px;">
                    </div>

                    <button type="submit" name="verify_mfa" class="btn btn-primary btn-full">
                        Verify and Enable MFA
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- MFA Status -->
        <div class="card">
            <h3>🔐 Multi-Factor Authentication (MFA)</h3>
            <p style="margin-bottom: 20px;">
                Add an extra layer of security to your account with two-factor authentication.
            </p>

            <div class="stock-info">
                <div class="stock-info-item">
                    <label>MFA Status</label>
                    <value>
                        <?php if ($user['mfa_enabled']): ?>
                            <span class="security-badge enabled">Enabled</span>
                        <?php else: ?>
                            <span class="security-badge disabled">Disabled</span>
                        <?php endif; ?>
                    </value>
                </div>
            </div>

            <form method="POST" action="" style="margin-top: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf_token); ?>">

                <?php if ($user['mfa_enabled']): ?>
                    <button type="submit"
                            name="disable_mfa"
                            class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to disable MFA? This will make your account less secure.');">
                        Disable MFA
                    </button>
                <?php else: ?>
                    <button type="submit" name="enable_mfa" class="btn btn-primary">
                        Enable MFA
                    </button>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>

        <!-- Security Logs -->
        <div class="card">
            <h3>🔒 Recent Security Activity</h3>
            <?php
            $logs = get_user_security_logs($user['user_id'], 10);
            if (!empty($logs)):
            ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Event</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo format_datetime($log['created_at']); ?></td>
                            <td><code><?php echo escape_html($log['event_type']); ?></code></td>
                            <td><?php echo escape_html($log['event_description']); ?></td>
                            <td><?php echo escape_html($log['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p style="text-align: center; color: #666; padding: 20px 0;">
                No security logs available.
            </p>
            <?php endif; ?>
        </div>

        <div class="alert alert-info">
            <strong>🔒 Security Features:</strong><br>
            ✓ <strong>Multi-Factor Authentication:</strong> TOTP-based (Google Authenticator compatible)<br>
            ✓ <strong>Security Logging:</strong> All authentication events tracked<br>
            ✓ <strong>Session Management:</strong> Automatic timeout after 15 minutes inactivity
        </div>

        <footer>
            <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
        </footer>
    </div>
</body>
</html>
