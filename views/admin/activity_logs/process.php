<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'clear_logs') {
    if (has_permission('clear_activity_logs')) {
        try {
            $conn = get_db_connection();
            $conn->beginTransaction();

            // Delete all records from the activity logs table
            $conn->exec("DELETE FROM activity_logs");

            // Log this clearing action. This will be the only log left.
            logUserActivity('clear_logs', 'Cleared all activity logs.');
            
            $conn->commit();
            $_SESSION['success'] = 'Activity logs cleared successfully.';
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $_SESSION['error'] = 'Error clearing logs: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'You do not have permission to clear logs.';
    }
}

// Redirect back to the activity logs page
redirect('/views/admin/activity_logs/index.php'); 