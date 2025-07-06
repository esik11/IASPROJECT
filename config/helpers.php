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

/**
 * Checks if a venue is available for a given time frame, excluding a specific event.
 *
 * @param int $venue_id The ID of the venue to check.
 * @param string $start_date The start date/time in 'Y-m-d H:i:s' format.
 * @param string $end_date The end date/time in 'Y-m-d H:i:s' format.
 * @param int|null $event_id_to_exclude An optional event ID to exclude from the check (for updates).
 * @param int|null $reservation_id_to_exclude An optional reservation ID to exclude (for approvals).
 * @return bool True if available, false otherwise.
 */
function isVenueAvailable($venue_id, $start_date, $end_date, $event_id_to_exclude = null, $reservation_id_to_exclude = null) {
    $conn = get_db_connection();

    // Check for conflicting approved/pending events
    $sql = "SELECT COUNT(*) FROM events 
            WHERE venue_id = :venue_id 
            AND status IN ('approved', 'pending')
            AND (
                (start_date < :end_date AND end_date > :start_date)
            )";
    
    if ($event_id_to_exclude) {
        $sql .= " AND id != :event_id_to_exclude";
    }

    $stmt = $conn->prepare($sql);
    $params = [
        ':venue_id' => $venue_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
    ];
    if ($event_id_to_exclude) {
        $params[':event_id_to_exclude'] = $event_id_to_exclude;
    }
    $stmt->execute($params);
    if ($stmt->fetchColumn() > 0) {
        return false; // Conflict found in events
    }

    // Check for conflicting approved/pending venue reservations
    $sql_res = "SELECT COUNT(*) FROM venue_reservations
                WHERE venue_id = :venue_id
                AND status IN ('confirmed', 'pending')
                AND (
                    (start_time < :end_date AND end_time > :start_date)
                )";
    
    if ($reservation_id_to_exclude) {
        $sql_res .= " AND id != :reservation_id_to_exclude";
    }

    $stmt_res = $conn->prepare($sql_res);
    $params_res = [
        ':venue_id' => $venue_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
    ];
    if ($reservation_id_to_exclude) {
        $params_res[':reservation_id_to_exclude'] = $reservation_id_to_exclude;
    }
    $stmt_res->execute($params_res);
    if ($stmt_res->fetchColumn() > 0) {
        return false; // Conflict found in reservations
    }

    return true; // No conflicts found
} 

/**
 * Notifies all users who have a specific permission.
 *
 * @param string $permission The permission string to check for.
 * @param string $message The notification message.
 * @param string|null $link An optional URL for the notification.
 * @param int|null $event_id An optional event ID to associate.
 */
function notifyUsersWithPermission($permission, $message, $link = null, $event_id = null) {
    $conn = get_db_connection();
    try {
        // Find all roles that have the specified permission
        $all_roles_permissions = require __DIR__ . '/permissions.php';
        $roles_with_permission = [];
        foreach ($all_roles_permissions as $role_name => $permissions) {
            if (in_array($permission, $permissions)) {
                $roles_with_permission[] = $role_name;
            }
        }

        if (empty($roles_with_permission)) {
            return; // No roles have this permission
        }

        // Find all users who have one of these roles
        $placeholders = rtrim(str_repeat('?,', count($roles_with_permission)), ',');
        $sql = "SELECT ur.user_id FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE r.name IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($roles_with_permission);
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($user_ids)) {
            return; // No users found with the required roles
        }

        // Create a notification for each user
        $notification_sql = "INSERT INTO notifications (user_id, event_id, message, link, is_read) 
                             VALUES (:user_id, :event_id, :message, :link, 0)";
        $notification_stmt = $conn->prepare($notification_sql);

        foreach ($user_ids as $user_id) {
            $notification_stmt->execute([
                ':user_id' => $user_id,
                ':event_id' => $event_id,
                ':message' => $message,
                ':link' => $link,
            ]);
        }
        
        // You would trigger Pusher here if it's set up
        // Example:
        // $pusher = get_pusher_instance();
        // if ($pusher) {
        //     $pusher->trigger('notifications-channel', 'new-notification', ['message' => $message]);
        // }

    } catch (Exception $e) {
        // Log error, don't break the user's flow
        error_log("Notification Error: " . $e->getMessage());
    }
}

/**
 * Gets the name of a venue by its ID.
 */
function get_venue_name($venue_id, $conn) {
    try {
        $stmt = $conn->prepare("SELECT name FROM venues WHERE id = ?");
        $stmt->execute([$venue_id]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        return $venue ? $venue['name'] : 'Unknown Venue';
    } catch (Exception $e) {
        error_log("Error fetching venue name: " . $e->getMessage());
        return 'Unknown Venue';
    }
}

/**
 * Gets the full name of a user by their ID.
 */
function get_user_full_name($user_id, $conn) {
    try {
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['full_name'] : 'Unknown User';
    } catch (Exception $e) {
        error_log("Error fetching user name: " . $e->getMessage());
        return 'Unknown User';
    }
}

/**
 * Notifies a specific user by their ID.
 *
 * @param int $user_id The ID of the user to notify.
 * @param string $message The notification message.
 * @param string|null $link An optional URL for the notification.
 * @param int|null $event_id An optional event ID to associate.
 */
function notifyUserById($user_id, $message, $link = null, $event_id = null) {
    $conn = get_db_connection();
    try {
        $sql = "INSERT INTO notifications (user_id, event_id, message, link, is_read) 
                VALUES (:user_id, :event_id, :message, :link, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':event_id' => $event_id,
            ':message' => $message,
            ':link' => $link,
        ]);
        
        // You would trigger Pusher here if it's set up
        
    } catch (Exception $e) {
        // Log error, don't break the user's flow
        error_log("Notification Error: " . $e->getMessage());
    }
} 