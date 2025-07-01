<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../includes/header.php';

// For now, we assume 'manage_users' is a proxy for high-level admin rights.
// Ideally, a new permission 'manage_event_ideas' should be created.
if (!has_permission('manage_event_ideas')) { 
    echo "<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>";
    exit();
}

$conn = get_db_connection();
try {
    $stmt = $conn->query("
        SELECT i.*, u.full_name AS submitted_by_name
        FROM event_ideas i
        JOIN users u ON i.submitted_by = u.id
        ORDER BY i.created_at DESC
    ");
    $ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to fetch event ideas: ' . $e->getMessage();
    $ideas = [];
}

// Function to determine badge color based on status
function get_status_badge($status) {
    switch ($status) {
        case 'new': return 'bg-primary';
        case 'under_review': return 'bg-info text-dark';
        case 'approved': return 'bg-success';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Manage Event Ideas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Submitted By</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ideas)): ?>
                            <?php foreach ($ideas as $idea): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($idea['title']); ?></td>
                                    <td><?php echo htmlspecialchars($idea['submitted_by_name']); ?></td>
                                    <td><?php echo date('M j, Y H:i', strtotime($idea['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo get_status_badge($idea['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $idea['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#ideaDetailsModal-<?php echo $idea['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal for idea details -->
                                <div class="modal fade" id="ideaDetailsModal-<?php echo $idea['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo htmlspecialchars($idea['title']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($idea['submitted_by_name']); ?></p>
                                                <p><strong>Description:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($idea['description'])); ?></p>
                                            </div>
                                            <div class="modal-footer justify-content-between">
                                                <div class="d-flex gap-2">
                                                    <!-- Approval Form -->
                                                    <form action="process.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="approve_idea">
                                                        <input type="hidden" name="idea_id" value="<?php echo $idea['id']; ?>">
                                                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this idea?');">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>

                                                    <!-- Rejection Form -->
                                                    <form action="process.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="reject_idea">
                                                        <input type="hidden" name="idea_id" value="<?php echo $idea['id']; ?>">
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this idea?');">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Under Review Form -->
                                                     <?php if ($idea['status'] === 'new'): ?>
                                                    <form action="process.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="review_idea">
                                                        <input type="hidden" name="idea_id" value="<?php echo $idea['id']; ?>">
                                                        <button type="submit" class="btn btn-info">
                                                            <i class="fas fa-search"></i> Mark as Under Review
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No event ideas have been submitted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?> 