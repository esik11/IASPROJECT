<?php
$page_title = 'Edit User';
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';

// Ensure user is admin
if (!has_permission('manage_users')) {
    $_SESSION['error'] = 'You do not have permission to access this page.';
    redirect('views/dashboard.php');
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$user_id) {
    $_SESSION['error'] = 'Invalid user ID.';
    redirect('views/admin/users/index.php');
}

$conn = get_db_connection();

// Fetch user data, including their roles
$stmt = $conn->prepare("SELECT u.*, GROUP_CONCAT(r.name) as roles FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id WHERE u.id = :id GROUP BY u.id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = 'User not found.';
    redirect('views/admin/users/index.php');
}

// Determine user type based on roles for UI display
$user_roles_array = explode(',', $user['roles'] ?? '');
$is_student_type = in_array('Student', $user_roles_array) || in_array('Guest User', $user_roles_array);
$type = $is_student_type ? 'student' : 'employee';

// Fetch all available roles for the dropdown
$role_query_part = ($type === 'student') 
    ? "WHERE name IN ('Student', 'Guest User')" 
    : "WHERE name NOT IN ('Student', 'Guest User')";
$stmt_all_roles = $conn->query("SELECT id, name FROM roles {$role_query_part} ORDER BY name ASC");
$all_roles = $stmt_all_roles->fetchAll(PDO::FETCH_ASSOC);

// Fetch IDs of roles currently assigned to the user
$stmt_user_roles = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = :user_id");
$stmt_user_roles->execute(['user_id' => $user_id]);
$user_role_ids = $stmt_user_roles->fetchAll(PDO::FETCH_COLUMN);

// Fetch existing departments
$stmt_depts = $conn->query("SELECT name FROM departments ORDER BY name ASC");
$departments = $stmt_depts->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit <?= htmlspecialchars($user['full_name']) ?></h3>
                </div>
                <form action="process.php" method="POST">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    <div class="card-body">
                        <div class="row">
                            <!-- Common Fields -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                                </div>
                            </div>
                            <!-- Type-Specific and Other Fields -->
                            <div class="col-md-6">
                                <?php if ($type === 'student'): ?>
                                    <div class="form-group">
                                        <label for="student_number">Student Number</label>
                                        <input type="text" class="form-control" id="student_number" name="student_number" value="<?= htmlspecialchars($user['student_number'] ?? '') ?>" required>
                                    </div>
                                <?php else: // Employee fields ?>
                                    <div class="form-group">
                                        <label for="position">Position</label>
                                        <input type="text" class="form-control" id="position" name="position" value="<?= htmlspecialchars($user['position'] ?? '') ?>" required>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <select class="form-control" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= htmlspecialchars($department) ?>" <?= ($user['department'] == $department) ? 'selected' : '' ?>><?= htmlspecialchars($department) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone (Optional)</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>Assign Roles</label>
                            <div>
                                <?php foreach ($all_roles as $role): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="roles[]" id="role_<?= $role['id'] ?>" value="<?= $role['id'] ?>" <?= in_array($role['id'], $user_role_ids) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="role_<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update User</button>
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