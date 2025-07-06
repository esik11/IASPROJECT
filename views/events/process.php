<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';

// Handle POST requests for event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff') {
        if (!has_permission('assign_staff')) {
            $_SESSION['error'] = 'You do not have permission to assign staff.';
            redirect('views/dashboard.php');
        }

        $event_id = (int)$_POST['event_id'];
        $user_id = (int)$_POST['user_id'];
        
        try {
            $conn = get_db_connection();
            // Check if already assigned
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM event_staff WHERE event_id = ? AND user_id = ?");
            $check_stmt->execute([$event_id, $user_id]);
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("This staff member is already assigned to this event.");
            }

            $stmt = $conn->prepare("INSERT INTO event_staff (event_id, user_id, assigned_by) VALUES (?, ?, ?)");
            $stmt->execute([$event_id, $user_id, get_current_user_id()]);
            
            logUserActivity('assign_staff', "Assigned staff member ID $user_id to event ID $event_id.");
            $_SESSION['success'] = 'Staff member assigned successfully.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error assigning staff: ' . $e->getMessage();
        }
        redirect('views/events/details.php?id=' . $event_id);
        exit();
    }

    if ($action === 'remove_staff') {
        if (!has_permission('assign_staff')) {
            $_SESSION['error'] = 'You do not have permission to remove staff.';
            redirect('views/dashboard.php');
        }

        $event_id = (int)$_POST['event_id'];
        $user_id = (int)$_POST['user_id'];

        try {
            $conn = get_db_connection();
            $stmt = $conn->prepare("DELETE FROM event_staff WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$event_id, $user_id]);

            if ($stmt->rowCount() > 0) {
                logUserActivity('remove_staff', "Removed staff member ID $user_id from event ID $event_id.");
                $_SESSION['success'] = 'Staff member removed successfully.';
            } else {
                throw new Exception("Staff member not found or could not be removed.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error removing staff: ' . $e->getMessage();
        }
        redirect('views/events/details.php?id=' . $event_id);
        exit();
    }

    if ($action === 'submit_idea') {
        if (!has_permission('submit_event_idea')) {
            $_SESSION['error'] = 'You do not have permission to submit an event idea.';
            redirect('views/dashboard.php');
        }
        
        try {
            $conn = get_db_connection();
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            
            if (empty($title) || empty($description)) {
                throw new Exception('Title and description are required.');
            }

            $sql = "INSERT INTO event_ideas (title, description, submitted_by) VALUES (:title, :description, :submitted_by)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':submitted_by' => get_current_user_id()
            ]);

            logUserActivity('submit_idea', "Submitted a new event idea: " . $title);
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

            // **Start Date in Past Check**
            if (new DateTime($_POST['start_date']) < new DateTime()) {
                throw new Exception('The start date cannot be in the past. Please select a future date.');
            }

            // **Venue Availability Check**
            $start_date_sql = (new DateTime($_POST['start_date']))->format('Y-m-d H:i:s');
            $end_date_sql = (new DateTime($_POST['end_date']))->format('Y-m-d H:i:s');
            if (!isVenueAvailable((int)$_POST['venue_id'], $start_date_sql, $end_date_sql)) {
                throw new Exception('The selected venue is not available for the chosen time frame. Please go back and select a different time or venue.');
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
                'max_participants' => !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null
            ];

            $sql = "INSERT INTO events (title, description, venue_id, start_date, end_date, category, status, created_by, max_participants) 
                    VALUES (:title, :description, :venue_id, :start_date, :end_date, :category, :status, :created_by, :max_participants)";
            
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

    if ($action === 'update_event') {
        if (!has_permission('edit_event')) {
            $_SESSION['error'] = 'You do not have permission to edit events.';
            header('Location: ' . base_url('views/dashboard.php'));
            exit();
        }

        $event_id = (int)$_POST['event_id'];
        $conn = get_db_connection();
        // Assume all relevant fields are posted from an edit form
        $sql = "UPDATE events SET title = :title, description = :description, venue_id = :venue_id, start_date = :start_date, end_date = :end_date, category = :category, max_participants = :max_participants WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $_POST['title'],
            ':description' => $_POST['description'],
            ':venue_id' => (int)$_POST['venue_id'],
            ':start_date' => $_POST['start_date'],
            ':end_date' => $_POST['end_date'],
            ':category' => $_POST['category'],
            ':max_participants' => (int)$_POST['max_participants'],
            ':id' => $event_id
        ]);

        logUserActivity('update_event', 'Updated event ID ' . $event_id);
        $_SESSION['success'] = 'Event updated successfully.';
        header('Location: ' . base_url('views/events/details.php?id=' . $event_id));
        exit();
    }

    if ($action === 'cancel_event') {
        if (!has_permission('cancel_event')) {
            $_SESSION['error'] = 'You do not have permission to cancel events.';
            header('Location: ' . base_url('views/dashboard.php'));
            exit();
        }
        $event_id = (int) $_POST['event_id'];
        $conn = get_db_connection();
        $stmt = $conn->prepare("UPDATE events SET status = 'cancelled' WHERE id = :id");
        $stmt->execute([':id' => $event_id]);
        logUserActivity('cancel_event', 'Cancelled event ID ' . $event_id);
        $_SESSION['success'] = 'Event cancelled successfully.';
        header('Location: ' . base_url('views/events/index.php'));
        exit();
    }

    if ($action === 'register_for_event') {
        $event_id = (int)$_POST['event_id'];
        $user_id = get_current_user_id();
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (:event_id, :user_id, 'registered')");
        $stmt->execute([':event_id' => $event_id, ':user_id' => $user_id]);
        logUserActivity('register_for_event', "User registered for event ID: " . $event_id);
        $_SESSION['success'] = 'Successfully registered for the event.';
        header('Location: ' . base_url('views/events/details.php?id=' . $event_id));
        exit();
    }

    if ($action === 'cancel_event_registration') {
        $event_id = (int)$_POST['event_id'];
        $user_id = get_current_user_id();
        $conn = get_db_connection();
        $stmt = $conn->prepare("UPDATE event_participants SET status = 'cancelled' WHERE event_id = :event_id AND user_id = :user_id");
        $stmt->execute([':event_id' => $event_id, ':user_id' => $user_id]);
        logUserActivity('cancel_event_registration', "User cancelled registration for event ID: " . $event_id);
        $_SESSION['success'] = 'Your registration has been cancelled.';
        header('Location: ' . base_url('views/events/details.php?id=' . $event_id));
        exit();
    }
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