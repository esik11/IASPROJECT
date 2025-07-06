<?php
require_once '../../../config/config.php';
require_once '../../../config/helpers.php';

is_logged_in();
if (!has_permission('manage_venue_reservations')) {
    header("Location: " . BASE_URL . "views/dashboard.php");
    exit;
}

$link = get_db_connection();
$admin_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['reservation_id'])) {
    
    $reservation_id = (int)$_POST['reservation_id'];
    $action = $_POST['action'];
    $redirect_page = $_POST['redirect_to'] ?? 'index.php'; // Default to index.php if not specified

    try {
        // Fetch reservation details to get user_id and title
        $stmt = $link->prepare("SELECT user_id, title, venue_id, start_time, end_time FROM venue_reservations WHERE id = :id");
        $stmt->execute([':id' => $reservation_id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            $_SESSION['error_message'] = "Reservation not found.";
            header("Location: index.php");
            exit;
        }

        $original_user_id = $reservation['user_id'];
        $reservation_title = $reservation['title'];
        $venue_id = $reservation['venue_id'];
        $start_time = $reservation['start_time'];
        $end_time = $reservation['end_time'];

        if ($action === 'approve_reservation') {
            // **Venue Availability Check**
            if (!isVenueAvailable($venue_id, $start_time, $end_time, null, $reservation_id)) {
                $_SESSION['error_message'] = "Cannot approve: This time slot is no longer available as it conflicts with another event or reservation.";
                header("Location: " . $redirect_page);
                exit();
            }

            $update_sql = "UPDATE venue_reservations SET status = 'confirmed' WHERE id = :id";
            $stmt = $link->prepare($update_sql);
            
            if ($stmt->execute([':id' => $reservation_id])) {
                $_SESSION['success_message'] = "Reservation approved successfully.";
                logUserActivity("Reservation Approved", "Approved reservation ID: $reservation_id for '$reservation_title'");
                
                // Notify the original user
                $message = "Your reservation for '".htmlspecialchars($reservation_title)."' has been approved.";
                notifyUserById($original_user_id, $message, 'views/reservations/my_reservations.php');

            } else {
                $_SESSION['error_message'] = "Failed to approve reservation.";
            }

        } elseif ($action === 'reject_reservation') {
            $rejection_reason = $_POST['rejection_reason'] ?? 'No reason provided.';
            
            $update_sql = "UPDATE venue_reservations SET status = 'rejected', rejection_reason = :reason WHERE id = :id";
            $stmt = $link->prepare($update_sql);
            
            if ($stmt->execute([':reason' => $rejection_reason, ':id' => $reservation_id])) {
                $_SESSION['success_message'] = "Reservation rejected successfully.";
                logUserActivity("Reservation Rejected", "Rejected reservation ID: $reservation_id for '$reservation_title'");

                // Notify the original user
                $message = "Your reservation for '".htmlspecialchars($reservation_title)."' has been rejected. Reason: " . htmlspecialchars($rejection_reason);
                notifyUserById($original_user_id, $message, 'views/reservations/my_reservations.php');

            } else {
                $_SESSION['error_message'] = "Failed to reject reservation.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }

    header("Location: " . $redirect_page);
    exit;
} else {
    header("Location: index.php");
    exit;
}

$link = null; // Close connection
?> 