<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../includes/header.php';

// Security Check
if (!has_permission('approve_reject_events')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>";
    exit();
}

$conn = get_db_connection();
try {
    // Fetch all events with 'pending' status, along with the creator's name and venue name
    $stmt = $conn->prepare("
        SELECT e.*, u.full_name AS creator_name, v.name AS venue_name
        FROM events e
        JOIN users u ON e.created_by = u.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE e.status = 'pending'
        ORDER BY e.created_at DESC
    ");
    $stmt->execute();
    $pending_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to fetch pending events: ' . $e->getMessage();
    $pending_events = [];
}
?>

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Pending Event Approvals</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Event Title</th>
                            <th>Submitted By</th>
                            <th>Dates</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_events)): ?>
                            <?php foreach ($pending_events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['creator_name']); ?></td>
                                    <td>
                                        <small>
                                            <strong>Start:</strong> <?php echo date('M j, Y g:i A', strtotime($event['start_date'])); ?><br>
                                            <strong>End:</strong> <?php echo date('M j, Y g:i A', strtotime($event['end_date'])); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['venue_name'] ?? 'N/A'); ?></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo ucfirst(htmlspecialchars($event['status'])); ?></span></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#eventDetailsModal-<?php echo $event['id']; ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal for event details -->
                                <div class="modal fade" id="eventDetailsModal-<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="eventDetailsModalLabel-<?php echo $event['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="eventDetailsModalLabel-<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['title']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Description:</strong></p>
                                                        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue_name'] ?? 'N/A'); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Category:</strong> <?php echo htmlspecialchars($event['category']); ?></p>
                                                        <p><strong>Max Participants:</strong> <?php echo htmlspecialchars($event['max_participants'] ?? 'N/A'); ?></p>
                                                        <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($event['creator_name']); ?></p>
                                                        <p><strong>Submission Date:</strong> <?php echo date('M j, Y H:i', strtotime($event['created_at'])); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="<?php echo base_url('views/events/process.php?action=approve_event&id=' . $event['id']); ?>" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this event?');">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                                <a href="<?php echo base_url('views/events/process.php?action=reject_event&id=' . $event['id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this event?');">
                                                    <i class="fas fa-times"></i> Reject
                                                </a>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No events are currently pending approval.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 