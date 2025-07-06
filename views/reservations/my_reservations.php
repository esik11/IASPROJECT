<?php
require_once '../../config/config.php';
require_once '../../config/helpers.php';

is_logged_in();
$link = get_db_connection();
$user_id = get_current_user_id();
$page_title = 'My Venue Reservations';

// Fetch all reservations for the current user
$my_reservations = [];
$sql = "
    SELECT 
        r.id, 
        r.title, 
        r.start_time, 
        r.end_time, 
        r.status, 
        r.rejection_reason,
        v.name as venue_name,
        r.created_at
    FROM venue_reservations r
    JOIN venues v ON r.venue_id = v.id
    WHERE r.user_id = :user_id
    ORDER BY r.start_time DESC
";

try {
    $stmt = $link->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $my_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching reservations: " . $e->getMessage();
    $my_reservations = [];
}

?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar is included in header.php -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $page_title ?></h1>
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
                    Your Reservation History
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Venue</th>
                                    <th>Purpose</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($my_reservations)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">You have not made any reservations.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($my_reservations as $res): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($res['venue_name']) ?></td>
                                        <td><?= htmlspecialchars($res['title']) ?></td>
                                        <td><?= date('M j, Y, g:i A', strtotime($res['start_time'])) ?></td>
                                        <td><?= date('M j, Y, g:i A', strtotime($res['end_time'])) ?></td>
                                        <td>
                                            <?php
                                                $status_class = '';
                                                switch ($res['status']) {
                                                    case 'pending': $status_class = 'bg-warning text-dark'; break;
                                                    case 'confirmed': $status_class = 'bg-success'; break;
                                                    case 'rejected': $status_class = 'bg-danger'; break;
                                                    case 'cancelled': $status_class = 'bg-secondary'; break;
                                                }
                                            ?>
                                            <span class="badge <?= $status_class ?>"><?= ucfirst(htmlspecialchars($res['status'])) ?></span>
                                            <?php if ($res['status'] === 'rejected' && !empty($res['rejection_reason'])): ?>
                                                <small class="d-block text-muted">Reason: <?= htmlspecialchars($res['rejection_reason']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (in_array($res['status'], ['pending', 'confirmed']) && has_permission('cancel_reservation')): ?>
                                                <form action="process.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                                    <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                    <button type="submit" name="action" value="cancel_reservation" class="btn btn-danger btn-sm">Cancel</button>
                                                </form>
                                            <?php endif; ?>
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

<?php include '../includes/footer.php'; ?>
<?php $link = null; // Close connection ?> 