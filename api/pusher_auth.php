<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

if (!is_logged_in()) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Forbidden';
    exit();
}

$pusher = new Pusher\Pusher(
    PUSHER_APP_KEY,
    PUSHER_APP_SECRET,
    PUSHER_APP_ID,
    ['cluster' => PUSHER_APP_CLUSTER]
);

// The channel name is sent as a POST parameter
$channel_name = $_POST['channel_name'];
// The socket ID is also sent as a POST parameter
$socket_id = $_POST['socket_id'];

// Basic validation
if (empty($channel_name) || empty($socket_id)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Missing parameters';
    exit;
}

// Extract user ID from channel name (e.g., from "private-user-notifications-123")
$prefix = 'private-user-notifications-';
if (strpos($channel_name, $prefix) === 0) {
    $channel_user_id = substr($channel_name, strlen($prefix));
    
    // Check if the currently logged-in user is the one trying to subscribe
    if ($channel_user_id == get_current_user_id()) {
        $auth = $pusher->socket_auth($channel_name, $socket_id);
        echo $auth;
    } else {
        header('HTTP/1.0 403 Forbidden');
        echo 'Forbidden';
    }
} else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid channel name';
} 