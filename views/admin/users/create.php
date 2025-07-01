<?php
$page_title = 'Create User';
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';

if (!has_permission('manage_users')) {
    $_SESSION['error'] = 'You do not have permission to access this page.';
    redirect('views/dashboard.php');
}

$conn = get_db_connection();
$type = $_GET['type'] ?? 'employee'; // Default to employee
if (!in_array($type, ['employee', 'student'])) {
    $type = 'employee'; // Security fallback
}

// Fetch available roles based on type
$role_query_part = ($type === 'student') 
    ? "WHERE name IN ('Student', 'Guest User')" 
    : "WHERE name NOT IN ('Student', 'Guest User')";
$stmt_roles = $conn->query("SELECT id, name FROM roles {$role_query_part} ORDER BY name ASC");
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing departments for the dropdown
$stmt_depts = $conn->query("SELECT name FROM departments ORDER BY name ASC");
$departments = $stmt_depts->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add New <?= htmlspecialchars(ucfirst($type)) ?></h3>
                </div>
                <form action="process.php" method="POST">
                    <input type="hidden" name="action" value="create_user">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    <div class="card-body">
                        <div class="row">
                            <!-- Common Fields -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <!-- Type-Specific and Other Fields -->
                            <div class="col-md-6">
                                <?php if ($type === 'student'): ?>
                                    <div class="form-group">
                                        <label for="student_number">Student Number</label>
                                        <input type="text" class="form-control" id="student_number" name="student_number" required>
                                    </div>
                                <?php else: // Employee fields ?>
                                    <div class="form-group">
                                        <label for="position">Position</label>
                                        <input type="text" class="form-control" id="position" name="position" required>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <select class="form-control" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= htmlspecialchars($department) ?>"><?= htmlspecialchars($department) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone (Optional)</label>
                                    <input type="text" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>Assign Roles</label>
                            <div>
                                <?php foreach ($roles as $role): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="roles[]" id="role_<?= $role['id'] ?>" value="<?= $role['id'] ?>">
                                        <label class="form-check-label" for="role_<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create User</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>