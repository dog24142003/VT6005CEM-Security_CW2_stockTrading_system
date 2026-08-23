<?php
/**
 * Portfolio Page
 * VT6005CEM CW2 - Stock Trading System
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

set_security_headers();
start_secure_session();
check_login();

$user = get_user_by_id($_SESSION['user_id']);
$portfolio = get_user_portfolio($_SESSION['user_id']);

// Calculate totals
$total_cost = 0;
$total_value = 0;
$total_profit_loss = 0;

foreach ($portfolio as $holding) {
    $total_cost += $holding['cost_basis'];
    $total_value += $holding['current_value'];
    $total_profit_loss += $holding['profit_loss'];
}

$total_return = $total_cost > 0 ? ($total_profit_loss / $total_cost * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio - Beta Investments</title>
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
                <li><a href="portfolio.php" class="active">My Portfolio</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="settings.php">Settings</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Portfolio Summary -->
        <div class="card">
            <h3>💼 Portfolio Summary</h3>
            <div class="stock-info">
                <div class="stock-info-item">
                    <label>Total Cost Basis</label>
                    <value><?php echo format_currency($total_cost); ?></value>
                </div>
                <div class="stock-info-item">
                    <label>Current Value</label>
                    <value><?php echo format_currency($total_value); ?></value>
                </div>
                <div class="stock-info-item">
                    <label>Total P&L</label>
                    <value class="<?php echo $total_profit_loss >= 0 ? 'price-up' : 'price-down'; ?>">
                        <?php echo format_currency($total_profit_loss); ?>
                    </value>
                </div>
                <div class="stock-info-item">
                    <label>Total Return</label>
                    <value class="<?php echo $total_return >= 0 ? 'price-up' : 'price-down'; ?>">
                        <?php echo format_percentage($total_return); ?>
                    </value>
                </div>
            </div>
        </div>

        <!-- Holdings Table -->
        <?php if (!empty($portfolio)): ?>
        <div class="card">
            <h3>📈 My Holdings</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Company</th>
                            <th>Quantity</th>
                            <th>Avg Price</th>
                            <th>Current Price</th>
                            <th>Cost Basis</th>
                            <th>Current Value</th>
                            <th>P&L</th>
                            <th>Return %</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portfolio as $holding): ?>
                        <tr>
                            <td><strong><?php echo escape_html($holding['symbol']); ?></strong></td>
                            <td><?php echo escape_html($holding['company_name']); ?></td>
                            <td><?php echo number_format($holding['quantity']); ?></td>
                            <td><?php echo format_currency($holding['average_price']); ?></td>
                            <td><?php echo format_currency($holding['current_price']); ?></td>
                            <td><?php echo format_currency($holding['cost_basis']); ?></td>
                            <td><?php echo format_currency($holding['current_value']); ?></td>
                            <td class="<?php echo $holding['profit_loss'] >= 0 ? 'price-up' : 'price-down'; ?>">
                                <?php echo format_currency($holding['profit_loss']); ?>
                            </td>
                            <td class="<?php echo $holding['return_percent'] >= 0 ? 'price-up' : 'price-down'; ?>">
                                <?php echo format_percentage($holding['return_percent']); ?>
                            </td>
                            <td>
                                <a href="trade.php?stock_id=<?php echo $holding['stock_id']; ?>"
                                   class="btn btn-primary"
                                   style="padding: 8px 15px; font-size: 14px;">Trade</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <p style="text-align: center; color: #666; padding: 40px 0;">
                You don't have any holdings yet.<br>
                <a href="stocks.php" class="btn btn-primary" style="margin-top: 20px;">Browse Stocks</a>
            </p>
        </div>
        <?php endif; ?>

        <footer>
            <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
        </footer>
    </div>
</body>
</html>
