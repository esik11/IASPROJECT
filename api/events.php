<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? null;
$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action)) {
    try {
        // Get current user's department
        $user_id = get_current_user_id();
        $user_stmt = $conn->prepare("SELECT department FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user_department = $user_stmt->fetchColumn();

        // Build the query based on access level
        $sql = "
            SELECT 
                e.id, 
                e.title, 
                e.start_date as start, 
                e.end_date as end, 
                e.description, 
                e.status,
                e.event_access_level,
                d.name as department_name
            FROM events e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE 1=1
            AND (
                e.event_access_level = 'school_wide'
                OR (e.event_access_level = 'department_only' 
                    AND EXISTS (
                        SELECT 1 FROM departments d2 
                        WHERE d2.id = e.department_id 
                        AND d2.name = :user_department
                    )
                )
                OR e.created_by = :user_id
                OR EXISTS (SELECT 1 FROM event_staff es WHERE es.event_id = e.id AND es.user_id = :user_id)
            )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_department' => $user_department,
            ':user_id' => $user_id
        ]);
        
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add visual indicators for department-only events
        foreach ($events as &$event) {
            if ($event['event_access_level'] === 'department_only') {
                $event['title'] = "[" . $event['department_name'] . "] " . $event['title'];
            }
            unset($event['event_access_level']);
            unset($event['department_name']);
        }
        
        echo json_encode($events);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error fetching events for calendar: " . $e->getMessage());
        echo json_encode(['error' => 'A server error occurred while fetching events.']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_event_details' && isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("
            SELECT 
                e.*, 
                u.full_name AS creator_name, 
                v.name as venue_name,
                d.name as department_name
            FROM events e
            JOIN users u ON e.created_by = u.id
            LEFT JOIN venues v ON e.venue_id = v.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.id = :event_id
        ");
        $stmt->execute(['event_id' => $event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            // Check if user has access to this event
            $user_id = get_current_user_id();
            $user_stmt = $conn->prepare("SELECT department FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            $user_department = $user_stmt->fetchColumn();

            $has_access = 
                $event['event_access_level'] === 'school_wide' ||
                $event['created_by'] == $user_id ||
                ($event['event_access_level'] === 'department_only' && $event['department_name'] === $user_department) ||
                exists_in_event_staff($conn, $event_id, $user_id);

            if (!$has_access) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have access to view this event.']);
                exit();
            }

            $event['start_date_formatted'] = date('M j, Y, g:i A', strtotime($event['start_date']));
            $event['end_date_formatted'] = date('M j, Y, g:i A', strtotime($event['end_date']));
            echo json_encode($event);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error fetching event details: " . $e->getMessage());
        echo json_encode(['error' => 'A server error occurred while fetching event details.']);
    }
    exit();
}

// Helper function to check if user is in event staff
function exists_in_event_staff($conn, $event_id, $user_id) {
    $stmt = $conn->prepare("SELECT 1 FROM event_staff WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    return (bool)$stmt->fetchColumn();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request. Please specify a valid action.']); 