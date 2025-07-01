<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_attachment') {
    // Security Checks
    if (!has_permission('upload_documents')) {
        $_SESSION['error'] = 'You do not have permission to upload documents.';
        redirect_to_event_details();
    }

    $event_id = $_POST['event_id'] ?? null;
    $user_id = get_current_user_id();
    $file = $_FILES['document'] ?? null;

    if (!$event_id || !$user_id || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'Invalid upload. Please select a file.';
        redirect_to_event_details($event_id);
    }
    
    // File Validation
    $file_size = $file['size'];
    $file_name = basename($file['name']);
    $file_tmp_name = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_ext = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['error'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_ext);
        redirect_to_event_details($event_id);
    }
    if ($file_size > $max_file_size) {
        $_SESSION['error'] = 'File size exceeds the 5MB limit.';
        redirect_to_event_details($event_id);
    }

    // Process and move the file
    $upload_dir = __DIR__ . '/../../uploads/event_attachments/';
    $new_file_name = uniqid('', true) . '.' . $file_ext;
    $destination = $upload_dir . $new_file_name;

    if (!move_uploaded_file($file_tmp_name, $destination)) {
        $_SESSION['error'] = 'Failed to move uploaded file.';
        redirect_to_event_details($event_id);
    }

    $conn = get_db_connection();
    try {
        // Save to database
        $sql = "INSERT INTO event_attachments (event_id, file_name, file_path, file_type, file_size, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$event_id, $file_name, $new_file_name, $file_ext, $file_size, $user_id]);

        // Send Notifications
        notify_stakeholders($conn, $event_id, $user_id, $file_name);

        $_SESSION['success'] = 'Document uploaded successfully.';

    } catch (Exception $e) {
        // Clean up uploaded file on DB error
        if (file_exists($destination)) {
            unlink($destination);
        }
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
    
    redirect_to_event_details($event_id);
}

function redirect_to_event_details($event_id) {
    $url = $event_id ? "views/events/details.php?id={$event_id}" : "views/dashboard.php";
    redirect($url);
}

function notify_stakeholders($conn, $event_id, $uploader_id, $file_name) {
    // Fetch event title and creator
    $event_stmt = $conn->prepare("SELECT title, created_by FROM events WHERE id = ?");
    $event_stmt->execute([$event_id]);
    $event = $event_stmt->fetch();

    if (!$event) return;

    // Fetch uploader name
    $uploader_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $uploader_stmt->execute([$uploader_id]);
    $uploader = $uploader_stmt->fetch();

    $notification_message = "A new document, '" . htmlspecialchars($file_name) . "', was uploaded to the event '" . htmlspecialchars($event['title']) . "' by " . htmlspecialchars($uploader['full_name']) . ".";

    // Get event coordinators assigned to this event
    $coord_stmt = $conn->prepare("
        SELECT u.id FROM users u
        JOIN event_staff es ON u.id = es.user_id
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE es.event_id = ? AND r.name = 'Event Coordinator'
    ");
    $coord_stmt->execute([$event_id]);
    $coordinator_ids = $coord_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get all admins
    $admin_stmt = $conn->prepare("
        SELECT u.id FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.name = 'Admin'
    ");
    $admin_stmt->execute();
    $admin_ids = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);

    $recipients = array_unique(array_merge([$event['created_by']], $coordinator_ids, $admin_ids));

    $notif_sql = "INSERT INTO notifications (user_id, event_id, message, is_read) VALUES (?, ?, ?, 0)";
    $notif_stmt = $conn->prepare($notif_sql);

    foreach ($recipients as $recipient_id) {
        if ($recipient_id == $uploader_id) continue; // Don't notify the person who uploaded
        $notif_stmt->execute([$recipient_id, $event_id, $notification_message]);
        // Pusher notification can be added here if desired
    }
} 