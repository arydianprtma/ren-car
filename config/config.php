<?php
/**
 * Main Configuration File
 */

// Database Configuration
require_once 'database.php';

// Base URL Configuration
define('BASE_URL', 'http://localhost/Rental%20Mobil/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('USER_URL', BASE_URL . 'user/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Session Configuration
session_start();

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Function to generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
} 