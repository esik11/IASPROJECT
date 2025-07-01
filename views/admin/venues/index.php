<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../includes/header.php';

// Security Check: Ensure the user has permission to manage venues
if (!has_permission('manage_venues')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>";
    exit();
}

$conn = get_db_connection();
try {
    $stmt = $conn->query("SELECT * FROM venues ORDER BY name");
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to fetch venues: ' . $e->getMessage();
    $venues = [];
}
?>

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Manage Venues</h5>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Venue
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($venues)): ?>
                            <?php foreach ($venues as $venue): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($venue['name']); ?></td>
                                    <td><?php echo htmlspecialchars($venue['location'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($venue['capacity'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $venue['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="process.php?action=delete_venue&id=<?php echo $venue['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this venue?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No venues found. Add one to get started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 