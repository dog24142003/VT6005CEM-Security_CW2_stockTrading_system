<?php
/**
 * Logout Page
 * VT6005CEM CW2 - Stock Trading System
 * Security Feature: Secure Session Destruction
 */

require_once '../includes/config.php';
require_once '../includes/security.php';

// Set security headers
set_security_headers();

// Start secure session
start_secure_session();

// Log logout event
if (isset($_SESSION['user_id'])) {
    log_security_event('logout', 'User logged out', $_SESSION['user_id']);
}

// Destroy session
destroy_session();

// Redirect to login
header('Location: index.php');
exit;
