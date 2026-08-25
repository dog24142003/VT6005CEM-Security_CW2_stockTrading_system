<?php
/**
 * MFA Verification Page
 * VT6005CEM CW2 - Stock Trading System
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/mfa.php';

set_security_headers();
start_secure_session();

// check if MFA verification is needed
if (!isset($_SESSION['mfa_user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// handle MFA code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize_input($_POST['code'] ?? '');

    if (empty($code)) {
        $error = 'Please enter the 6-digit code.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Code must be 6 digits.';
    } else {
        $user_id = $_SESSION['mfa_user_id'];
        $secret = get_mfa_secret($user_id);

        if (verify_mfa_code($secret, $code)) {
            // code is correct
            $user = get_user_by_id($user_id);

            // regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['initiated'] = true;

            // clear temporary MFA session data
            unset($_SESSION['mfa_user_id']);
            unset($_SESSION['mfa_username']);

            // save session to database
            store_session($user['user_id']);

            // update last login time
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE user_id = ?");
            $stmt->execute([get_client_ip(), $user['user_id']]);

            log_security_event('mfa_success', "MFA verification successful", $user['user_id']);

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid code. Please try again.';
            log_security_event('mfa_failed', "MFA verification failed", $user_id);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFA Verification - Beta Investments</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <header style="text-align: center; margin-bottom: 30px;">
                <h1>🔐 Two-Factor Authentication</h1>
                <p>Enter the code from your authenticator app</p>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo escape_html($error); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3>Verify Identity</h3>
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Logged in as: <strong><?php echo escape_html($_SESSION['mfa_username']); ?></strong>
                </p>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="code">6-Digit Code:</label>
                        <input type="text"
                               id="code"
                               name="code"
                               required
                               pattern="\d{6}"
                               maxlength="6"
                               placeholder="000000"
                               autocomplete="off"
                               style="text-align: center; font-size: 24px; letter-spacing: 5px;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Verify</button>
                </form>

                <p style="text-align: center; margin-top: 20px;">
                    <a href="logout.php">Cancel and logout</a>
                </p>
            </div>

            <div class="alert alert-info" style="margin-top: 20px;">
                <strong>📱 How to use:</strong><br>
                1. Open your Google Authenticator app<br>
                2. Find "Beta Investments" entry<br>
                3. Enter the 6-digit code shown
            </div>

            <footer>
                <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
                <p style="font-size: 12px; margin-top: 10px;">
                    🔒 Multi-Factor Authentication (TOTP)
                </p>
            </footer>
        </div>
    </div>

    <script>
        // Auto-submit when 6 digits entered
        document.getElementById('code').addEventListener('input', function(e) {
            if (e.target.value.length === 6) {
                document.querySelector('form').submit();
            }
        });

        // Only allow numbers
        document.getElementById('code').addEventListener('keypress', function(e) {
            if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
