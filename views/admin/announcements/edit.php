<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../includes/header.php';

// Security Check
if (!has_permission('post_announcements')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to manage announcements.</p>";
    exit();
}

$announcement_id = $_GET['id'] ?? null;
if (!$announcement_id) {
    $_SESSION['error'] = 'No announcement ID specified.';
    redirect('views/admin/announcements/index.php');
}

$conn = get_db_connection();
try {
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$announcement_id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$announcement) {
        $_SESSION['error'] = 'Announcement not found.';
        redirect('views/admin/announcements/index.php');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to fetch announcement details: ' . $e->getMessage();
    redirect('views/admin/announcements/index.php');
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Announcement</h5>
                </div>
                <div class="card-body">
                    <form action="process.php" method="POST">
                        <input type="hidden" name="action" value="update_announcement">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($announcement['id']); ?>">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Announcement</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 