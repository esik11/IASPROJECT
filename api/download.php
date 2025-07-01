<?php
session_start();
include_once '../config/database.php';
include_once '../config/helpers.php';
include_once '../config/permissions.php';

// Security Check: Ensure user is logged in and has permission
if (!is_logged_in() || !has_permission('download_documents')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: You do not have permission to download documents.']);
    exit;
}

$attachment_id = $_GET['id'] ?? null;
if (!$attachment_id) {
    http_response_code(400);
    die('Bad Request: No attachment ID specified.');
}

$conn = get_db_connection();
try {
    // Fetch attachment details from the database
    $stmt = $conn->prepare("SELECT * FROM event_attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attachment) {
        http_response_code(404);
        die('Not Found: The requested file does not exist.');
    }

    // More specific security check can be added here, e.g.,
    // check if the current user is associated with $attachment['event_id']

    $file_path = __DIR__ . '/../uploads/event_attachments/' . $attachment['file_path'];

    if (!file_exists($file_path)) {
        http_response_code(404);
        die('Not Found: The file is missing from the server.');
    }

    // Set headers to trigger browser download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream'); // Generic binary stream
    header('Content-Disposition: attachment; filename="' . basename($attachment['file_name']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));

    // Clear output buffer
    flush(); 
    
    // Read the file and send it to the output buffer
    readfile($file_path);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    error_log("Download error: " . $e->getMessage());
    die('Internal Server Error: Could not process the download request.');
} 