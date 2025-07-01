<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';

// Handle POST requests for event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_idea') {
        if (!has_permission('submit_event_idea')) {
            $_SESSION['error'] = 'You do not have permission to submit an event idea.';
            redirect('views/dashboard.php');
        }
        
        try {
            $conn = get_db_connection();
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $current_user_id = get_current_user_id();
            
            if (empty($title) || empty($description)) {
                throw new Exception('Title and description are required.');
            }

            $sql = "INSERT INTO event_ideas (title, description, submitted_by) VALUES (:title, :description, :submitted_by)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':submitted_by' => $current_user_id
            ]);

            $idea_id = $conn->lastInsertId();

            // Log the activity
            logUserActivity('submit_idea', "Submitted a new event idea: " . $title);
            
            // Notify users with permission to manage ideas
            $permission_name = 'manage_event_ideas';
            $notification_message = "A new event idea '{$title}' has been submitted for review.";
            $notification_link = base_url('views/admin/ideas/index.php');
            
            notify_users_with_permission($permission_name, $notification_message, $notification_link);

            $_SESSION['success'] = 'Your event idea has been submitted successfully for review!';
            redirect('views/dashboard.php');

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error submitting idea: ' . $e->getMessage();
            redirect('views/events/submit_idea.php');
        }
        exit();
    }

    if ($action === 'create_event') {
        if (!has_permission('create_official_event')) {
            $_SESSION['error'] = 'You do not have permission to create an event.';
            header('Location: ' . base_url('views/dashboard.php'));
            exit();
        }
        
        try {
            $conn = get_db_connection();
            $conn->beginTransaction(); // Start transaction

            // Validate required fields
            $required_fields = ['title', 'start_date', 'end_date', 'venue_id', 'category'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                }
            }

            // Additional validation
            if (strtotime($_POST['end_date']) <= strtotime($_POST['start_date'])) {
                throw new Exception('End date must be after the start date.');
            }

            // Prepare data for insertion
            $params = [
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description'] ?? null),
                'venue_id' => (int)$_POST['venue_id'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'category' => $_POST['category'],
                'status' => 'pending', // All new events require approval
                'created_by' => get_current_user_id(),
                'max_participants' => !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null,
                'budget' => !empty($_POST['budget']) ? (float)$_POST['budget'] : null,
                'event_access_level' => $_POST['event_access_level'],
                'department_id' => $_POST['event_access_level'] === 'department_only' ? (int)$_POST['department_id'] : null
            ];

            // Additional validation for department-only events
            if ($params['event_access_level'] === 'department_only' && empty($params['department_id'])) {
                throw new Exception('Department is required for department-only events.');
            }

            $sql = "INSERT INTO events (title, description, venue_id, start_date, end_date, category, status, created_by, max_participants, budget, event_access_level, department_id) 
                    VALUES (:title, :description, :venue_id, :start_date, :end_date, :category, :status, :created_by, :max_participants, :budget, :event_access_level, :department_id)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            // Get the ID of the newly created event
            $event_id = $conn->lastInsertId();

            // Handle staff assignments
            if (!empty($_POST['assigned_staff']) && is_array($_POST['assigned_staff'])) {
                $assigned_staff_ids = $_POST['assigned_staff'];
                $staff_stmt = $conn->prepare("INSERT INTO event_staff (event_id, user_id, assigned_by) VALUES (:event_id, :user_id, :assigned_by)");

                foreach ($assigned_staff_ids as $staff_id) {
                    if (empty($staff_id)) continue;
                    $staff_id = (int)$staff_id;

                    $staff_stmt->execute([
                        'event_id' => $event_id,
                        'user_id' => $staff_id,
                        'assigned_by' => $params['created_by']
                    ]);
                }
            }

            // Log the activity
            logUserActivity('create_event', 'Submitted a new event for approval: ' . $params['title'] . ' (ID: ' . $event_id . ')');
            
            // Commit the transaction
            $conn->commit();

            $_SESSION['success'] = 'Event submitted for approval successfully!';
            header('Location: ' . base_url('views/events/index.php'));
            exit();

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack(); // Rollback on error
            }
            $_SESSION['error'] = 'Error creating event: ' . $e->getMessage();
            header('Location: ' . base_url('views/events/create.php')); // Redirect back to form
            exit();
        }
    }

    if ($action === 'add_staff') {
        if (!has_permission('assign_staff')) {
            $_SESSION['error'] = 'You do not have permission to assign staff.';
            redirect_to_event_details($_POST['event_id'] ?? null);
        }

        try {
            $conn = get_db_connection();
            $event_id = (int)$_POST['event_id'];
            $user_id = (int)$_POST['user_id'];

            if (empty($event_id) || empty($user_id)) {
                throw new Exception("Event ID and User ID are required.");
            }

            // Check if already assigned
            $check_stmt = $conn->prepare("SELECT id FROM event_staff WHERE event_id = :event_id AND user_id = :user_id");
            $check_stmt->execute([':event_id' => $event_id, ':user_id' => $user_id]);
            if ($check_stmt->fetch()) {
                throw new Exception("This staff member is already assigned to the event.");
            }

            $sql = "INSERT INTO event_staff (event_id, user_id, assigned_by) VALUES (:event_id, :user_id, :assigned_by)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':event_id' => $event_id,
                ':user_id' => $user_id,
                ':assigned_by' => get_current_user_id()
            ]);

            logUserActivity('assign_staff', "Assigned user ID $user_id to event ID $event_id.");
            $_SESSION['success'] = 'Staff member assigned successfully.';

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error assigning staff: ' . $e->getMessage();
        }
        
        redirect_to_event_details($_POST['event_id'] ?? null);
    }

    if ($action === 'remove_staff') {
        if (!has_permission('assign_staff')) {
            $_SESSION['error'] = 'You do not have permission to remove staff.';
            redirect_to_event_details($_POST['event_id'] ?? null);
        }

        try {
            $conn = get_db_connection();
            $event_id = (int)$_POST['event_id'];
            $user_id = (int)$_POST['user_id'];

            if (empty($event_id) || empty($user_id)) {
                throw new Exception("Event ID and User ID are required.");
            }

            $sql = "DELETE FROM event_staff WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':event_id' => $event_id,
                ':user_id' => $user_id
            ]);
            
            logUserActivity('remove_staff', "Removed user ID $user_id from event ID $event_id.");
            $_SESSION['success'] = 'Staff member removed successfully.';

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error removing staff: ' . $e->getMessage();
        }

        redirect_to_event_details($_POST['event_id'] ?? null);
    }

    if ($action === 'cancel_registration') {
        if (!has_permission('cancel_reservation')) {
            $_SESSION['error'] = 'You do not have permission to cancel your registration.';
            redirect_to_event_details($_POST['event_id'] ?? null);
        }

        try {
            $conn = get_db_connection();
            $event_id = (int)$_POST['event_id'];
            $user_id = get_current_user_id();

            if (empty($event_id) || empty($user_id)) {
                throw new Exception("Event ID and User ID are required.");
            }

            $sql = "UPDATE event_participants SET status = 'cancelled' WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['event_id' => $event_id, 'user_id' => $user_id]);
            
            logUserActivity('cancel_registration', "Cancelled registration for event ID $event_id.");
            $_SESSION['success'] = 'Your registration for the event has been successfully cancelled.';

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error cancelling registration: ' . $e->getMessage();
        }

        redirect_to_event_details($_POST['event_id'] ?? null);
    }

    if ($action === 'cancel_event') {
        if (!has_permission('delete_event')) {
            $_SESSION['error'] = 'You do not have permission to cancel this event.';
            redirect_to_event_details($_POST['event_id'] ?? null);
        }

        try {
            $conn = get_db_connection();
            $conn->beginTransaction();

            $event_id = (int)$_POST['event_id'];
            if (empty($event_id)) {
                throw new Exception("Event ID is required.");
            }

            // Update event status to cancelled
            $update_event_sql = "UPDATE events SET status = 'cancelled' WHERE id = :event_id";
            $update_stmt = $conn->prepare($update_event_sql);
            $update_stmt->execute(['event_id' => $event_id]);

            // Fetch event title for notification
            $event_title_stmt = $conn->prepare("SELECT title FROM events WHERE id = ?");
            $event_title_stmt->execute([$event_id]);
            $event_title = $event_title_stmt->fetchColumn();

            // Fetch all registered participants to notify them
            $participants_sql = "SELECT user_id FROM event_participants WHERE event_id = :event_id AND status = 'registered'";
            $participants_stmt = $conn->prepare($participants_sql);
            $participants_stmt->execute(['event_id' => $event_id]);
            $participant_ids = $participants_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Send notification to all participants
            $notification_message = "The event '{$event_title}' has been cancelled.";
            foreach ($participant_ids as $user_id) {
                Notification::create($user_id, $notification_message, base_url("views/events/details.php?id={$event_id}"));
            }

            logUserActivity('cancel_event', "Cancelled the entire event: {$event_title} (ID: $event_id).");
            $_SESSION['success'] = 'The event has been successfully cancelled and all participants have been notified.';
            
            $conn->commit();

        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = 'Error cancelling the event: ' . $e->getMessage();
        }

        redirect_to_event_details($_POST['event_id'] ?? null);
    }
}

// Helper function to redirect back to the details page
function redirect_to_event_details($event_id) {
    if ($event_id) {
        header('Location: ' . base_url("views/events/details.php?id={$event_id}"));
    } else {
        header('Location: ' . base_url('views/dashboard.php'));
    }
    exit();
}

// Handle GET requests for approving/rejecting events
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $event_id = $_GET['id'] ?? null;

    if (!$event_id || !in_array($action, ['approve_event', 'reject_event'])) {
        header('Location: ' . base_url('views/dashboard.php'));
        exit();
    }

    if (!has_permission('approve_reject_events')) {
        $_SESSION['error'] = 'You do not have permission to perform this action.';
        header('Location: ' . base_url('views/dashboard.php'));
        exit();
    }

    $new_status = ($action === 'approve_event') ? 'approved' : 'rejected';
    $log_action = ($action === 'approve_event') ? 'approve_event' : 'reject_event';

    try {
        $conn = get_db_connection();
        
        $sql = "UPDATE events SET status = :status, approved_by = :approved_by WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'status' => $new_status,
            'approved_by' => get_current_user_id(),
            'id' => $event_id
        ]);

        // If approved, send notifications to assigned staff
        if ($new_status === 'approved') {
            // Fetch assigned staff
            $staff_stmt = $conn->prepare("SELECT user_id FROM event_staff WHERE event_id = :event_id");
            $staff_stmt->execute(['event_id' => $event_id]);
            $assigned_staff_ids = $staff_stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($assigned_staff_ids)) {
                // Fetch event details for the notification message
                $event_details_stmt = $conn->prepare("
                    SELECT e.title, e.start_date, u.full_name as creator_name, v.name as venue_name
                    FROM events e
                    JOIN users u ON e.created_by = u.id
                    LEFT JOIN venues v ON e.venue_id = v.id
                    WHERE e.id = :event_id
                ");
                $event_details_stmt->execute(['event_id' => $event_id]);
                $event_details = $event_details_stmt->fetch(PDO::FETCH_ASSOC);

                $notification_message = "Your assigned event '" . htmlspecialchars($event_details['title']) . "' has been approved.";
                
                $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, event_id, message, is_read) VALUES (:user_id, :event_id, :message, 0)");
                
                $pusher = null;
                if (class_exists('Pusher\\Pusher')) {
                    $pusher = new Pusher\Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID, ['cluster' => PUSHER_APP_CLUSTER, 'useTLS' => true]);
                }

                foreach ($assigned_staff_ids as $staff_id) {
                    $notification_stmt->execute([
                        'user_id' => $staff_id,
                        'event_id' => $event_id,
                        'message' => $notification_message
                    ]);
                    $notification_id = $conn->lastInsertId();

                    if ($pusher) {
                        try {
                            $channel_name = 'private-user-notifications-' . $staff_id;
                            $pusher->trigger(
                                $channel_name,
                                'new-notification',
                                [
                                    'id' => $notification_id,
                                    'event_id' => $event_id,
                                    'message' => $notification_message,
                                    'venue' => $event_details['venue_name'],
                                    'start_date' => date('M j, g:i A', strtotime($event_details['start_date']))
                                ]
                            );
                        } catch (Exception $e) {
                            error_log("Pusher trigger failed for user {$staff_id}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        logUserActivity($log_action, "Set event ID $event_id to $new_status.");
        $_SESSION['success'] = "Event has been successfully " . $new_status . ".";

    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating event: " . $e->getMessage();
    }

    header('Location: ' . base_url('views/approvals/index.php'));
    exit();
}

// Fallback redirect
header('Location: ' . base_url('views/dashboard.php'));
exit(); 