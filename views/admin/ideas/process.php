<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../models/ActivityLog.php';
require_once __DIR__ . '/../../../models/Notification.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('views/admin/ideas/index.php'));
}

if (!has_permission('manage_event_ideas')) {
    $_SESSION['error'] = 'You do not have permission to perform this action.';
    redirect(base_url('views/dashboard.php'));
}

$action = $_POST['action'] ?? '';
$idea_id = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : 0;
$current_user_id = get_current_user_id();

if (empty($action) || $idea_id <= 0) {
    $_SESSION['error'] = 'Invalid action or idea ID.';
    redirect(base_url('views/admin/ideas/index.php'));
}

$conn = get_db_connection();

try {
    // Fetch idea details to get submitter's ID and title
    $stmt = $conn->prepare("SELECT submitted_by, title, description FROM event_ideas WHERE id = :id");
    $stmt->execute(['id' => $idea_id]);
    $idea = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$idea) {
        throw new Exception("Event idea not found.");
    }
    
    $submitter_id = $idea['submitted_by'];
    $idea_title = $idea['title'];
    $idea_description = $idea['description'];
    $new_status = '';
    $notification_message = '';
    $log_message = '';

    switch ($action) {
        case 'approve_idea':
            $new_status = 'approved';
            $notification_message = "Your event idea \"{$idea_title}\" has been approved!";
            $log_message = "Approved event idea: {$idea_title} (ID: {$idea_id})";
            break;
        case 'reject_idea':
            $new_status = 'rejected';
            $notification_message = "Your event idea \"{$idea_title}\" has been rejected.";
            $log_message = "Rejected event idea: {$idea_title} (ID: {$idea_id})";
            break;
        case 'review_idea':
            $new_status = 'under_review';
            $log_message = "Marked event idea as under review: {$idea_title} (ID: {$idea_id})";
            break;
        default:
            throw new Exception("Invalid action specified.");
    }

    // Update the idea status
    $update_stmt = $conn->prepare("UPDATE event_ideas SET status = :status WHERE id = :id");
    $update_stmt->execute(['status' => $new_status, 'id' => $idea_id]);

    // Log the activity
    $activityLog = new ActivityLog();
    $activityLog->logActivity($current_user_id, $action, $log_message);

    // Send notification to the original submitter if the action is approve or reject
    if (!empty($notification_message)) {
        Notification::create($submitter_id, $notification_message, base_url('views/dashboard.php')); // Link to their dashboard or a future "my ideas" page
    }
    
    $_SESSION['success'] = "The event idea has been successfully updated to '{$new_status}'.";

    // If approved, redirect to the create event form with pre-filled data
    if ($action === 'approve_idea') {
        $relative_path = 'views/events/create.php?idea_id=' . $idea_id . '&title=' . urlencode($idea_title) . '&description=' . urlencode($idea_description);
        redirect($relative_path);
    }

} catch (Exception $e) {
    $_SESSION['error'] = 'Error processing request: ' . $e->getMessage();
}

redirect(base_url('views/admin/ideas/index.php')); 