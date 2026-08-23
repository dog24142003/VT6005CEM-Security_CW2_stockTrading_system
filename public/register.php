<?php
/**
 * Registration Page
 * VT6005CEM CW2 - Stock Trading System
 * Security Features: Input Validation, Password Hashing, XSS Prevention, CSRF Protection
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!validate_username($username)) {
        $error = 'Username must be 3-50 characters and contain only letters, numbers, and underscores.';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email address.';
    } elseif (!validate_password($password)) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, number and special character (@$!%*?&).';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Create user (with password hashing)
            $user_id = create_user($username, $email, $password, 'customer');
            $success = 'Registration successful! You can now login.';

            // Clear form
            $username = '';
            $email = '';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Beta Investments Trading System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <header style="text-align: center; margin-bottom: 30px;">
                <h1>🔐 Beta Investments</h1>
                <p>Create Your Account</p>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo escape_html($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo escape_html($success); ?>
                    <a href="index.php">Click here to login</a>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3>Register</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text"
                               id="username"
                               name="username"
                               required
                               pattern="[a-zA-Z0-9_]{3,50}"
                               title="3-50 characters, letters, numbers, and underscores only"
                               value="<?php echo isset($username) ? escape_html($username) : ''; ?>">
                        <small style="color: #666;">3-50 characters, letters, numbers, and underscores only</small>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               value="<?php echo isset($email) ? escape_html($email) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}"
                               title="At least 8 characters with uppercase, lowercase, number and special character">
                        <small style="color: #666;">Min 8 chars: uppercase, lowercase, number, special char (@$!%*?&)</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Register</button>
                </form>

                <p style="text-align: center; margin-top: 20px;">
                    Already have an account? <a href="index.php">Login here</a>
                </p>
            </div>

            <div class="alert alert-info" style="margin-top: 20px;">
                <strong>Password Requirements:</strong><br>
                ✓ At least 8 characters<br>
                ✓ One uppercase letter (A-Z)<br>
                ✓ One lowercase letter (a-z)<br>
                ✓ One number (0-9)<br>
                ✓ One special character (@$!%*?&)
            </div>

            <footer>
                <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
                <p style="font-size: 12px; margin-top: 10px;">
                    🔒 Secured with: Input Validation, Password Hashing (bcrypt), XSS Prevention
                </p>
            </footer>
        </div>
    </div>

    <script>
        // Client-side password match validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
