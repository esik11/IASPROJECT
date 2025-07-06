<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

$response = [
    'available' => false,
    'message' => 'Invalid input.'
];

if (isset($_POST['venue_id'], $_POST['start_date'], $_POST['end_date'])) {
    $venue_id = (int)$_POST['venue_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $event_id_to_exclude = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;

    // Basic validation
    if (empty($venue_id) || empty($start_date) || empty($end_date)) {
        $response['message'] = 'Missing required fields.';
        echo json_encode($response);
        exit;
    }

    try {
        // Re-format dates to ensure consistency, assuming 'Y-m-d\TH:i' from datetime-local input
        $start_dt_obj = new DateTime($start_date);
        $end_dt_obj = new DateTime($end_date);

        $start_date_sql = $start_dt_obj->format('Y-m-d H:i:s');
        $end_date_sql = $end_dt_obj->format('Y-m-d H:i:s');

        if ($end_dt_obj <= $start_dt_obj) {
             $response['message'] = 'End date must be after the start date.';
             echo json_encode($response);
             exit;
        }

        if (isVenueAvailable($venue_id, $start_date_sql, $end_date_sql, $event_id_to_exclude)) {
            $response['available'] = true;
            $response['message'] = 'This venue is available for the selected time.';
        } else {
            $response['available'] = false;
            $response['message'] = 'This venue is already booked for the selected time. Please choose another time or venue.';
        }

    } catch (Exception $e) {
        // In a production environment, you might want to log this error instead of exposing it.
        $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
    }
}

echo json_encode($response); 