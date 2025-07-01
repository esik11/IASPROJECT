<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/ActivityLog.php';

$permissions = require __DIR__ . '/permissions.php';

if (!function_exists('logUserActivity')) {
    function logUserActivity($action, $description = '') {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $activityLog = new ActivityLog();
        return $activityLog->logActivity($_SESSION['user_id'], $action, $description);
    }
}

if (!function_exists('getActionDescription')) {
    // Helper function to get user-friendly action descriptions
    function getActionDescription($action) {
        $descriptions = [
            'login' => 'User logged in',
            'logout' => 'User logged out',
            'view_page' => 'Viewed page',
            'create_user' => 'Created new user',
            'update_user' => 'Updated user information',
            'delete_user' => 'Deleted user',
            // Add more action descriptions as needed
        ];

        return $descriptions[$action] ?? $action;
    }
}

if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        $db = new Database();
        return $db->getConnection();
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('get_user_roles')) {
    function get_user_roles($userId) {
        static $roles = [];
        if (isset($roles[$userId])) {
            return $roles[$userId];
        }

        try {
            $conn = get_db_connection();
            $stmt = $conn->prepare("
                SELECT r.name 
                FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $roles[$userId] = $userRoles;
            return $userRoles;
        } catch (PDOException $e) {
            error_log("Error fetching user roles: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('normalize_role')) {
    /**
     * Normalizes a role name from the database to a standardized key.
     *
     * @param string $db_role_name The role name from the database.
     * @return string|null The normalized role key or null if not found.
     */
    function normalize_role($db_role_name) {
        $role_map = [
            'Admin' => 'admin',
            'Event Coordinator' => 'event_coordinator',
            'Approver (Dean/Head)' => 'approver',
            'Student' => 'student',
            'Faculty' => 'faculty',
            'Security Officer' => 'security_officer',
            'Maintenance Staff' => 'maintenance_staff',
            'Finance Officer' => 'finance_officer',
            'Guest User' => 'guest',
            'Auditor' => 'auditor'
        ];
        return $role_map[$db_role_name] ?? null;
    }
}

if (!function_exists('has_permission')) {
    /**
     * Checks if the current user has a specific permission.
     *
     * @param string $permission The permission to check.
     * @return bool True if the user has the permission, false otherwise.
     */
    function has_permission($permission, $userRoles = null) {
        if (!is_logged_in()) {
            return false;
        }

        $permissions = require __DIR__ . '/permissions.php';
        
        // If roles are not passed in, fetch them. This maintains backward compatibility.
        if ($userRoles === null) {
        $userId = get_current_user_id();
        $userRoles = get_user_roles($userId);
        }

        if (empty($userRoles)) {
            return false;
        }

        foreach ($userRoles as $role) {
            $normalizedRole = normalize_role($role);
            if ($normalizedRole && isset($permissions[$normalizedRole]) && in_array($permission, $permissions[$normalizedRole])) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        if (!is_logged_in() || !in_array('admin', get_user_roles(get_current_user_id()))) {
            $_SESSION['error'] = 'You must be an administrator to access this page.';
            redirect('views/dashboard.php');
        }
    }
}

if (!function_exists('base_url')) {
    /**
     * Helper function to get base URL.
     *
     * @param string $path
     * @return string
     */
    function base_url($path = '') {
        // This assumes BASE_URL is defined in config.php
        return BASE_URL . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirects to a given path in the application.
     *
     * @param string $path
     */
    function redirect($path) {
        header('Location: ' . base_url($path));
        exit();
    } 
}

if (!function_exists('notify_users_with_permission')) {
    /**
     * Sends a notification to all users who have a specific permission.
     *
     * @param string $permission The permission key (e.g., 'manage_event_ideas').
     * @param string $message The notification message.
     * @param string $link The URL link for the notification.
     * @return void
     */
    function notify_users_with_permission($permission, $message, $link) {
        require_once __DIR__ . '/../models/Notification.php';
        $all_permissions = require __DIR__ . '/permissions.php';
        $conn = get_db_connection();

        $roles_with_permission = [];
        foreach ($all_permissions as $role_key => $permissions_list) {
            if (in_array($permission, $permissions_list)) {
                $roles_with_permission[] = $role_key;
            }
        }

        if (empty($roles_with_permission)) {
            return; // No role has this permission
        }

        // We need to map the role keys (e.g., 'event_coordinator') back to the role names in the DB (e.g., 'Event Coordinator')
        // This is the reverse of normalize_role()
        $db_role_map = [
            'admin' => 'Admin',
            'event_coordinator' => 'Event Coordinator',
            'approver' => 'Approver (Dean/Head)',
            'student' => 'Student',
            'faculty' => 'Faculty',
            'security_officer' => 'Security Officer',
            'maintenance_staff' => 'Maintenance Staff',
            'finance_officer' => 'Finance Officer',
            'guest' => 'Guest User',
            'auditor' => 'Auditor'
        ];
        
        $db_roles = array_map(fn($role_key) => $db_role_map[$role_key] ?? null, $roles_with_permission);
        $db_roles = array_filter($db_roles); // Remove any nulls if a mapping fails

        if (empty($db_roles)) {
            return;
        }

        try {
            // Create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($db_roles), '?'));

            $sql = "
                SELECT ur.user_id
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE r.name IN ({$placeholders})
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($db_roles);
            $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Remove duplicate user IDs if a user has multiple relevant roles
            $unique_user_ids = array_unique($user_ids);

            foreach ($unique_user_ids as $user_id) {
                Notification::create($user_id, $message, $link);
            }
        } catch (Exception $e) {
            error_log("Failed to notify_users_with_permission for '{$permission}': " . $e->getMessage());
        }
    }
} 