<?php
require_once '../../../config/config.php';
require_once '../../../config/helpers.php';

// Ensure user is logged in and has permission to view all reservations
is_logged_in();
if (!has_permission('view_all_reservation_history')) {
    // If they don't have global permission, maybe they have personal permission?
    if (has_permission('view_reservation_history')) {
        header("Location: " . BASE_URL . "views/reservations/my_reservations.php");
    } else {
        header("Location: " . BASE_URL . "views/dashboard.php");
    }
    exit;
}

$link = get_db_connection();
$page_title = 'All Venue Reservations';

// Search and filter logic
$search_term = $_GET['search'] ?? '';
$where_clauses = [];
$params = [];

if (!empty($search_term)) {
    $where_clauses[] = "(v.name LIKE :search1 OR u.full_name LIKE :search2 OR r.title LIKE :search3)";
    $params[':search1'] = "%$search_term%";
    $params[':search2'] = "%$search_term%";
    $params[':search3'] = "%$search_term%";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Fetch all reservations
$all_reservations = [];
$sql = "
    SELECT 
        r.id, r.title, r.start_time, r.end_time, r.status, 
        u.full_name, v.name as venue_name, r.created_at
    FROM venue_reservations r
    JOIN users u ON r.user_id = u.id
    JOIN venues v ON r.venue_id = v.id
    $where_sql
    ORDER BY r.start_time DESC
";

try {
    $stmt = $link->prepare($sql);
    $stmt->execute($params);
    $all_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching all reservations: " . $e->getMessage();
    $all_reservations = [];
}

?>
<?php include '../../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $page_title ?></h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <form action="history.php" method="GET" class="d-flex">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search by venue, user, or title..." value="<?= htmlspecialchars($search_term) ?>">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Reserved By</th>
                                    <th>Venue</th>
                                    <th>Purpose</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_reservations)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No reservations found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_reservations as $res): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($res['full_name']) ?></td>
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
                                        </td>
                                        <td>
                                            <?php if ($res['status'] === 'pending' && has_permission('manage_venue_reservations')): ?>
                                                <form action="process.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                    <input type="hidden" name="redirect_to" value="history.php">
                                                    <button type="submit" name="action" value="approve_reservation" class="btn btn-success btn-sm" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form action="process.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                    <input type="hidden" name="redirect_to" value="history.php">
                                                    <button type="submit" name="action" value="reject_reservation" class="btn btn-danger btn-sm" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
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

<?php include '../../includes/footer.php'; ?>
<?php $link = null; ?> 