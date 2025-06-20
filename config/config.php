<?php
// Base URL configuration
define('BASE_URL', '/IASPROJECT/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campus_event_management');

// Session configuration - Move these before session_start()
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings before starting the session
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('Asia/Manila');

// Helper function to get base URL
function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

// Helper function to redirect
function redirect($path) {
    header('Location: ' . base_url($path));
    exit();
}

// Helper function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Helper function to require login
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Please login to access this page.';
        redirect('views/auth/login.php');
    }
}

// Helper function to require admin
function require_admin() {
    require_login();
    if (!isset($_SESSION['roles']) || !in_array('Admin', $_SESSION['roles'])) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        redirect('views/dashboard.php');
    }
} 