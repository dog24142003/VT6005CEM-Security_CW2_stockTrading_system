<?php
/**
 * Admin Panel
 * VT6005CEM CW2 - Stock Trading System
 * Security Features: RBAC (Role-Based Access Control), Security Logs Viewer
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

set_security_headers();
start_secure_session();
check_admin(); // Only admins can access

$user = get_user_by_id($_SESSION['user_id']);
$all_users = get_all_users();
$security_logs = get_security_logs(100);

// Generate CSRF token
$csrf_token = generate_csrf_token();

$error = '';
$success = '';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $user_id = intval($_POST['user_id'] ?? 0);

        if ($user_id === $_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } elseif (delete_user($user_id)) {
            $success = 'User deleted successfully.';
            log_security_event('user_deleted', "Admin deleted user ID: $user_id", $_SESSION['user_id']);
            $all_users = get_all_users(); // Refresh
        } else {
            $error = 'Failed to delete user.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Beta Investments</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🔧 Admin Panel - Beta Investments</h1>
            <div class="user-info">
                Logged in as: <strong><?php echo escape_html($user['username']); ?></strong> (Admin)
            </div>
        </header>

        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="stocks.php">Browse Stocks</a></li>
                <li><a href="trade.php">Trade</a></li>
                <li><a href="portfolio.php">My Portfolio</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="admin.php" class="active">Admin Panel</a></li>
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

        <!-- User Management -->
        <div class="card">
            <h3>👥 User Management</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>MFA</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $u): ?>
                        <tr>
                            <td><?php echo $u['user_id']; ?></td>
                            <td><strong><?php echo escape_html($u['username']); ?></strong></td>
                            <td><?php echo escape_html($u['email']); ?></td>
                            <td>
                                <span class="security-badge <?php echo $u['role'] === 'admin' ? 'enabled' : 'disabled'; ?>">
                                    <?php echo strtoupper($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['mfa_enabled']): ?>
                                    <span class="security-badge enabled">ON</span>
                                <?php else: ?>
                                    <span class="security-badge disabled">OFF</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['account_locked']): ?>
                                    <span class="security-badge locked">LOCKED</span>
                                <?php else: ?>
                                    <span class="security-badge enabled">ACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px;">
                                <?php echo $u['last_login'] ? format_datetime($u['last_login']) : 'Never'; ?>
                            </td>
                            <td style="font-size: 12px;">
                                <?php echo date('Y-m-d', strtotime($u['created_at'])); ?>
                            </td>
                            <td>
                                <?php if ($u['user_id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf_token); ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                    <button type="submit"
                                            name="delete_user"
                                            class="btn btn-danger"
                                            style="padding: 5px 10px; font-size: 12px;"
                                            onclick="return confirm('Are you sure you want to delete user: <?php echo escape_html($u['username']); ?>?');">
                                        Delete
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 12px;">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Security Logs -->
        <div class="card">
            <h3>🔒 Security Event Logs</h3>
            <p style="margin-bottom: 15px; color: #666;">
                Showing last 100 security events. All authentication attempts and transactions are logged.
            </p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Event Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($security_logs as $log): ?>
                        <tr>
                            <td><?php echo $log['log_id']; ?></td>
                            <td style="font-size: 12px;"><?php echo format_datetime($log['created_at']); ?></td>
                            <td><?php echo $log['username'] ? escape_html($log['username']) : '<em>N/A</em>'; ?></td>
                            <td>
                                <code style="font-size: 11px; background: #f8f9fa; padding: 3px 6px; border-radius: 3px;">
                                    <?php echo escape_html($log['event_type']); ?>
                                </code>
                            </td>
                            <td style="font-size: 12px;"><?php echo escape_html($log['event_description']); ?></td>
                            <td style="font-size: 12px;"><?php echo escape_html($log['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="card">
            <h3>📊 System Statistics</h3>
            <div class="stock-info">
                <div class="stock-info-item">
                    <label>Total Users</label>
                    <value><?php echo count($all_users); ?></value>
                </div>
                <div class="stock-info-item">
                    <label>Admin Users</label>
                    <value>
                        <?php echo count(array_filter($all_users, fn($u) => $u['role'] === 'admin')); ?>
                    </value>
                </div>
                <div class="stock-info-item">
                    <label>MFA Enabled</label>
                    <value>
                        <?php echo count(array_filter($all_users, fn($u) => $u['mfa_enabled'])); ?>
                    </value>
                </div>
                <div class="stock-info-item">
                    <label>Locked Accounts</label>
                    <value>
                        <?php echo count(array_filter($all_users, fn($u) => $u['account_locked'])); ?>
                    </value>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>🔒 Admin Security Features:</strong><br>
            ✓ <strong>Role-Based Access Control:</strong> Only admin role can access this panel<br>
            ✓ <strong>Audit Trail:</strong> All admin actions are logged<br>
            ✓ <strong>User Management:</strong> View all users, delete accounts<br>
            ✓ <strong>Security Monitoring:</strong> Real-time security event viewer
        </div>

        <footer>
            <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
        </footer>
    </div>
</body>
</html>
