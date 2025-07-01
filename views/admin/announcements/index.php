<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../includes/header.php';

// Security Check
if (!has_permission('post_announcements')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to manage announcements.</p>";
    exit();
}

$conn = get_db_connection();
try {
    $stmt = $conn->query("
        SELECT a.*, u.full_name AS author_name
        FROM announcements a
        JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to fetch announcements: ' . $e->getMessage();
    $announcements = [];
}
?>

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Manage Announcements</h5>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Announcement
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                    <td><?php echo htmlspecialchars($announcement['author_name']); ?></td>
                                    <td><?php echo date('M j, Y H:i', strtotime($announcement['created_at'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $announcement['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="process.php?action=delete_announcement&id=<?php echo $announcement['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this announcement?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No announcements have been posted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?> 