<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../includes/header.php';

// Security Check
if (!has_permission('manage_venues')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>";
    exit();
}

$venue_id = $_GET['id'] ?? null;
if (!$venue_id) {
    $_SESSION['error'] = 'No venue ID specified.';
    redirect('views/admin/venues/index.php');
}

$conn = get_db_connection();
try {
    $stmt = $conn->prepare("SELECT * FROM venues WHERE id = ?");
    $stmt->execute([$venue_id]);
    $venue = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$venue) {
        $_SESSION['error'] = 'Venue not found.';
        redirect('views/admin/venues/index.php');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to fetch venue details: ' . $e->getMessage();
    redirect('views/admin/venues/index.php');
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Venue</h5>
                </div>
                <div class="card-body">
                    <form action="process.php" method="POST">
                        <input type="hidden" name="action" value="update_venue">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($venue['id']); ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label">Venue Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($venue['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($venue['location'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo htmlspecialchars($venue['capacity'] ?? ''); ?>" min="1">
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Venue</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 