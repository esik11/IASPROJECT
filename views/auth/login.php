<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username and password are required.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // Try to find user in employees table first
        $stmt = $conn->prepare("
            SELECT e.*, GROUP_CONCAT(r.role_name) as roles 
            FROM employees e 
            LEFT JOIN employee_roles er ON e.employee_id = er.employee_id 
            LEFT JOIN roles r ON er.role_id = r.role_id 
            WHERE e.username = ? AND e.status = 'active'
            GROUP BY e.employee_id
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            // If not found in employees, try customers table
            $stmt = $conn->prepare("
                SELECT c.*, GROUP_CONCAT(r.role_name) as roles 
                FROM customers c 
                LEFT JOIN customer_roles cr ON c.customer_id = cr.customer_id 
                LEFT JOIN roles r ON cr.role_id = r.role_id 
                WHERE c.username = ? AND c.status = 'active'
                GROUP BY c.customer_id
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['employee_id'] ?? $user['customer_id'];
            $_SESSION['employee_id'] = $user['employee_id'] ?? null;
            $_SESSION['customer_id'] = $user['customer_id'] ?? null;
            $_SESSION['username'] = $user['username'];
            $_SESSION['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
            
            header('Location: /IASPROJECT/views/admin/users/index.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Event Management</title>
    <link rel="stylesheet" href="/IASPROJECT/assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1>Login</h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>