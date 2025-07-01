<?php
require_once __DIR__ . '/../../config/helpers.php';

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout activity before destroying the session
if (isset($_SESSION['user_id'])) {
    logUserActivity('logout', 'User logged out');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: /IASPROJECT/views/auth/login.php');
exit(); 