<header class="header">
    <div class="header-content">
        <div class="logo">
            Campus Event Management
        </div>
        
        <nav class="nav">
            <ul>
                <li><a href="../views/dashboard.php">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="../controllers/UserController.php?action=index">Users</a></li>
                    <li><a href="#">Events</a></li>
                    <li><a href="#">Reports</a></li>
                <?php endif; ?>
                <li><a href="#">Calendar</a></li>
            </ul>
        </nav>
        
        <div class="user-menu">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
            <a href="../controllers/AuthController.php?action=logout" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</header>