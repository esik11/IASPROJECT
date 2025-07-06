<?php
require_once '../../config/config.php';
require_once '../../config/helpers.php';
require_once '../../models/ActivityLog.php'; 

// Ensure user is logged in
is_logged_in();

$link = get_db_connection();

// Fetch all available venues
$venues = [];
$sql = "SELECT id, name, location, capacity FROM venues ORDER BY name ASC";
try {
    $stmt = $link->query($sql);
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error appropriately
    $_SESSION['error_message'] = "Error fetching venues: " . $e->getMessage();
    $venues = [];
}

$page_title = 'Reserve a Venue';
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar is included in header.php -->

        <!-- Main content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Venue Reservation</h1>
            </div>

            <?php if (isset($_SESSION['success_message'])) : ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])) : ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    Make a New Reservation
                </div>
                <div class="card-body">
                    <form action="process.php" method="POST" id="reservationForm">
                        <input type="hidden" name="action" value="create_reservation">
                        
                        <div class="form-group">
                            <label for="venue_id">Select Venue</label>
                            <select class="form-control" id="venue_id" name="venue_id" required>
                                <option value="">-- Choose a Venue --</option>
                                <?php foreach ($venues as $venue): ?>
                                    <option value="<?= htmlspecialchars($venue['id']) ?>">
                                        <?= htmlspecialchars($venue['name']) ?> (Capacity: <?= htmlspecialchars($venue['capacity']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Reservation Purpose / Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="start_time">Start Time</label>
                                <input type="datetime-local" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="end_time">End Time</label>
                                <input type="datetime-local" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Reservation Request</button>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <h2>Venue Schedules</h2>
                <p>Select a venue from the dropdown above to view its existing schedule.</p>
                <!-- Schedule will be loaded here via AJAX -->
                <div id="venueSchedule"></div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php $link = null; // Close connection ?> 