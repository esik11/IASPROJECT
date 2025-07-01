<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../models/ActivityLog.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create logs directory if it doesn't exist
$processLogsDir = __DIR__ . '/logs';
if (!file_exists($processLogsDir)) {
    mkdir($processLogsDir, 0777, true);
}

// Configure process-specific logging
ini_set('log_errors', 1);
ini_set('error_log', $processLogsDir . '/process_error.log');

// Log function for debugging
function debug_log($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$type] $message" . PHP_EOL;
    error_log($log_message);
    
    // Also log to process-specific log
    $process_log = __DIR__ . '/logs/process_error.log';
    file_put_contents($process_log, $log_message, FILE_APPEND);
}

// Start logging
debug_log("=== New Request Started ===");
debug_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
debug_log("POST Data: " . print_r($_POST, true));
debug_log("Session Data: " . print_r($_SESSION, true));

// Ensure the user has permission to manage users
if (!has_permission('manage_users')) {
    $_SESSION['error'] = 'You do not have permission to perform this action.';
    redirect('views/admin/users/index.php');
    exit();
}

$conn = get_db_connection();
$activityLog = new ActivityLog($conn);
$action = $_POST['action'] ?? null;
$current_user_id = get_current_user_id();

try {
    if ($action === 'create_user') {
        handle_create_user($conn, $activityLog, $current_user_id);
    } elseif ($action === 'update_user') {
        handle_update_user($conn, $activityLog, $current_user_id);
    } elseif ($action === 'delete_user') {
        handle_delete_user($conn, $activityLog);
    } else {
        $_SESSION['error'] = 'Invalid action specified.';
    }
} catch (PDOException $e) {
    // Log the detailed error message for the admin
    error_log("User processing error: " . $e->getMessage());
    // Provide a generic error message to the user
    $_SESSION['error'] = 'A database error occurred. Please check the logs for more details.';
} catch (Exception $e) {
    error_log("An unexpected error occurred: " . $e->getMessage());
    $_SESSION['error'] = 'An unexpected error occurred.';
            }

redirect('views/admin/users/index.php');

function handle_create_user($conn, $activityLog, $current_user_id) {
    // Sanitize and validate input
    $type = $_POST['type'] ?? 'employee';
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $roles = $_POST['roles'] ?? [];
    
    // Type-specific fields
    $student_number = ($type === 'student') ? filter_input(INPUT_POST, 'student_number', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $position = ($type === 'employee') ? filter_input(INPUT_POST, 'position', FILTER_SANITIZE_SPECIAL_CHARS) : null;

    // Basic validation
    if (!$full_name || !$username || !$email || empty($password) || empty($roles)) {
        $_SESSION['error'] = 'Please fill all required fields and assign at least one role.';
        redirect('views/admin/users/create.php?type=' . $type);
        return;
    }
    
    try {
        $conn->beginTransaction();

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, email, full_name, department, position, student_number, phone, status, created_at) 
             VALUES (:username, :password, :email, :full_name, :department, :position, :student_number, :phone, :status, NOW())"
        );

            $stmt->execute([
            ':username' => $username,
            ':password' => $hashed_password,
            ':email' => $email,
            ':full_name' => $full_name,
            ':department' => $department,
            ':position' => $position,
            ':student_number' => $student_number,
            ':phone' => $phone,
            ':status' => $status,
            ]);

            $user_id = $conn->lastInsertId();

            // Assign roles
        $stmt_roles = $conn->prepare("INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (:user_id, :role_id, :assigned_by)");
        foreach ($roles as $role_id) {
            $stmt_roles->execute([
                ':user_id' => $user_id,
                ':role_id' => $role_id,
                ':assigned_by' => $current_user_id
            ]);
        }
        
            $conn->commit();

        // Log activity
        $loggedInUserId = $_SESSION['user_id'] ?? 0;
        $activityLog->logActivity($loggedInUserId, "Created new user: {$username} (ID: {$user_id})");

        $_SESSION['success'] = 'User created successfully!';
        redirect('views/admin/users/index.php');

    } catch (PDOException $e) {
            $conn->rollBack();
        $_SESSION['error'] = 'Failed to create user. Error: ' . $e->getMessage();
        redirect('views/admin/users/create.php?type=' . $type);
        }
}

function handle_update_user($conn, $activityLog, $current_user_id) {
    // Sanitize and validate input
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $type = $_POST['type'] ?? 'employee';
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password']; // Don't sanitize, will be hashed
    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $roles = $_POST['roles'] ?? [];
    
    // Type-specific fields
    $student_number = ($type === 'student') ? filter_input(INPUT_POST, 'student_number', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    $position = ($type === 'employee') ? filter_input(INPUT_POST, 'position', FILTER_SANITIZE_SPECIAL_CHARS) : null;
    
    // Basic validation
    if (!$user_id || !$full_name || !$username || !$email || empty($roles)) {
        $_SESSION['error'] = 'Please fill all required fields and assign at least one role.';
        redirect('views/admin/users/edit.php?id=' . $user_id);
        return;
    }

    try {
        $conn->beginTransaction();

        // Update password only if a new one is provided
        $password_part = '';
            $params = [
            ':id' => $user_id,
            ':username' => $username,
            ':email' => $email,
            ':full_name' => $full_name,
            ':department' => $department,
            ':position' => $position,
            ':student_number' => $student_number,
            ':phone' => $phone,
            ':status' => $status,
            ];

        if (!empty($password)) {
            $password_part = ", password = :password";
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }

        $stmt = $conn->prepare(
            "UPDATE users SET 
             username = :username, email = :email, full_name = :full_name,
             department = :department, position = :position, student_number = :student_number,
             phone = :phone, status = :status {$password_part}
             WHERE id = :id"
        );
                $stmt->execute($params);
            
        // Sync roles (delete old, insert new)
        $stmt_delete_roles = $conn->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
        $stmt_delete_roles->execute([':user_id' => $user_id]);

        $stmt_insert_roles = $conn->prepare("INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (:user_id, :role_id, :assigned_by)");
        foreach ($roles as $role_id) {
            $stmt_insert_roles->execute([
                ':user_id' => $user_id,
                ':role_id' => $role_id,
                ':assigned_by' => $current_user_id
            ]);
        }
            
            $conn->commit();

        // Log activity
        $loggedInUserId = $_SESSION['user_id'] ?? 0;
        $activityLog->logActivity($loggedInUserId, "Updated user: {$username} (ID: {$user_id})");

        $_SESSION['success'] = 'User updated successfully!';
        redirect('views/admin/users/index.php');

    } catch (PDOException $e) {
            $conn->rollBack();
        $_SESSION['error'] = 'Failed to update user. Error: ' . $e->getMessage();
        redirect('views/admin/users/edit.php?id=' . $user_id);
    }
}

function handle_delete_user($conn, $activityLog) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);

    if (!$user_id) {
        $_SESSION['error'] = 'Invalid user ID.';
        redirect('views/admin/users/index.php');
        return;
    }
    
    try {
        $conn->beginTransaction();

        // First, get username for logging before deletion
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $username = $stmt->fetchColumn();

        // Delete from user_roles first to satisfy foreign key constraints
        $stmt_roles = $conn->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
        $stmt_roles->execute([':user_id' => $user_id]);

        // Delete from users
        $stmt_user = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt_user->execute([':id' => $user_id]);
            
            $conn->commit();

        // Log activity
        $loggedInUserId = $_SESSION['user_id'] ?? 0;
        $activityLog->logActivity($loggedInUserId, "Deleted user: {$username} (ID: {$user_id})");

        $_SESSION['success'] = 'User deleted successfully!';
        redirect('views/admin/users/index.php');

    } catch (PDOException $e) {
            $conn->rollBack();
        $_SESSION['error'] = 'Failed to delete user. Error: ' . $e->getMessage();
        redirect('views/admin/users/index.php');
    }
}