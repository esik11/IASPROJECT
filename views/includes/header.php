<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';

$isLoggedIn = is_logged_in();
$userFullName = $_SESSION['full_name'] ?? '';
$userRoles = $isLoggedIn ? get_user_roles(get_current_user_id()) : [];
$userRoleDisplay = !empty($userRoles) ? implode(', ', array_map('ucfirst', $userRoles)) : 'Guest';
$absoluteBaseUrl = BASE_URL;

$sidebar_nav_map = [
    [
        'label' => 'Dashboard',
        'permission' => 'view_dashboard',
        'icon' => 'fas fa-home',
        'url' => $absoluteBaseUrl . 'views/dashboard.php'
    ],
    [
        'label' => 'Event Calendar',
        'permission' => 'view_event_calendar',
        'icon' => 'fas fa-calendar-alt',
        'url' => $absoluteBaseUrl . 'views/events/index.php'
    ],
    [
        'label' => 'Submit Idea',
        'permission' => 'submit_event_idea',
        'icon' => 'fas fa-lightbulb',
        'url' => $absoluteBaseUrl . 'views/events/submit_idea.php'
    ],
    [
        'label' => 'Reserve a Venue',
        'permission' => 'reserve_venue',
        'icon' => 'fas fa-calendar-check',
        'url' => $absoluteBaseUrl . 'views/reservations/index.php'
    ],
    [
        'label' => 'Reservation History',
        'permission' => 'view_reservation_history',
        'icon' => 'fas fa-history',
        'url' => (has_permission('view_all_reservation_history', $userRoles) ? $absoluteBaseUrl . 'views/admin/reservations/history.php' : $absoluteBaseUrl . 'views/reservations/my_reservations.php')
    ],
    [
        'label' => 'Create Event',
        'permission' => 'create_official_event',
        'icon' => 'fas fa-plus-circle',
        'url' => $absoluteBaseUrl . 'views/events/create.php'
    ],
    [
        'label' => 'Approvals',
        'permission' => 'approve_reject_events',
        'icon' => 'fas fa-check-square',
        'url' => $absoluteBaseUrl . 'views/approvals/index.php'
    ],
    [
        'label' => 'Event Ideas',
        'permission' => 'manage_event_ideas',
        'icon' => 'fas fa-clipboard-list',
        'url' => $absoluteBaseUrl . 'views/admin/ideas/index.php'
    ],
    [
        'label' => 'Announcements',
        'permission' => 'post_announcements',
        'icon' => 'fas fa-bullhorn',
        'url' => $absoluteBaseUrl . 'views/admin/announcements/index.php'
    ],
    [
        'label' => 'Venues',
        'permission' => 'manage_venues',
        'icon' => 'fas fa-map-marked-alt',
        'url' => $absoluteBaseUrl . 'views/admin/venues/index.php'
    ],
    [
        'label' => 'Reports',
        'permission' => 'generate_event_reports',
        'icon' => 'fas fa-chart-bar',
        'url' => $absoluteBaseUrl . 'views/admin/reports/index.php'
    ],
    [
        'label' => 'User Management',
        'permission' => 'manage_users',
        'icon' => 'fas fa-users-cog',
        'url' => $absoluteBaseUrl . 'views/admin/users/index.php'
    ],
    [
        'label' => 'Activity Logs',
        'permission' => 'view_activity_logs',
        'icon' => 'fas fa-history',
        'url' => $absoluteBaseUrl . 'views/admin/activity_logs/index.php'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Event Management' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $absoluteBaseUrl; ?>assets/css/style.css">
</head>
<body 
    data-base-url="<?php echo $absoluteBaseUrl; ?>" 
    data-user-id="<?php echo $isLoggedIn ? get_current_user_id() : ''; ?>"
    data-pusher-key="<?php echo PUSHER_APP_KEY; ?>"
    data-pusher-cluster="<?php echo PUSHER_APP_CLUSTER; ?>"
>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>Campus Events</h3>
        </div>
        <?php if ($isLoggedIn): ?>
            <div class="sidebar-user">
                <div class="user-avatar">
                    <i class="fas fa-user-circle fa-2x"></i>
                </div>
                <div class="user-info">
                    <p class="user-name mb-0"><?php echo htmlspecialchars($userFullName); ?></p>
                    <span class="user-role text-muted"><?php echo htmlspecialchars($userRoleDisplay); ?></span>
                </div>
            </div>
            <ul class="list-unstyled components">
                <?php foreach ($sidebar_nav_map as $item): ?>
                    <?php 
                        // Dashboard is a special case, visible to all logged-in users.
                            $has_perm = ($item['permission'] === 'view_dashboard' && $isLoggedIn) || has_permission($item['permission'], $userRoles);
                            if ($has_perm): 
                        ?>
                            <li>
                        <a href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                <?php endforeach; ?>
                </ul>
        <?php endif; ?>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span>Toggle Sidebar</span>
                </button>
                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="badge rounded-pill bg-danger" id="notification-count"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" id="notification-list" aria-labelledby="notificationDropdown">
                                <!-- Notifications will be populated here by JS -->
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($userFullName); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo $absoluteBaseUrl; ?>views/profile/index.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $absoluteBaseUrl; ?>views/auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                    </div>
            </div>
        </nav>
        
            <div class="container-fluid">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            <!-- Main page content starts here -->

</body>
</html>