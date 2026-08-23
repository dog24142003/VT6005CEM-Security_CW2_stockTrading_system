<?php
/**
 * Dashboard
 * VT6005CEM CW2 - Stock Trading System
 * Security Features: Session Management, RBAC, CSRF Protection
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

// Set security headers
set_security_headers();

// Start secure session
start_secure_session();

// Check login
check_login();

// Get user info
$user = get_user_by_id($_SESSION['user_id']);

// Get user portfolio
$portfolio = get_user_portfolio($_SESSION['user_id']);

// Get recent transactions
$recent_transactions = get_user_transactions($_SESSION['user_id'], 10);

// Calculate portfolio totals
$total_value = 0;
$total_profit_loss = 0;
foreach ($portfolio as $holding) {
    $total_value += $holding['current_value'];
    $total_profit_loss += $holding['profit_loss'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Beta Investments</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Beta Investments Trading System</h1>
            <div class="user-info">
                Welcome, <strong><?php echo escape_html($user['username']); ?></strong>
                (<?php echo escape_html($user['role']); ?>)
                <?php if ($user['mfa_enabled']): ?>
                    <span class="security-badge enabled">MFA Enabled</span>
                <?php endif; ?>
            </div>
        </header>

        <nav>
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="stocks.php">Browse Stocks</a></li>
                <li><a href="trade.php">Trade</a></li>
                <li><a href="portfolio.php">My Portfolio</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="settings.php">Settings</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

        <div class="card-grid">
            <!-- Portfolio Summary Card -->
            <div class="card">
                <h3>💼 Portfolio Summary</h3>
                <div class="stock-info">
                    <div class="stock-info-item">
                        <label>Total Holdings</label>
                        <value><?php echo count($portfolio); ?> stocks</value>
                    </div>
                    <div class="stock-info-item">
                        <label>Total Value</label>
                        <value><?php echo format_currency($total_value); ?></value>
                    </div>
                    <div class="stock-info-item">
                        <label>Total P&L</label>
                        <value class="<?php echo $total_profit_loss >= 0 ? 'price-up' : 'price-down'; ?>">
                            <?php echo format_currency($total_profit_loss); ?>
                        </value>
                    </div>
                    <div class="stock-info-item">
                        <label>Return</label>
                        <value class="<?php echo $total_profit_loss >= 0 ? 'price-up' : 'price-down'; ?>">
                            <?php
                            $cost_basis = $total_value - $total_profit_loss;
                            $return_pct = $cost_basis > 0 ? ($total_profit_loss / $cost_basis * 100) : 0;
                            echo format_percentage($return_pct);
                            ?>
                        </value>
                    </div>
                </div>
                <a href="portfolio.php" class="btn btn-primary">View Full Portfolio</a>
            </div>

            <!-- Quick Stats Card -->
            <div class="card">
                <h3>📈 Quick Stats</h3>
                <div class="stock-info">
                    <div class="stock-info-item">
                        <label>Recent Transactions</label>
                        <value><?php echo count($recent_transactions); ?></value>
                    </div>
                    <div class="stock-info-item">
                        <label>Account Status</label>
                        <value>
                            <?php if ($user['account_locked']): ?>
                                <span class="security-badge locked">Locked</span>
                            <?php else: ?>
                                <span class="security-badge enabled">Active</span>
                            <?php endif; ?>
                        </value>
                    </div>
                    <div class="stock-info-item">
                        <label>Last Login</label>
                        <value style="font-size: 14px;">
                            <?php echo $user['last_login'] ? format_datetime($user['last_login']) : 'First login'; ?>
                        </value>
                    </div>
                    <div class="stock-info-item">
                        <label>Member Since</label>
                        <value style="font-size: 14px;">
                            <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                        </value>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Holdings -->
        <?php if (!empty($portfolio)): ?>
        <div class="card">
            <h3>🏆 Top Holdings</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Company</th>
                            <th>Quantity</th>
                            <th>Avg Price</th>
                            <th>Current Price</th>
                            <th>Current Value</th>
                            <th>P&L</th>
                            <th>Return %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Show top 5 holdings by value
                        $top_holdings = array_slice($portfolio, 0, 5);
                        foreach ($top_holdings as $holding):
                        ?>
                        <tr>
                            <td><strong><?php echo escape_html($holding['symbol']); ?></strong></td>
                            <td><?php echo escape_html($holding['company_name']); ?></td>
                            <td><?php echo number_format($holding['quantity']); ?></td>
                            <td><?php echo format_currency($holding['average_price']); ?></td>
                            <td><?php echo format_currency($holding['current_price']); ?></td>
                            <td><?php echo format_currency($holding['current_value']); ?></td>
                            <td class="<?php echo $holding['profit_loss'] >= 0 ? 'price-up' : 'price-down'; ?>">
                                <?php echo format_currency($holding['profit_loss']); ?>
                            </td>
                            <td class="<?php echo $holding['return_percent'] >= 0 ? 'price-up' : 'price-down'; ?>">
                                <?php echo format_percentage($holding['return_percent']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <h3>📊 Your Portfolio</h3>
            <p style="text-align: center; color: #666; padding: 40px 0;">
                You don't have any holdings yet.<br>
                <a href="stocks.php" class="btn btn-primary" style="margin-top: 20px;">Browse Stocks</a>
            </p>
        </div>
        <?php endif; ?>

        <!-- Recent Transactions -->
        <?php if (!empty($recent_transactions)): ?>
        <div class="card">
            <h3>🕒 Recent Transactions</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Symbol</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $tx): ?>
                        <tr>
                            <td><?php echo format_datetime($tx['transaction_date']); ?></td>
                            <td>
                                <span class="security-badge <?php echo $tx['transaction_type'] === 'buy' ? 'enabled' : 'disabled'; ?>">
                                    <?php echo strtoupper($tx['transaction_type']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo escape_html($tx['symbol']); ?></strong></td>
                            <td><?php echo number_format($tx['quantity']); ?></td>
                            <td><?php echo format_currency($tx['price']); ?></td>
                            <td><?php echo format_currency($tx['total_amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="transactions.php" class="btn btn-secondary" style="margin-top: 15px;">View All Transactions</a>
        </div>
        <?php endif; ?>

        <footer>
            <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
            <p style="font-size: 12px; margin-top: 10px;">
                Session expires in: <span id="session-timer"></span>
            </p>
        </footer>
    </div>

    <script>
        // Session timeout countdown
        let sessionTimeout = <?php echo SESSION_LIFETIME; ?>;
        let lastActivity = <?php echo $_SESSION['last_activity']; ?>;

        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const elapsed = now - lastActivity;
            const remaining = sessionTimeout - elapsed;

            if (remaining <= 0) {
                window.location.href = 'index.php?timeout=1';
            } else {
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                document.getElementById('session-timer').textContent =
                    minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>
</html>
