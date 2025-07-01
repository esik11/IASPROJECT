<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../includes/header.php';

// Security Check: Ensure the user has permission to view event details
if (!has_permission('view_event_details')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>";
    exit();
}

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    $_SESSION['error'] = 'No event ID specified.';
    redirect('views/events/index.php');
}

$conn = get_db_connection();
try {
    // Fetch event details along with creator and venue name
    $stmt = $conn->prepare("
        SELECT e.*, u.full_name AS creator_name, v.name AS venue_name, d.name AS department_name
        FROM events e
        JOIN users u ON e.created_by = u.id
        LEFT JOIN venues v ON e.venue_id = v.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $_SESSION['error'] = 'Event not found.';
        redirect('views/events/index.php');
    }

    // Fetch event attachments
    $attach_stmt = $conn->prepare("
        SELECT a.*, u.full_name as uploader_name
        FROM event_attachments a
        JOIN users u ON a.uploaded_by = u.id
        WHERE a.event_id = ?
        ORDER BY a.uploaded_at DESC
    ");
    $attach_stmt->execute([$event_id]);
    $attachments = $attach_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch assigned staff
    $assigned_staff_stmt = $conn->prepare("
        SELECT u.id, u.full_name, r.name as role_name
        FROM users u
        JOIN event_staff es ON u.id = es.user_id
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE es.event_id = ?
        ORDER BY u.full_name
    ");
    $assigned_staff_stmt->execute([$event_id]);
    $assigned_staff = $assigned_staff_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch available staff (users with staff roles not already assigned to this event)
    $available_staff_stmt = $conn->prepare("
        SELECT u.id, u.full_name, r.name as role_name
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.name IN ('Event Coordinator', 'Security Officer', 'Maintenance Staff', 'Finance Officer')
        AND u.id NOT IN (SELECT user_id FROM event_staff WHERE event_id = ?)
        ORDER BY u.full_name
    ");
    $available_staff_stmt->execute([$event_id]);
    $available_staff = $available_staff_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check current user's registration status for this event
    $user_id = get_current_user_id();
    $is_registered = false;
    if ($user_id) {
        $reg_stmt = $conn->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND user_id = ? AND status = 'registered'");
        $reg_stmt->execute([$event_id, $user_id]);
        if ($reg_stmt->fetchColumn() > 0) {
            $is_registered = true;
        }
    }

} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to fetch event details: ' . $e->getMessage();
    redirect('views/events/index.php');
}
?>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h4 class="card-title mb-0"><?php echo htmlspecialchars($event['title']); ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>
                <div class="col-md-4">
                    <ul class="list-group">
                        <li class="list-group-item"><strong>Status:</strong> <span class="badge <?php echo 'bg-' . ($event['status'] === 'cancelled' ? 'danger' : 'primary'); ?>"><?php echo ucfirst(htmlspecialchars($event['status'])); ?></span></li>
                        <li class="list-group-item"><strong>Category:</strong> <?php echo htmlspecialchars($event['category']); ?></li>
                        <li class="list-group-item">
                            <strong>Access Level:</strong>
                            <?php if ($event['event_access_level'] === 'department_only'): ?>
                                <span class="badge bg-info">Department Only</span>
                                <small class="d-block text-muted">Limited to <?php echo htmlspecialchars($event['department_name']); ?></small>
                            <?php else: ?>
                                <span class="badge bg-success">School-wide</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item"><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue_name']); ?></li>
                        <li class="list-group-item"><strong>Starts:</strong> <?php echo date('M j, Y, g:i A', strtotime($event['start_date'])); ?></li>
                        <li class="list-group-item"><strong>Ends:</strong> <?php echo date('M j, Y, g:i A', strtotime($event['end_date'])); ?></li>
                        <li class="list-group-item"><strong>Created by:</strong> <?php echo htmlspecialchars($event['creator_name']); ?></li>
                        <li class="list-group-item"><strong>Max Participants:</strong> <?php echo htmlspecialchars($event['max_participants'] ?? 'N/A'); ?></li>
                        <li class="list-group-item"><strong>Budget:</strong> $<?php echo htmlspecialchars(number_format($event['budget'] ?? 0, 2)); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Actions Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Event Actions</h5>
        </div>
        <div class="card-body d-flex flex-wrap gap-2">
            <!-- Participant Actions -->
            <?php if ($event['status'] === 'approved'): ?>
                <?php if ($is_registered): ?>
                    <?php if (has_permission('cancel_reservation')): ?>
                        <form action="<?php echo base_url('views/events/process.php'); ?>" method="POST">
                            <input type="hidden" name="action" value="cancel_registration">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to cancel your registration for this event?');">
                                <i class="fas fa-user-times me-1"></i> Cancel My Registration
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Placeholder for a future "Register" button -->
                     <a href="#" class="btn btn-success disabled"><i class="fas fa-user-plus me-1"></i> Register for Event (Not Implemented)</a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Admin Actions -->
            <?php if (has_permission('delete_event') && $event['status'] !== 'cancelled'): ?>
                 <form action="<?php echo base_url('views/events/process.php'); ?>" method="POST">
                    <input type="hidden" name="action" value="cancel_event">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('WARNING: This will cancel the entire event for all participants. Are you sure?');">
                        <i class="fas fa-ban me-1"></i> Cancel This Event
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Staff Management Section -->
    <?php if (has_permission('assign_staff')): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-users-cog me-2"></i>Manage Assigned Staff</h5>
        </div>
        <div class="card-body">
            <!-- List of Assigned Staff -->
            <h6>Currently Assigned</h6>
            <?php if (!empty($assigned_staff)): ?>
                <ul class="list-group mb-4">
                    <?php foreach ($assigned_staff as $staff): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?php echo htmlspecialchars($staff['full_name']); ?>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($staff['role_name']); ?></small>
                            </div>
                            <form action="<?php echo base_url('views/events/process.php'); ?>" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="remove_staff">
                                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this staff member?');">
                                    <i class="fas fa-user-minus"></i> Remove
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No staff members are currently assigned to this event.</p>
            <?php endif; ?>

            <hr>

            <!-- Add New Staff Form -->
            <h6>Add New Staff</h6>
            <form action="<?php echo base_url('views/events/process.php'); ?>" method="POST">
                <input type="hidden" name="action" value="add_staff">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                <div class="input-group">
                    <select class="form-select" name="user_id" required>
                        <option value="">Select a staff member to add...</option>
                        <?php foreach ($available_staff as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>">
                                <?php echo htmlspecialchars($staff['full_name']); ?> (<?php echo htmlspecialchars($staff['role_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="submit" <?php echo empty($available_staff) ? 'disabled' : ''; ?>>
                        <i class="fas fa-plus"></i> Add Staff
                    </button>
                </div>
                <?php if (empty($available_staff)): ?>
                    <small class="form-text text-muted">All available staff members are already assigned to this event.</small>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Document Management Section -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Event Documents</h5>
        </div>
        <div class="card-body">
            <!-- Upload Form -->
            <?php if (has_permission('upload_documents')): // Add more specific checks if needed ?>
                <div class="mb-4">
                    <h6>Upload New Document</h6>
                    <form action="<?php echo base_url('views/events/process_attachment.php'); ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_attachment">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <div class="input-group">
                            <input type="file" class="form-control" name="document" id="document" required>
                            <button class="btn btn-primary" type="submit">Upload</button>
                        </div>
                        <small class="form-text text-muted">Max file size: 5MB. Allowed types: PDF, DOC, DOCX, PNG, JPG.</small>
                    </form>
                </div>
                <hr>
            <?php endif; ?>

            <!-- Attachments List -->
            <h6>Uploaded Documents</h6>
            <?php if (!empty($attachments)): ?>
                <ul class="list-group">
                    <?php foreach ($attachments as $attachment): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-alt me-2"></i>
                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                                <small class="text-muted d-block">
                                    Uploaded by <?php echo htmlspecialchars($attachment['uploader_name']); ?>
                                    on <?php echo date('M j, Y', strtotime($attachment['uploaded_at'])); ?>
                                </small>
                            </div>
                            <?php if (has_permission('download_documents')): ?>
                                <a href="<?php echo base_url('api/download.php?id=' . $attachment['id']); ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No documents have been uploaded for this event yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (has_permission('upload_documents')) : ?>
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-paperclip me-2"></i>Upload Attachments</h5>
            </div>
            <div class="card-body">
                <form action="process_attachment.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    <div class="mb-3">
                        <label for="document" class="form-label">Select file</label>
                        <input type="file" class="form-control" name="document" id="document" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload File</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?> 