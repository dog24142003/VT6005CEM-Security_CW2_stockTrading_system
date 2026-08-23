# Stock Trading System - VT6005CEM CW2

**Beta Investments Secure Trading Platform**

## 🔐 Security Features Implemented

### 1. Authentication & Authorization (40 marks potential)
- ✅ **Password Hashing**: bcrypt algorithm with cost factor 12
- ✅ **Multi-Factor Authentication (MFA)**: TOTP-based using Google Authenticator
- ✅ **Session Management**: Secure cookies with HttpOnly, Secure, SameSite flags
- ✅ **Session Timeout**: 15-minute inactivity timeout
- ✅ **Account Lockout**: 5 failed login attempts = 1 hour lockout
- ✅ **Role-Based Access Control (RBAC)**: Customer and Admin roles

### 2. Input Validation & Injection Prevention (40 marks potential)
- ✅ **SQL Injection Prevention**: Prepared statements for ALL database queries
- ✅ **XSS Prevention**: HTML entity encoding on all outputs
- ✅ **CSRF Protection**: Token-based validation on all forms
- ✅ **Input Validation**: Whitelist approach for usernames, emails, quantities
- ✅ **Input Sanitization**: Trim and strip tags on user inputs

### 3. Cryptographic Controls (40 marks potential)
- ✅ **HTTPS/TLS**: Secure communication (requires SSL certificate)
- ✅ **Security Headers**: HSTS, CSP, X-Frame-Options, X-Content-Type-Options, etc.
- ✅ **Password Storage**: bcrypt hashing (never plain text)
- ✅ **Session Security**: Cryptographically secure session IDs

### 4. Security Logging & Monitoring (20 marks potential)
- ✅ **Comprehensive Logging**: All authentication events logged
- ✅ **Transaction Logging**: All buy/sell operations recorded
- ✅ **Security Event Logging**: Failed logins, MFA events, admin actions
- ✅ **Audit Trail**: IP address, user agent, timestamp tracking
- ✅ **Admin Dashboard**: Real-time security event viewer

---

## 📋 System Requirements

- **Web Server**: Apache 2.4+ (XAMPP recommended)
- **PHP**: 8.0 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Composer**: Latest version
- **Browser**: Modern browser with JavaScript enabled

---

## 🚀 Installation Guide

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install and start Apache + MySQL

### Step 2: Setup Project
1. Copy project folder to: `B:\CU_TopUp\VT6005CEM-Security_CW2_stockTrading_system\`
2. Or copy to XAMPP htdocs: `C:\xampp\htdocs\stock_trading_system\`

### Step 3: Install Composer Dependencies
```bash
cd B:\CU_TopUp\VT6005CEM-Security_CW2_stockTrading_system
composer install
```

### Step 4: Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Import database schema:
   - Open `database/schema.sql`
   - Execute all SQL statements
   - Database `stock_trading_system` will be created

### Step 5: Configure Database Connection
Edit `includes/config.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stock_trading_system');
define('DB_USER', 'root');
define('DB_PASS', '');  // Change if you have MySQL password
```

### Step 6: Configure XAMPP for HTTPS (Optional but recommended for demo)
1. Edit `C:\xampp\apache\conf\extra\httpd-ssl.conf`
2. Or use HTTP for development (some security features require HTTPS)

### Step 7: Access System
- Navigate to: http://localhost/stock_trading_system/public/
- Or: http://localhost/stock_trading_system/public/index.php

---

## 👤 Demo Accounts

### Admin Account
- **Username**: `admin`
- **Password**: `Admin@123`
- **Features**: Full access + Admin panel

### Customer Account
- **Username**: `customer1`
- **Password**: `Customer@123`
- **Features**: Trading + Portfolio management

---

## 📁 Project Structure

```
B:\CU_TopUp\VT6005CEM-Security_CW2_stockTrading_system\
├── public/                    # Web root (point Apache here)
│   ├── index.php             # Login page
│   ├── register.php          # Registration
│   ├── verify_mfa.php        # MFA verification
│   ├── dashboard.php         # Main dashboard
│   ├── stocks.php            # Browse stocks (SQL injection demo)
│   ├── trade.php             # Trading page (CSRF demo)
│   ├── portfolio.php         # User portfolio
│   ├── transactions.php      # Transaction history
│   ├── settings.php          # User settings + MFA setup
│   ├── admin.php             # Admin panel (RBAC demo)
│   ├── logout.php            # Logout
│   └── css/
│       └── style.css         # Stylesheet
│
├── includes/                  # PHP backend
│   ├── config.php            # Database configuration
│   ├── security.php          # Security functions (10 features)
│   ├── mfa.php               # MFA functions
│   └── functions.php         # Helper functions
│
├── database/
│   └── schema.sql            # Database schema with sample data
│
├── composer.json             # PHP dependencies
└── README.md                 # This file
```

---

## 🎥 Demonstration Video Script

### Introduction (1 minute)
"This is a secure stock trading system for Beta Investments with 10+ security features."

### Security Features List (1 minute)
Show slide with all features:
1. Password Hashing (bcrypt)
2. Multi-Factor Authentication (TOTP)
3. SQL Injection Prevention (Prepared Statements)
4. XSS Prevention (Output Encoding)
5. CSRF Protection (Token-based)
6. Secure Session Management
7. Role-Based Access Control (RBAC)
8. Account Lockout Mechanism
9. Security Logging & Audit Trail
10. HTTPS + Security Headers

### Implementation Demo - Authentication (2 minutes)
1. Show registration code (password hashing)
2. Show login code (prepared statements)
3. Demo MFA setup in settings
4. Demo MFA verification
5. Show session management code

### Implementation Demo - Input Validation (2 minutes)
1. Show search page code (prepared statements)
2. Try SQL injection attack: `' OR '1'='1` → fails safely
3. Show trade page code (CSRF token)
4. Show XSS prevention (output encoding)

### Implementation Demo - Security Features (2 minutes)
1. Show security headers in browser DevTools
2. Show security logs in admin panel
3. Demo account lockout (5 failed attempts)
4. Show RBAC (customer cannot access admin panel)

### Results & Conclusion (2 minutes)
- All features working correctly
- Security demonstrated practically
- Code walkthrough summary

---

## 🔒 Security Testing Checklist

### SQL Injection Prevention
- [x] Try: `' OR '1'='1` in search → Should be safe
- [x] All queries use prepared statements
- [x] No string concatenation in SQL

### XSS Prevention
- [x] Try: `<script>alert('XSS')</script>` in inputs → Should be encoded
- [x] All outputs use `htmlspecialchars()`

### CSRF Protection
- [x] All forms have CSRF token
- [x] Token verified on submission
- [x] Token regenerated after use

### Authentication Security
- [x] Passwords hashed with bcrypt
- [x] MFA works with Google Authenticator
- [x] Session timeout after 15 minutes
- [x] Account locks after 5 failed attempts

### Authorization
- [x] Customer cannot access admin.php
- [x] Logout destroys session properly
- [x] Session fixation prevented

---

## 📊 Database Schema Overview

**7 Tables:**
1. `users` - User accounts with MFA settings
2. `stocks` - Stock information
3. `user_portfolios` - User stock holdings
4. `transactions` - Trading history
5. `security_logs` - Security event audit trail
6. `sessions` - Active sessions
7. `csrf_tokens` - CSRF protection tokens

**3 Views:**
- `v_user_transaction_history` - Transaction details with stock info
- `v_user_portfolio_summary` - Portfolio with P&L calculations
- `v_security_events_summary` - Security logs with usernames

**3 Stored Procedures:**
- `sp_clean_expired_sessions()` - Remove old sessions
- `sp_clean_expired_csrf_tokens()` - Remove old tokens
- `sp_reset_failed_login_attempts()` - Auto-unlock accounts

---

## 🐛 Troubleshooting

### "Database connection error"
- Check MySQL is running in XAMPP
- Verify database credentials in `includes/config.php`
- Ensure database schema is imported

### "Composer not found"
- Install Composer: https://getcomposer.org/
- Run `composer install` in project root

### "MFA QR code not showing"
- Check `vendor/` folder exists
- Run `composer install` if missing
- Verify GD library enabled in PHP

### "CSRF token validation failed"
- Clear browser cookies
- Regenerate token by refreshing page

### "Session timeout too fast"
- Increase `SESSION_LIFETIME` in `includes/config.php`

---

## 📝 Code Highlights for Video

### Security Feature 1: Password Hashing
```php
// includes/security.php line 11
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

### Security Feature 2: SQL Injection Prevention
```php
// includes/functions.php line 20
$stmt = $pdo->prepare("SELECT * FROM stocks WHERE symbol LIKE ? OR company_name LIKE ?");
$stmt->execute([$keyword, $keyword]);
```

### Security Feature 3: CSRF Protection
```php
// includes/security.php line 26
function generate_csrf_token() {
    $token = bin2hex(random_bytes(32));
    // Store in database + session
}
```

### Security Feature 4: XSS Prevention
```php
// includes/security.php line 60
function escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
```

---

## 📧 Support

For questions or issues, contact:
- **Student**: [Your Name]
- **Student ID**: [Your ID]
- **Module**: VT6005CEM Security
- **Assignment**: Coursework 2

---

## 📄 License

This project is for educational purposes only (VT6005CEM CW2).

---

**Last Updated**: 2026-08-23
