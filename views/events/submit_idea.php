<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../includes/header.php';

// Security Check: Ensure the user has permission to submit an idea
if (!has_permission('submit_event_idea')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>";
    exit();
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Submit Your Event Idea</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Have a great idea for an event? Share it with us! Your idea will be reviewed by our event coordinators, and you'll be notified if it's approved.</p>
                    <hr>
                    <form action="<?php echo base_url('views/events/process.php'); ?>" method="POST">
                        <input type="hidden" name="action" value="submit_idea">

                        <!-- Idea Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Idea Title</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Annual Tech Fair" required>
                            <small class="form-text text-muted">Give your event a catchy and descriptive name.</small>
                        </div>

                        <!-- Idea Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Idea Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Describe your event idea in detail. What is it about? Who is the target audience? What are the goals?" required></textarea>
                            <small class="form-text text-muted">The more detail, the better!</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Idea
                            </button>
                            <a href="<?php echo base_url('views/dashboard.php'); ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 