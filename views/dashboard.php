<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['customer_id'])) {
    header('Location: /IASPROJECT/views/auth/login.php');
    exit();
}

// Include header
require_once __DIR__ . '/includes/header.php';

// Get the current user's data
$user = new User();
$userData = $user->find($_SESSION['user_id']);
?>

<div class="dashboard-container">
    <h2>Welcome to Campus Event Management System</h2>
    
    <?php if (isset($_SESSION['roles']) && in_array('Admin', $_SESSION['roles'])): ?>
    <div class="admin-section">
        <h3>Administrative Tools</h3>
        <div class="card-grid">
            <div class="card">
                <h4>User Management</h4>
                <p>Manage employees and customers</p>
                <a href="/IASPROJECT/views/admin/users/index.php" class="btn btn-primary">Manage Users</a>
            </div>
            <!-- Add more admin cards here -->
        </div>
    </div>
    <?php endif; ?>

    <div class="user-section">
        <h3>Quick Actions</h3>
        <div class="card-grid">
            <div class="card">
                <h4>My Profile</h4>
                <p>View and update your profile information</p>
                <a href="#" class="btn btn-primary">View Profile</a>
            </div>
            <!-- Add more user cards here -->
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 2rem;
}

.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 1rem;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card h4 {
    margin: 0 0 1rem 0;
    color: #333;
}

.card p {
    margin: 0 0 1.5rem 0;
    color: #666;
}

.admin-section, .user-section {
    margin-bottom: 3rem;
}

h2 {
    color: #333;
    margin-bottom: 2rem;
}

h3 {
    color: #444;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #eee;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.btn:hover {
    background-color: #0056b3;
}

.btn-primary {
    background-color: #007bff;
}

.btn-primary:hover {
    background-color: #0056b3;
}
</style>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>