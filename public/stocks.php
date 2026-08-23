<?php
/**
 * Browse Stocks Page
 * VT6005CEM CW2 - Stock Trading System
 * Security Features: SQL Injection Prevention (Prepared Statements), XSS Prevention
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

// Handle search
$search_keyword = '';
$stocks = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_keyword = sanitize_input($_GET['search']);
    // Using prepared statement - SQL Injection Prevention
    $stocks = search_stocks($search_keyword);
} else {
    $stocks = get_all_stocks();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Stocks - Beta Investments</title>
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
                <li><a href="stocks.php" class="active">Browse Stocks</a></li>
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

        <div class="card">
            <h3>📈 Browse Stocks</h3>

            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" action="">
                    <input type="text"
                           name="search"
                           placeholder="🔍 Search by symbol or company name..."
                           value="<?php echo escape_html($search_keyword); ?>">
                </form>
            </div>

            <?php if ($search_keyword): ?>
                <p style="color: #666; margin-bottom: 15px;">
                    Search results for: <strong><?php echo escape_html($search_keyword); ?></strong>
                    (<?php echo count($stocks); ?> found)
                    <a href="stocks.php" style="margin-left: 10px;">Clear search</a>
                </p>
            <?php endif; ?>

            <!-- Stock List -->
            <?php if (!empty($stocks)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Company Name</th>
                            <th>Current Price</th>
                            <th>Previous Close</th>
                            <th>Change %</th>
                            <th>Volume</th>
                            <th>Market Cap</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $stock): ?>
                        <tr>
                            <td><strong><?php echo escape_html($stock['symbol']); ?></strong></td>
                            <td><?php echo escape_html($stock['company_name']); ?></td>
                            <td><?php echo format_currency($stock['current_price']); ?></td>
                            <td><?php echo format_currency($stock['previous_close']); ?></td>
                            <td class="<?php echo $stock['change_percent'] >= 0 ? 'price-up' : 'price-down'; ?>">
                                <?php echo format_percentage($stock['change_percent']); ?>
                            </td>
                            <td><?php echo number_format($stock['volume']); ?></td>
                            <td><?php echo escape_html($stock['market_cap']); ?></td>
                            <td>
                                <a href="trade.php?stock_id=<?php echo $stock['stock_id']; ?>"
                                   class="btn btn-primary"
                                   style="padding: 8px 15px; font-size: 14px;">Trade</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                No stocks found matching your search.
            </div>
            <?php endif; ?>
        </div>

        <div class="alert alert-info">
            <strong>🔒 Security Feature Demonstration:</strong><br>
            Try SQL injection attack in search: <code>' OR '1'='1</code><br>
            Result: Search will be safely handled with prepared statements (no injection possible)
        </div>

        <footer>
            <p>&copy; 2026 Beta Investments. VT6005CEM CW2</p>
            <p style="font-size: 12px; margin-top: 10px;">
                🔒 SQL Injection Prevention: All queries use prepared statements
            </p>
        </footer>
    </div>

    <script>
        // Auto-submit search on input
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    </script>
</body>
</html>
