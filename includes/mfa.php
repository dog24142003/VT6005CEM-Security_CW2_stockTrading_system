<?php
/**
 * Multi-Factor Authentication Functions
 * VT6005CEM CW2 - Stock Trading System
 * Using Google Authenticator TOTP
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

// generate new MFA secret
function generate_mfa_secret() {
    $g = new GoogleAuthenticator();
    return $g->generateSecret();
}

// get QR code URL for Google Authenticator app
function get_mfa_qr_code($username, $secret) {
    $issuer = 'Beta Investments';
    return GoogleQrUrl::generate($username, $secret, $issuer);
}

// verify the 6-digit code
function verify_mfa_code($secret, $code) {
    $g = new GoogleAuthenticator();
    return $g->checkCode($secret, $code);
}

// enable MFA for a user
function enable_mfa($user_id, $secret) {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE users SET mfa_secret = ?, mfa_enabled = 1 WHERE user_id = ?");
    return $stmt->execute([$secret, $user_id]);
}

// disable MFA for a user
function disable_mfa($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE users SET mfa_secret = NULL, mfa_enabled = 0 WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

// check if user has MFA enabled
function is_mfa_enabled($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT mfa_enabled FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    return $user && $user['mfa_enabled'] == 1;
}

// get user's MFA secret
function get_mfa_secret($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT mfa_secret FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    return $user ? $user['mfa_secret'] : null;
}
