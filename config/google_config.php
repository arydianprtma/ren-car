<?php
/**
 * Google OAuth Configuration
 */

// Google API credentials
define('GOOGLE_CLIENT_ID', '60803874914-l7rt9bfljjljr9bg650cjjqiq5d2rrrs.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Google OAuth scopes
define('GOOGLE_SCOPES', [
    'email',
    'profile',
    'openid'
]);

// Gunakan autoload untuk Google Client API
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Get Google Client instance
 */
function getGoogleClient() {
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(BASE_URL . 'user/google_callback.php');
    $client->addScope(GOOGLE_SCOPES);
    return $client;
} 