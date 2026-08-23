<?php
/**
 * Transactions History Page
 * VT6005CEM CW2 - Stock Trading System
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

set_security_headers();
start_secure_session();
check_login();

$user = get_user_by_id($_SESSION['user_id']);
$transactions = get_user_transactions($_SESSION['user_id'], 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Beta Investments</title>
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
                <li><a href="transactions.php" class="active">Transactions</a></li>
                <li><a href="settings.php">Settings</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

        <div class="card">
            <h3>📜 Transaction History</h3>

            <?php if (!empty($transactions)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Symbol</th>
                            <th>Company</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?php echo format_datetime($tx['transaction_date']); ?></td>
                            <td>
                                <span class="security-badge <?php echo $tx['transaction_type'] === 'buy' ? 'enabled' : 'disabled'; ?>">
                                    <?php echo strtoupper($tx['transaction_type']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo escape_html($tx['symbol']); ?></strong></td>
                            <td><?php echo escape_html($tx['company_name']); ?></td>
                            <td><?php echo number_format($tx['quantity']); ?></td>
                            <td><?php echo format_currency($tx['price']); ?></td>
                            <td><?php echo format_currency($tx['total_amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p style="text-align: center; color: #666; padding: 40px 0;">
                No transactions yet.<br>
                <a href="trade.php" class="btn btn-primary" style="margin-top: 20px;">Start Trading</a>
            </p>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
        </footer>
    </div>
</body>
</html>
