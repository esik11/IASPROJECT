<?php
require_once __DIR__ . '/../../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['roles']) || !in_array('Admin', $_SESSION['roles'])) {
    header('Location: /IASPROJECT/views/auth/login.php');
    exit();
}

// Get user type and ID from URL
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (!in_array($type, ['employee', 'customer']) || !$id) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Fetch user data
if ($type === 'employee') {
    $stmt = $conn->prepare("
        SELECT e.*, GROUP_CONCAT(r.role_id) as role_ids, GROUP_CONCAT(r.role_name) as role_names
        FROM employees e 
        LEFT JOIN employee_roles er ON e.employee_id = er.employee_id 
        LEFT JOIN roles r ON er.role_id = r.role_id 
        WHERE e.employee_id = ?
        GROUP BY e.employee_id
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['error'] = "Employee not found.";
        header('Location: index.php');
        exit();
    }
} else {
    $stmt = $conn->prepare("
        SELECT c.*, GROUP_CONCAT(r.role_id) as role_ids, GROUP_CONCAT(r.role_name) as role_names
        FROM customers c 
        LEFT JOIN customer_roles cr ON c.customer_id = cr.customer_id 
        LEFT JOIN roles r ON cr.role_id = r.role_id 
        WHERE c.customer_id = ?
        GROUP BY c.customer_id
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['error'] = "Customer not found.";
        header('Location: index.php');
        exit();
    }
}

// Fetch available roles based on user type
if ($type === 'customer') {
    $stmt = $conn->prepare("SELECT * FROM roles WHERE role_name IN ('Student', 'Guest')");
} else {
    $stmt = $conn->prepare("SELECT * FROM roles WHERE role_name NOT IN ('Student', 'Guest')");
}
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current role IDs
$currentRoleIds = $user['role_ids'] ? explode(',', $user['role_ids']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo ucfirst($type); ?> - Campus Event Management</title>
    <link rel="stylesheet" href="/IASPROJECT/assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit <?php echo ucfirst($type); ?></h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="process.php" method="POST" class="form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="type" value="<?php echo $type; ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">New Password (leave blank to keep current)</label>
                <input type="password" id="password" name="password" class="form-control">
            </div>

            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" 
                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" 
                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>

            <?php if ($type === 'employee'): ?>
            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" class="form-control">
                    <option value="">Select Department</option>
                    <option value="CET" <?php echo ($user['department'] ?? '') === 'CET' ? 'selected' : ''; ?>>CET - College Of Engineering and Technology</option>
                    <option value="CAS" <?php echo ($user['department'] ?? '') === 'CAS' ? 'selected' : ''; ?>>CAS - College of Arts and Science</option>
                    <option value="CCJ" <?php echo ($user['department'] ?? '') === 'CCJ' ? 'selected' : ''; ?>>CCJ - College of Criminal Justice</option>
                    <option value="CBE" <?php echo ($user['department'] ?? '') === 'CBE' ? 'selected' : ''; ?>>CBE - College of Business Education</option>
                    <option value="CTE" <?php echo ($user['department'] ?? '') === 'CTE' ? 'selected' : ''; ?>>CTE - College of Teachers Education</option>
                </select>
            </div>

            <div class="form-group">
                <label for="position">Position</label>
                <input type="text" id="position" name="position" class="form-control" 
                       value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>">
            </div>
            <?php endif; ?>

            <?php if ($type === 'customer'): ?>
            <div class="form-group student-department" style="display: none;">
                <label for="student_department">Department</label>
                <select id="student_department" name="student_department" class="form-control">
                    <option value="">Select Department</option>
                    <option value="CET" <?php echo ($user['department'] ?? '') === 'CET' ? 'selected' : ''; ?>>CET - College Of Engineering and Technology</option>
                    <option value="CAS" <?php echo ($user['department'] ?? '') === 'CAS' ? 'selected' : ''; ?>>CAS - College of Arts and Science</option>
                    <option value="CCJ" <?php echo ($user['department'] ?? '') === 'CCJ' ? 'selected' : ''; ?>>CCJ - College of Criminal Justice</option>
                    <option value="CBE" <?php echo ($user['department'] ?? '') === 'CBE' ? 'selected' : ''; ?>>CBE - College of Business Education</option>
                    <option value="CTE" <?php echo ($user['department'] ?? '') === 'CTE' ? 'selected' : ''; ?>>CTE - College of Teachers Education</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Role<?php echo ($type === 'customer' ? ' (Student or Guest only)' : ''); ?></label>
                <div class="checkbox-group">
                    <?php foreach ($roles as $role): ?>
                    <div class="checkbox-item">
                        <input type="radio" id="role_<?php echo $role['role_id']; ?>" 
                               name="roles[]" value="<?php echo $role['role_id']; ?>" required
                               <?php echo in_array($role['role_id'], $currentRoleIds) ? 'checked' : ''; ?>
                               onchange="toggleStudentDepartment(this, '<?php echo $role['role_name']; ?>')">
                        <label for="role_<?php echo $role['role_id']; ?>">
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update <?php echo ucfirst($type); ?></button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function toggleStudentDepartment(radio, roleName) {
        const studentDeptDiv = document.querySelector('.student-department');
        if (studentDeptDiv) {
            if (roleName === 'Student') {
                studentDeptDiv.style.display = 'block';
                document.getElementById('student_department').required = true;
            } else {
                studentDeptDiv.style.display = 'none';
                document.getElementById('student_department').required = false;
                document.getElementById('student_department').value = '';
            }
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const checkedRole = document.querySelector('input[name="roles[]"]:checked');
        if (checkedRole) {
            const roleLabel = checkedRole.nextElementSibling.textContent.trim();
            toggleStudentDepartment(checkedRole, roleLabel);
        }
    });
    </script>
</body>
</html> 