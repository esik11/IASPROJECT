<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../includes/header.php';

// Security Check: Ensure the user has permission to view announcements
if (!has_permission('view_announcements')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>";
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
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h2 class="text-center mb-4">Announcements</h2>

            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            <hr>
                            <div class="card-text">
                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            Posted by <?php echo htmlspecialchars($announcement['author_name']); ?>
                            on <?php echo date('F j, Y, g:i a', strtotime($announcement['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted">
                    <p>There are no announcements at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 