<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site Details
define('SITE_NAME', 'Campus Event Management');

// Base URL - Made static to prevent pathing issues.
define('BASE_URL', 'http://localhost/IASPROJECT/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campus_event_management');

// Pusher API Credentials - Replace with your actual credentials
define('PUSHER_APP_ID', '2015139');
define('PUSHER_APP_KEY', 'fd6a54298bed787adaa8');
define('PUSHER_APP_SECRET', 'e198e618cda1a65dcba1');
define('PUSHER_APP_CLUSTER', 'ap1');

// Timezone
date_default_timezone_set('Asia/Manila');

// Error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');
error_reporting(E_ALL);

// Include the main database connection and helper functions
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php'; // Centralized helpers

