<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = get_current_user_id();
$action = $_GET['action'] ?? null;
$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_initial_notifications') {
    try {
        $sql = "
            SELECT 
                n.id, n.event_id, n.message, n.is_read, n.created_at,
                v.name as venue,
                e.start_date
            FROM notifications n
            LEFT JOIN events e ON n.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE n.user_id = :user_id AND n.is_read = 0 
            ORDER BY n.created_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the date for consistency with Pusher notifications
        foreach ($notifications as &$notif) {
            if (!empty($notif['start_date'])) {
                $notif['start_date'] = date('M j, g:i A', strtotime($notif['start_date']));
            }
        }

        echo json_encode($notifications);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error fetching initial notifications: " . $e->getMessage());
        echo json_encode(['error' => 'A server error occurred.']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $post_action = $data['action'] ?? null;

    if ($post_action === 'mark_as_read' && isset($data['id'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $data['id'], 'user_id' => $user_id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Notification not found or not owned by user.']);
        }
    } elseif ($post_action === 'clear_all') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $user_id]);
        echo json_encode(['status' => 'success', 'cleared' => $stmt->rowCount()]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action or missing parameters.']);
    }
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request method or action.']); 