<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Ensure the user has permission to manage venues
if (!has_permission('manage_venues')) {
    $_SESSION['error'] = 'You do not have permission to perform this action.';
    redirect('views/admin/venues/index.php');
}

$action = $_REQUEST['action'] ?? null;
$conn = get_db_connection();

try {
    if ($action === 'create_venue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        if (empty($name)) {
            throw new Exception('Venue name is required.');
        }
        $sql = "INSERT INTO venues (name, location, capacity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $name,
            trim($_POST['location'] ?? null),
            !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null
        ]);
        $_SESSION['success'] = 'Venue created successfully.';
        logUserActivity('create_venue', "Created venue: {$name}");

    } elseif ($action === 'update_venue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        if (empty($name) || empty($id)) {
            throw new Exception('Venue name and ID are required.');
        }
        $sql = "UPDATE venues SET name = ?, location = ?, capacity = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $name,
            trim($_POST['location'] ?? null),
            !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null,
            $id
        ]);
        $_SESSION['success'] = 'Venue updated successfully.';
        logUserActivity('update_venue', "Updated venue ID: {$id}");

    } elseif ($action === 'delete_venue' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = $_GET['id'];
        if (empty($id)) {
            throw new Exception('Venue ID is required.');
        }
        $sql = "DELETE FROM venues WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Venue deleted successfully.';
        logUserActivity('delete_venue', "Deleted venue ID: {$id}");

    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Operation failed: ' . $e->getMessage();
}

redirect('views/admin/venues/index.php'); 