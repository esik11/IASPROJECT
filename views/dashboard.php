<?php
require_once '../config/database.php';
requireLogin();

// Get basic statistics
$database = new Database();
$conn = $database->getConnection();

$stats = array();

// Total users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total events
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM events");
$stmt->execute();
$stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total reservations
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations");
$stmt->execute();
$stats['total_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Active users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stmt->execute();
$stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Campus Event Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../views/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Dashboard</h1>
            </div>
            
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_events']; ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_reservations']; ?></div>
                    <div class="stat-label">Total Reservations</div>
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <h3>Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!</h3>
                <p>Your roles: <?php echo implode(', ', $_SESSION['roles']); ?></p>
                
                <?php if (isAdmin()): ?>
                    <div style="margin-top: 2rem;">
                        <h4>Admin Quick Actions:</h4>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                            <a href="../controllers/UserController.php?action=index" class="btn btn-primary">Manage Users</a>
                            <a href="../controllers/UserController.php?action=create" class="btn btn-success">Add New User</a>
                            <a href="#" class="btn btn-warning">Manage Events</a>
                            <a href="#" class="btn btn-warning">View Reports</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>