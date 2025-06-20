<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['employee_id']) || isset($_SESSION['customer_id']);
$userFullName = '';

// Get user's full name based on their type
if (isset($_SESSION['employee_id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
    $stmt->execute([$_SESSION['employee_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userFullName = $user['first_name'] . ' ' . $user['last_name'];
    }
} elseif (isset($_SESSION['customer_id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT first_name, last_name FROM customers WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userFullName = $user['first_name'] . ' ' . $user['last_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Event Management</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header {
            background-color: #333;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-title {
            margin: 0;
            font-size: 1.5rem;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-info {
            color: white;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1 class="header-title">Campus Event Management</h1>
        <?php if ($isLoggedIn): ?>
        <div class="user-menu">
            <span class="user-info">Welcome, <?php echo htmlspecialchars($userFullName); ?></span>
            <a href="/IASPROJECT/views/auth/logout.php" class="logout-btn">Logout</a>
        </div>
        <?php endif; ?>
    </header>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .user-menu {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .btn-logout {
        color: var(--danger-color) !important;
    }

    .btn-logout:hover {
        background-color: var(--danger-color) !important;
        color: white !important;
    }
    </style>
</body>
</html>