<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Pusher\Pusher;

function send_notification($user_id, $event_id, $message, $venue, $start_date) {
    $options = [
        'cluster' => PUSHER_APP_CLUSTER,
        'useTLS' => true
    ];
    
    try {
        $pusher = new Pusher(
            PUSHER_APP_KEY,
            PUSHER_APP_SECRET,
            PUSHER_APP_ID,
            $options
        );

        $notificationData = [
            'event_id' => $event_id,
            'message' => $message,
            'venue' => $venue,
            'start_date' => date('M j, g:i A', strtotime($start_date))
        ];

        // Persist to database
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, event_id, message, venue, start_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $event_id, $message, $venue, $start_date]);
        $notification_id = $conn->lastInsertId();
        
        $notificationData['id'] = $notification_id;

        // Determine the channel based on the user ID
        $channel = 'private-user-notifications-' . $user_id;

        // Trigger the event on the user-specific channel
        $pusher->trigger($channel, 'new-notification', $notificationData);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to send notification: " . $e->getMessage());
        return false;
    }
} 