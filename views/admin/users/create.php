<?php
require_once __DIR__ . '/../../../config/database.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['roles']) || !in_array('Admin', $_SESSION['roles'])) {
    header('Location: /IASPROJECT/views/auth/login.php');
    exit();
}

// Get user type from URL
$type = $_GET['type'] ?? 'employee';
if (!in_array($type, ['employee', 'customer'])) {
    header('Location: index.php');
    exit();
}

// Fetch roles based on user type
$db = new Database();
$conn = $db->getConnection();

if ($type === 'customer') {
    // Only fetch Student and Guest roles for customers
    $stmt = $conn->prepare("SELECT * FROM roles WHERE role_name IN ('Student', 'Guest')");
} else {
    // Fetch all roles except Student and Guest for employees
    $stmt = $conn->prepare("SELECT * FROM roles WHERE role_name NOT IN ('Student', 'Guest')");
}
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New <?php echo ucfirst($type); ?> - Campus Event Management</title>
    <link rel="stylesheet" href="/IASPROJECT/assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Add New <?php echo ucfirst($type); ?></h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="process.php" method="POST" class="form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="type" value="<?php echo $type; ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control">
            </div>

            <?php if ($type === 'employee'): ?>
            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" class="form-control">
                    <option value="">Select Department</option>
                    <option value="CET">CET - College Of Engineering and Technology</option>
                    <option value="CAS">CAS - College of Arts and Science</option>
                    <option value="CCJ">CCJ - College of Criminal Justice</option>
                    <option value="CBE">CBE - College of Business Education</option>
                    <option value="CTE">CTE - College of Teachers Education</option>
                </select>
            </div>

            <div class="form-group">
                <label for="position">Position</label>
                <input type="text" id="position" name="position" class="form-control">
            </div>
            <?php endif; ?>

            <?php if ($type === 'customer'): ?>
            <div class="form-group student-department" style="display: none;">
                <label for="student_department">Department</label>
                <select id="student_department" name="student_department" class="form-control">
                    <option value="">Select Department</option>
                    <option value="CET">CET - College Of Engineering and Technology</option>
                    <option value="CAS">CAS - College of Arts and Science</option>
                    <option value="CCJ">CCJ - College of Criminal Justice</option>
                    <option value="CBE">CBE - College of Business Education</option>
                    <option value="CTE">CTE - College of Teachers Education</option>
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
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create <?php echo ucfirst($type); ?></button>
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
    </script>
</body>
</html>