<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if (!has_permission('post_announcements')) {
    $_SESSION['error'] = 'You do not have permission to perform this action.';
    redirect('views/admin/announcements/index.php');
}

$action = $_REQUEST['action'] ?? null;
$conn = get_db_connection();
$user_id = get_current_user_id();

try {
    if ($action === 'create_announcement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        if (empty($title) || empty($content)) {
            throw new Exception('Title and content are required.');
        }
        $sql = "INSERT INTO announcements (title, content, user_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $content, $user_id]);
        $_SESSION['success'] = 'Announcement posted successfully.';
        logUserActivity('create_announcement', "Created announcement: {$title}");

    } elseif ($action === 'update_announcement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        if (empty($title) || empty($content) || empty($id)) {
            throw new Exception('Title, content, and ID are required.');
        }
        $sql = "UPDATE announcements SET title = ?, content = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $content, $id]);
        $_SESSION['success'] = 'Announcement updated successfully.';
        logUserActivity('update_announcement', "Updated announcement ID: {$id}");

    } elseif ($action === 'delete_announcement' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = $_GET['id'];
        if (empty($id)) {
            throw new Exception('Announcement ID is required.');
        }
        $sql = "DELETE FROM announcements WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Announcement deleted successfully.';
        logUserActivity('delete_announcement', "Deleted announcement ID: {$id}");

    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Operation failed: ' . $e->getMessage();
}

redirect('views/admin/announcements/index.php'); 