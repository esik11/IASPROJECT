<?php
require_once __DIR__ . '/../../config/config.php';

$page_title = "Events Calendar";
include_once __DIR__ . '/../includes/header.php';

// Security Check: Ensure the user has permission to view the calendar
if (!has_permission('view_event_calendar')) {
    // Redirect or show a forbidden message
    echo "<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>";
    exit();
}
?>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<style>
    /* Custom styling for the calendar */
    #calendar {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 10px;
    }
    .fc-event {
        cursor: pointer;
    }
</style>

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Event Calendar</h5>
        </div>
        <div class="card-body">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<!-- Modal for displaying event details -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventDetailModalLabel">Event Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 id="eventTitle"></h6>
        <p><strong class="text-muted">Starts:</strong> <span id="eventStart"></span></p>
        <p><strong class="text-muted">Ends:</strong> <span id="eventEnd"></span></p>
        <p id="eventDescription"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var eventDetailModal = new bootstrap.Modal(document.getElementById('eventDetailModal'));

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: '<?php echo base_url('api/events.php'); ?>',
        eventDidMount: function(info) {
            let color = '';
            switch (info.event.extendedProps.status) {
                case 'approved':
                    color = '#28a745'; // Green
                    break;
                case 'pending':
                    color = '#ffc107'; // Yellow
                    break;
                case 'rejected':
                    color = '#dc3545'; // Red
                    break;
                case 'cancelled':
                    color = '#6c757d'; // Gray
                    break;
                case 'completed':
                    color = '#17a2b8'; // Teal
                    break;
                default:
                    color = '#007bff'; // Blue
            }
            if (color) {
                info.el.style.backgroundColor = color;
                info.el.style.borderColor = color;
            }
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // Prevent browser navigation
            window.location.href = '<?php echo base_url('views/events/details.php?id='); ?>' + info.event.id;
        },
        loading: function(isLoading) {
            // Optional: show a loading indicator
            if (isLoading) {
                // You can add a spinner or loading text here
            } else {
                // Hide the spinner
            }
        }
    });

    calendar.render();
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?> 