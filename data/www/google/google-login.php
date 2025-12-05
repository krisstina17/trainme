<?php
/**
 * Google OAuth Login Handler
 * 
 * This file initiates the Google OAuth flow
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if Google OAuth is configured
if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID' || empty(GOOGLE_CLIENT_ID)) {
    $_SESSION['error'] = 'Google prijava ni konfigurirana. Prosimo, kontaktirajte administratorja.';
    header('Location: /login.php');
    exit;
}

// Generate state token for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

// Build Google OAuth URL
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Redirect to Google
header('Location: ' . $authUrl);
exit;
