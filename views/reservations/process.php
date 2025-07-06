<?php
require_once '../../config/config.php';
require_once '../../config/helpers.php';
require_once '../../models/ActivityLog.php';
require_once '../../models/Notification.php';

is_logged_in();
$link = get_db_connection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create_reservation') {
        // Validation
        $venue_id = (int)$_POST['venue_id'];
        $title = $_POST['title'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        if (empty($venue_id) || empty($title) || empty($start_time) || empty($end_time)) {
            $_SESSION['error_message'] = "All fields are required.";
            header("Location: index.php");
            exit;
        }

        if (strtotime($start_time) >= strtotime($end_time)) {
            $_SESSION['error_message'] = "The reservation start time must be before the end time.";
            header("Location: index.php");
            exit;
        }

        try {
            // Check for conflicting reservations (confirmed or pending)
            $conflict_sql = "SELECT id FROM venue_reservations WHERE venue_id = :venue_id AND status IN ('confirmed', 'pending') AND (
                (:start_time < end_time) AND (:end_time > start_time)
            )";
            $stmt = $link->prepare($conflict_sql);
            $stmt->execute([
                ':venue_id' => $venue_id,
                ':start_time' => $start_time,
                ':end_time' => $end_time
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['error_message'] = "This time slot is already booked or has a pending reservation. Please choose a different time.";
                header("Location: index.php");
                exit;
            }

            // Insert the new reservation as 'pending'
            $insert_sql = "INSERT INTO venue_reservations (venue_id, user_id, title, start_time, end_time, status) VALUES (:venue_id, :user_id, :title, :start_time, :end_time, 'pending')";
            $stmt = $link->prepare($insert_sql);
            
            if ($stmt->execute([
                ':venue_id' => $venue_id,
                ':user_id' => $user_id,
                ':title' => $title,
                ':start_time' => $start_time,
                ':end_time' => $end_time
            ])) {
                $reservation_id = $link->lastInsertId();
                $_SESSION['success_message'] = "Your reservation request has been submitted and is pending approval.";
                
                // Log activity
                logUserActivity("Reservation Request", "User requested to reserve venue ID: $venue_id for '$title'");

                // Notify users with permission to manage reservations
                $venue_name = get_venue_name($venue_id, $link);
                $requester_name = get_user_full_name($user_id, $link);
                $message = "New reservation for venue '$venue_name' by $requester_name needs your approval.";
                $notification_link = 'views/admin/reservations/index.php';
                notifyUsersWithPermission('manage_venue_reservations', $message, $notification_link);

            } else {
                $_SESSION['error_message'] = "Failed to submit reservation request. Please try again.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
        // Redirect to the new history page after creation
        header("Location: my_reservations.php");
        exit;

    } elseif ($_POST['action'] === 'cancel_reservation') {
        $reservation_id = (int)$_POST['reservation_id'];

        if (empty($reservation_id)) {
            $_SESSION['error_message'] = "Invalid reservation ID.";
            header("Location: my_reservations.php");
            exit;
        }

        try {
            // Verify the user owns this reservation before cancelling
            $stmt = $link->prepare("SELECT id FROM venue_reservations WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $reservation_id, ':user_id' => $user_id]);
            
            if ($stmt->rowCount() === 0) {
                $_SESSION['error_message'] = "You do not have permission to cancel this reservation.";
                header("Location: my_reservations.php");
                exit;
            }

            // Update the status to 'cancelled'
            $update_sql = "UPDATE venue_reservations SET status = 'cancelled' WHERE id = :id";
            $stmt = $link->prepare($update_sql);
            
            if ($stmt->execute([':id' => $reservation_id])) {
                $_SESSION['success_message'] = "Reservation cancelled successfully.";
                
                // Log activity using the helper function
                logUserActivity("Reservation Cancelled", "User cancelled reservation ID: $reservation_id");

            } else {
                $_SESSION['error_message'] = "Failed to cancel reservation.";
            }

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }

        header("Location: my_reservations.php");
        exit;
    }
    // Fallback redirect
    header("Location: index.php");
    exit;
} else {
    // Redirect if accessed directly without POST
    header("Location: index.php");
    exit;
}

$link = null; // Close connection
?>
 