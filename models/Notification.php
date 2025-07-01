<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class Notification {

    /**
     * Creates a new notification, saves it to the database, and sends a real-time push event.
     *
     * @param int $user_id The ID of the user to notify.
     * @param string $message The notification message.
     * @param string|null $link An optional link for the notification.
     * @return bool True on success, false on failure.
     */
    public static function create($user_id, $message, $link = null) {
        // Since this can be called from various scripts, ensure get_db_connection is available
        if (!function_exists('get_db_connection')) {
            require_once __DIR__ . '/../config/database.php';
        }
        $conn = get_db_connection();

        try {
            $conn->beginTransaction();

            $sql = "INSERT INTO notifications (user_id, message, link, is_read) VALUES (?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $message, $link]);
            $notification_id = $conn->lastInsertId();

            // Trigger Pusher for real-time update if Pusher is configured and available
            if (class_exists('Pusher\\Pusher') && defined('PUSHER_APP_KEY') && PUSHER_APP_KEY) {
                $pusher = new Pusher\Pusher(
                    PUSHER_APP_KEY,
                    PUSHER_APP_SECRET,
                    PUSHER_APP_ID,
                    ['cluster' => PUSHER_APP_CLUSTER, 'useTLS' => true]
                );

                $channel_name = 'private-user-notifications-' . $user_id;
                $pusher->trigger(
                    $channel_name,
                    'new-notification',
                    [
                        'id' => $notification_id,
                        'message' => htmlspecialchars($message),
                        'link' => $link,
                        'is_read' => false,
                        'created_at' => date('M j, Y, g:i A')
                    ]
                );
            }

            $conn->commit();
            return true;

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Failed to create notification for user {$user_id}: " . $e->getMessage());
            return false;
        }
    }
} 