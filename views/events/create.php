<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../includes/header.php';

// Security Check
if (!has_permission('create_official_event')) {
    echo "<h1>403 Forbidden</h1><p>You do not have permission to create an event.</p>";
    exit();
}

// Fetch users with staff roles to be assigned
$conn = get_db_connection();
try {
    $stmt = $conn->query("
        SELECT u.id, u.full_name, r.name as role_name
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.name IN ('Event Coordinator', 'Security Officer', 'Maintenance Staff', 'Finance Officer')
        ORDER BY u.full_name
    ");
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle error gracefully
    $staff_members = [];
    error_log("Failed to fetch staff members: " . $e->getMessage());
}

// Fetch venues
try {
    $stmt_venues = $conn->query("SELECT id, name, location FROM venues ORDER BY name");
    $venues = $stmt_venues->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $venues = [];
    error_log("Failed to fetch venues: " . $e->getMessage());
}

// Fetch departments
try {
    $stmt_departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt_departments->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $departments = [];
    error_log("Failed to fetch departments: " . $e->getMessage());
}

// Check for pre-filled data from an approved idea
$idea_title = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : '';
$idea_description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '';

// Get the current date and time in the format required for the 'min' attribute of datetime-local
$min_date = (new DateTime())->format('Y-m-d\TH:i');

?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create New Official Event</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo base_url('views/events/process.php'); ?>" method="POST" novalidate>
                        <input type="hidden" name="action" value="create_event">

                        <!-- Event Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo $idea_title; ?>" required>
                        </div>

                        <!-- Event Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $idea_description; ?></textarea>
                        </div>

                        <div class="row">
                            <!-- Start Date -->
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" required min="<?php echo $min_date; ?>">
                            </div>
                            <!-- End Date -->
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>

                        <!-- Event Access Level and Department -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_access_level" class="form-label">Event Access Level</label>
                                <select class="form-select" id="event_access_level" name="event_access_level" required onchange="toggleDepartmentField()">
                                    <option value="school_wide">School-wide Event</option>
                                    <option value="department_only">Department-only Event</option>
                                </select>
                                <small class="form-text text-muted">Select who can participate in this event</small>
                            </div>
                            <div class="col-md-6 mb-3" id="department_field" style="display: none;">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">Select a department...</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>">
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Only required for department-only events</small>
                            </div>
                        </div>

                        <!-- Venue -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="venue_id" class="form-label">Venue</label>
                                <select class="form-select" id="venue_id" name="venue_id" required>
                                    <option value="">Select a venue...</option>
                                    <?php foreach ($venues as $venue): ?>
                                        <option value="<?php echo $venue['id']; ?>"><?php echo htmlspecialchars($venue['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="venue-availability-status" class="mb-3"></div>

                        <div class="row">
                            <!-- Category -->
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select a category...</option>
                                    <option value="Academic">Academic</option>
                                    <option value="Arts & Culture">Arts & Culture</option>
                                    <option value="Sports">Sports</option>
                                    <option value="Community Service">Community Service</option>
                                    <option value="Workshop/Seminar">Workshop/Seminar</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <!-- Max Participants -->
                            <div class="col-md-6 mb-3">
                                <label for="max_participants" class="form-label">Max Participants (Optional)</label>
                                <input type="number" class="form-control" id="max_participants" name="max_participants" min="1">
                            </div>
                        </div>

                        <!-- Budget -->
                        <div class="mb-3">
                            <label for="budget" class="form-label">Proposed Budget (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="budget" name="budget" min="0" step="0.01" placeholder="e.g., 500.00">
                            </div>
                        </div>

                        <!-- Assigned Staff -->
                        <div class="mb-3">
                            <label for="assigned_staff" class="form-label">Assign Staff (Optional)</label>
                            <select class="form-select" id="assigned_staff" name="assigned_staff[]" multiple>
                                <option value="">Select staff members...</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>">
                                        <?php echo htmlspecialchars($staff['full_name']) . ' (' . htmlspecialchars($staff['role_name']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Hold down Ctrl (or Cmd on Mac) to select multiple staff members.</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Submit for Approval
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

<script>
function toggleDepartmentField() {
    const accessLevel = document.getElementById('event_access_level').value;
    const departmentField = document.getElementById('department_field');
    const departmentSelect = document.getElementById('department_id');
    
    if (accessLevel === 'department_only') {
        departmentField.style.display = 'block';
        departmentSelect.required = true;
    } else {
        departmentField.style.display = 'none';
        departmentSelect.required = false;
        departmentSelect.value = '';
    }
}

// Call on page load to set initial state
document.addEventListener('DOMContentLoaded', toggleDepartmentField);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const venueSelect = document.getElementById('venue_id');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const availabilityStatus = document.getElementById('venue-availability-status');
    const submitButton = document.querySelector('button[type="submit"]');

    function checkAvailability() {
        const venueId = venueSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Only check if all three fields have values
        if (!venueId || !startDate || !endDate) {
            availabilityStatus.innerHTML = ''; // Clear status if fields are incomplete
            submitButton.disabled = false; // Re-enable button if fields are cleared
            return;
        }

        // Basic validation: start date cannot be in the past
        if (new Date(startDate) < new Date('<?php echo $min_date; ?>')) {
            availabilityStatus.innerHTML = '<div class="alert alert-danger p-2">The start date cannot be in the past. Please select a future date.</div>';
            submitButton.disabled = true;
            return;
        }

        // Basic validation: end date must be after start date
        if (new Date(endDate) <= new Date(startDate)) {
            availabilityStatus.innerHTML = '<div class="alert alert-danger p-2">End date must be after the start date.</div>';
            submitButton.disabled = true;
            return;
        }

        availabilityStatus.innerHTML = '<div class="alert alert-info p-2">Checking availability...</div>';
        submitButton.disabled = true; // Disable button while checking

        const formData = new FormData();
        formData.append('venue_id', venueId);
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);

        fetch('<?php echo base_url('api/check_venue_availability.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                availabilityStatus.innerHTML = `<div class="alert alert-success p-2">${data.message}</div>`;
                submitButton.disabled = false;
            } else {
                availabilityStatus.innerHTML = `<div class="alert alert-danger p-2">${data.message}</div>`;
                submitButton.disabled = true;
            }
        })
        .catch(error => {
            availabilityStatus.innerHTML = '<div class="alert alert-danger p-2">An error occurred while checking availability.</div>';
            submitButton.disabled = true;
            console.error('Error:', error);
        });
    }

    venueSelect.addEventListener('change', checkAvailability);
    startDateInput.addEventListener('change', checkAvailability);
    endDateInput.addEventListener('change', checkAvailability);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?> 