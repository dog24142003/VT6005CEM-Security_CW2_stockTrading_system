<?php
/**
 * Trade Page
 * VT6005CEM CW2 - Stock Trading System
 * Security Features: CSRF Protection, Input Validation, Prepared Statements
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

$error = '';
$success = '';
$selected_stock = null;

// Get stock if stock_id provided
if (isset($_GET['stock_id'])) {
    $stock_id = intval($_GET['stock_id']);
    $selected_stock = get_stock_by_id($stock_id);
}

// Generate CSRF token for form
$csrf_token = generate_csrf_token();

// Handle trade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stock_id = intval($_POST['stock_id'] ?? 0);
    $trade_type = sanitize_input($_POST['trade_type'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $csrf_token_post = $_POST['csrf_token'] ?? '';

    // CSRF Protection
    if (!verify_csrf_token($csrf_token_post)) {
        $error = 'Invalid CSRF token. Please refresh the page and try again.';
        log_security_event('csrf_failed', 'CSRF token validation failed on trade', $_SESSION['user_id']);
    } elseif (!stock_exists($stock_id)) {
        $error = 'Invalid stock selected.';
    } elseif (!in_array($trade_type, ['buy', 'sell'])) {
        $error = 'Invalid trade type.';
    } elseif (!validate_quantity($quantity)) {
        $error = 'Invalid quantity. Must be a positive integer.';
    } else {
        $stock = get_stock_by_id($stock_id);

        // Execute transaction
        if (execute_transaction($_SESSION['user_id'], $stock_id, $trade_type, $quantity, $stock['current_price'])) {
            $total = $quantity * $stock['current_price'];
            $success = "Successfully executed $trade_type order: $quantity shares of {$stock['symbol']} at " . format_currency($stock['current_price']) . " (Total: " . format_currency($total) . ")";

            // Regenerate CSRF token after successful transaction
            $csrf_token = generate_csrf_token();
        } else {
            $error = 'Transaction failed. Please try again or contact support.';
        }

        // Refresh stock data
        $selected_stock = get_stock_by_id($stock_id);
    }
}

// Get all stocks for dropdown
$all_stocks = get_all_stocks();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade - Beta Investments</title>
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
                <li><a href="trade.php" class="active">Trade</a></li>
                <li><a href="portfolio.php">My Portfolio</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="settings.php">Settings</a></li>
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
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="card-grid">
            <!-- Stock Selection & Info -->
            <div class="card">
                <h3>📈 Select Stock</h3>
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="stock_select">Choose Stock:</label>
                        <select id="stock_select" name="stock_id" onchange="this.form.submit()">
                            <option value="">-- Select a stock --</option>
                            <?php foreach ($all_stocks as $stock): ?>
                            <option value="<?php echo $stock['stock_id']; ?>"
                                    <?php echo $selected_stock && $selected_stock['stock_id'] == $stock['stock_id'] ? 'selected' : ''; ?>>
                                <?php echo escape_html($stock['symbol']); ?> - <?php echo escape_html($stock['company_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if ($selected_stock): ?>
                <div class="stock-info" style="margin-top: 20px;">
                    <div class="stock-info-item">
                        <label>Symbol</label>
                        <value><?php echo escape_html($selected_stock['symbol']); ?></value>
                    </div>
                    <div class="stock-info-item">
                        <label>Company</label>
                        <value style="font-size: 14px;"><?php echo escape_html($selected_stock['company_name']); ?></value>
                    </div>
                    <div class="stock-info-item">
                        <label>Current Price</label>
                        <value><?php echo format_currency($selected_stock['current_price']); ?></value>
                    </div>
                    <div class="stock-info-item">
                        <label>Change</label>
                        <value class="<?php echo $selected_stock['change_percent'] >= 0 ? 'price-up' : 'price-down'; ?>">
                            <?php echo format_percentage($selected_stock['change_percent']); ?>
                        </value>
                    </div>
                    <div class="stock-info-item">
                        <label>Volume</label>
                        <value style="font-size: 14px;"><?php echo number_format($selected_stock['volume']); ?></value>
                    </div>
                    <div class="stock-info-item">
                        <label>Market Cap</label>
                        <value style="font-size: 14px;"><?php echo escape_html($selected_stock['market_cap']); ?></value>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Trade Form -->
            <?php if ($selected_stock): ?>
            <div class="card">
                <h3>💰 Place Order</h3>
                <form method="POST" action="" id="trade-form">
                    <!-- CSRF Token (Security Feature) -->
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf_token); ?>">
                    <input type="hidden" name="stock_id" value="<?php echo $selected_stock['stock_id']; ?>">

                    <div class="form-group">
                        <label for="trade_type">Order Type:</label>
                        <select id="trade_type" name="trade_type" required>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number"
                               id="quantity"
                               name="quantity"
                               min="1"
                               step="1"
                               required
                               onchange="calculateTotal()">
                        <small style="color: #666;">Number of shares to trade</small>
                    </div>

                    <div class="form-group">
                        <label>Order Summary:</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                            <p>Stock: <strong><?php echo escape_html($selected_stock['symbol']); ?></strong></p>
                            <p>Price per share: <strong><?php echo format_currency($selected_stock['current_price']); ?></strong></p>
                            <p>Estimated Total: <strong id="total-amount">HK$ 0.00</strong></p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full" id="submit-btn">
                        Place Order
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="card">
                <h3>💰 Place Order</h3>
                <p style="text-align: center; color: #666; padding: 40px 0;">
                    Please select a stock first.
                </p>
            </div>
            <?php endif; ?>
        </div>

        <div class="alert alert-info">
            <strong>🔒 Security Features Demonstrated:</strong><br>
            ✓ <strong>CSRF Protection:</strong> Hidden token validates form submission<br>
            ✓ <strong>Input Validation:</strong> Quantity must be positive integer<br>
            ✓ <strong>SQL Injection Prevention:</strong> Prepared statements for all queries<br>
            ✓ <strong>XSS Prevention:</strong> All outputs are HTML-encoded
        </div>

        <footer>
            <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
        </footer>
    </div>

    <script>
        const stockPrice = <?php echo $selected_stock ? $selected_stock['current_price'] : 0; ?>;

        function calculateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const total = quantity * stockPrice;
            document.getElementById('total-amount').textContent = 'HK$ ' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        // Update total on quantity change
        document.getElementById('quantity')?.addEventListener('input', calculateTotal);

        // Form validation
        document.getElementById('trade-form')?.addEventListener('submit', function(e) {
            const quantity = parseInt(document.getElementById('quantity').value);
            const tradeType = document.getElementById('trade_type').value;

            if (quantity <= 0) {
                e.preventDefault();
                alert('Quantity must be greater than 0');
                return false;
            }

            if (confirm(`Are you sure you want to ${tradeType.toUpperCase()} ${quantity} shares of <?php echo $selected_stock ? escape_html($selected_stock['symbol']) : ''; ?>?`)) {
                return true;
            } else {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
