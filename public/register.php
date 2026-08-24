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
                        <div style="position: relative;">
                            <input type="password"
                                   id="password"
                                   name="password"
                                   required
                                   pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}"
                                   title="At least 8 characters with uppercase, lowercase, number and special character"
                                   style="padding-right: 45px;">
                            <button type="button"
                                    id="toggle-password"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                                           background: none; border: none; cursor: pointer; font-size: 20px;
                                           color: #666; padding: 0; width: 30px; height: 30px;">
                                👁️
                            </button>
                        </div>

                        <!-- Password Strength Meter -->
                        <div id="password-strength-meter" style="margin-top: 8px; display: none;">
                            <div style="display: flex; gap: 4px; margin-bottom: 5px;">
                                <div id="strength-bar-1" style="flex: 1; height: 6px; background: #ddd; border-radius: 3px; transition: all 0.3s;"></div>
                                <div id="strength-bar-2" style="flex: 1; height: 6px; background: #ddd; border-radius: 3px; transition: all 0.3s;"></div>
                                <div id="strength-bar-3" style="flex: 1; height: 6px; background: #ddd; border-radius: 3px; transition: all 0.3s;"></div>
                                <div id="strength-bar-4" style="flex: 1; height: 6px; background: #ddd; border-radius: 3px; transition: all 0.3s;"></div>
                            </div>
                            <small id="strength-text" style="font-weight: 600;"></small>
                        </div>

                        <small style="color: #666;">Min 8 chars: uppercase, lowercase, number, special char (@$!%*?&)</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <div style="position: relative;">
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   required
                                   style="padding-right: 45px;">
                            <button type="button"
                                    id="toggle-confirm-password"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                                           background: none; border: none; cursor: pointer; font-size: 20px;
                                           color: #666; padding: 0; width: 30px; height: 30px;">
                                👁️
                            </button>
                        </div>
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
        // Toggle Password Visibility
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        const toggleConfirmPassword = document.getElementById('toggle-confirm-password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });

        // Password Strength Meter
        const strengthMeter = document.getElementById('password-strength-meter');
        const strengthBars = [
            document.getElementById('strength-bar-1'),
            document.getElementById('strength-bar-2'),
            document.getElementById('strength-bar-3'),
            document.getElementById('strength-bar-4')
        ];
        const strengthText = document.getElementById('strength-text');

        passwordInput.addEventListener('input', function() {
            const password = this.value;

            if (password.length === 0) {
                strengthMeter.style.display = 'none';
                return;
            }

            strengthMeter.style.display = 'block';

            // Calculate password strength
            let strength = 0;
            let feedback = [];

            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;

            // Character type checks
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[@$!%*?&]/.test(password)) strength++;

            // Cap at 4
            strength = Math.min(strength, 4);

            // Reset all bars
            strengthBars.forEach(bar => {
                bar.style.background = '#ddd';
            });

            // Update bars and text based on strength
            let color, text;
            if (strength === 1) {
                color = '#e74c3c'; // Red
                text = 'Weak';
                strengthBars[0].style.background = color;
            } else if (strength === 2) {
                color = '#f39c12'; // Orange
                text = 'Fair';
                strengthBars[0].style.background = color;
                strengthBars[1].style.background = color;
            } else if (strength === 3) {
                color = '#f1c40f'; // Yellow
                text = 'Good';
                strengthBars[0].style.background = color;
                strengthBars[1].style.background = color;
                strengthBars[2].style.background = color;
            } else if (strength === 4) {
                color = '#27ae60'; // Green
                text = 'Strong';
                strengthBars.forEach(bar => bar.style.background = color);
            } else {
                color = '#95a5a6'; // Gray
                text = 'Too Short';
                strengthBars[0].style.background = color;
            }

            strengthText.textContent = text;
            strengthText.style.color = color;
        });

        // Client-side password match validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
