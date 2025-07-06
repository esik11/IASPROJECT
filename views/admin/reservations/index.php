<?php
require_once '../../../config/config.php';
require_once '../../../config/helpers.php';
require_once '../../../models/ActivityLog.php';

// Check if the user is logged in and has the required permission
is_logged_in();
if (!has_permission('manage_venue_reservations')) {
    // Or handle the unauthorized access in a way that fits your application
    header("Location: " . BASE_URL . "views/dashboard.php");
    exit;
}

$link = get_db_connection();
$page_title = 'Manage Venue Reservations';

// Fetch all reservations that need action (pending)
$reservations = [];
$sql = "
    SELECT r.id, r.title, r.start_time, r.end_time, r.status, u.full_name, v.name as venue_name
    FROM venue_reservations r
    JOIN users u ON r.user_id = u.id
    JOIN venues v ON r.venue_id = v.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
";

try {
    $stmt = $link->query($sql);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle query error
    $_SESSION['error_message'] = "Error fetching reservations: " . $e->getMessage();
    $reservations = [];
}

?>
<?php include '../../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar is included in header.php -->

        <!-- Main content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Venue Reservations</h1>
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
                    Pending Reservation Requests
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Venue</th>
                                    <th>Purpose</th>
                                    <th>Reserved By</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No pending reservations.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservations as $res): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($res['venue_name']) ?></td>
                                        <td><?= htmlspecialchars($res['title']) ?></td>
                                        <td><?= htmlspecialchars($res['full_name']) ?></td>
                                        <td><?= date('M j, Y, g:i A', strtotime($res['start_time'])) ?></td>
                                        <td><?= date('M j, Y, g:i A', strtotime($res['end_time'])) ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= ucfirst(htmlspecialchars($res['status'])) ?></span></td>
                                        <td>
                                            <form action="process.php" method="POST" class="d-inline">
                                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                <input type="hidden" name="redirect_to" value="index.php">
                                                <button type="submit" name="action" value="approve_reservation" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form action="process.php" method="POST" class="d-inline">
                                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                <input type="hidden" name="redirect_to" value="index.php">
                                                <input type="hidden" name="rejection_reason" value="Venue not available."> <!-- Or use a modal to get reason -->
                                                <button type="submit" name="action" value="reject_reservation" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<?php $link = null; // Close connection ?> 