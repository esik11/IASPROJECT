<?php
$debug_timings = ['header_start' => microtime(true)];

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the absolute URL for assets
$absoluteBaseUrl = BASE_URL;

$isLoggedIn = is_logged_in();
$userFullName = $_SESSION['full_name'] ?? '';

$debug_timings['before_get_roles'] = microtime(true);
$userRoles = $isLoggedIn ? get_user_roles(get_current_user_id()) : [];
$debug_timings['after_get_roles'] = microtime(true);

$userRoleDisplay = !empty($userRoles) ? implode(', ', array_map('ucfirst', $userRoles)) : 'Guest';

$sidebar_nav_map = [
    [
        'label' => 'Dashboard',
        'permission' => 'view_dashboard',
        'icon' => 'fas fa-home',
        'url' => $absoluteBaseUrl . 'views/dashboard.php'
    ],
    [
        'label' => 'View Event Calendar',
        'permission' => 'view_event_calendar',
        'icon' => 'fas fa-calendar-alt',
        'url' => $absoluteBaseUrl . 'views/events/index.php'
    ],
    [
        'label' => 'Submit Event Idea',
        'permission' => 'submit_event_idea',
        'icon' => 'fas fa-lightbulb',
        'url' => $absoluteBaseUrl . 'views/events/submit_idea.php'
    ],
    [
        'label' => 'Create Official Event',
        'permission' => 'create_official_event',
        'icon' => 'fas fa-plus-circle',
        'url' => $absoluteBaseUrl . 'views/events/create.php'
    ],
    [
        'label' => 'Approve/Reject Events',
        'permission' => 'approve_reject_events',
        'icon' => 'fas fa-check-square',
        'url' => $absoluteBaseUrl . 'views/approvals/index.php'
    ],
    [
        'label' => 'Manage Event Ideas',
        'permission' => 'manage_event_ideas',
        'icon' => 'fas fa-clipboard-list',
        'url' => $absoluteBaseUrl . 'views/admin/ideas/index.php'
    ],
    [
        'label' => 'Reserve Venue',
        'permission' => 'reserve_venue',
        'icon' => 'fas fa-map-marker-alt',
        'url' => '#'
    ],
    [
        'label' => 'Assign Staff',
        'permission' => 'assign_staff',
        'icon' => 'fas fa-user-plus',
        'url' => $absoluteBaseUrl . 'views/events/index.php'
    ],
    [
        'label' => 'Manage Announcements',
        'permission' => 'post_announcements',
        'icon' => 'fas fa-bullhorn',
        'url' => $absoluteBaseUrl . 'views/admin/announcements/index.php'
    ],
    [
        'label' => 'View Announcements',
        'permission' => 'view_announcements',
        'icon' => 'fas fa-newspaper',
        'url' => $absoluteBaseUrl . 'views/announcements/index.php'
    ],
    [
        'label' => 'Manage Venues',
        'permission' => 'manage_venues',
        'icon' => 'fas fa-map-marked-alt',
        'url' => $absoluteBaseUrl . 'views/admin/venues/index.php'
    ],
    [
        'label' => 'Generate Reports',
        'permission' => 'generate_event_reports',
        'icon' => 'fas fa-chart-bar',
        'url' => $absoluteBaseUrl . 'views/admin/reports/index.php'
    ],
    [
        'label' => 'Manage Users',
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
</head>
<body 
    data-base-url="<?php echo base_url(); ?>" 
    data-user-id="<?php echo is_logged_in() ? get_current_user_id() : ''; ?>"
    data-pusher-key="<?php echo PUSHER_APP_KEY; ?>"
    data-pusher-cluster="<?php echo PUSHER_APP_CLUSTER; ?>"
>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Campus Events</h3>
            <button id="closeSidebar" class="sidebar-toggle d-lg-none">
                <i class="fas fa-times"></i>
            </button>
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
            <nav class="sidebar-nav">
                <ul>
                    <?php 
                    $debug_timings['sidebar_loop_start'] = microtime(true);
                    foreach ($sidebar_nav_map as $item): ?>
                        <?php 
                            // The dashboard is a special case visible to all logged-in users.
                            $has_perm = ($item['permission'] === 'view_dashboard' && $isLoggedIn) || has_permission($item['permission'], $userRoles);
                            if ($has_perm): 
                        ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo strpos($_SERVER['REQUEST_URI'], basename(parse_url($item['url'], PHP_URL_PATH))) !== false ? 'active' : ''; ?>">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; 
                    $debug_timings['sidebar_loop_end'] = microtime(true);
                    ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <button id="sidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title d-none d-sm-block">
                    <?php
                    // A simple way to set the title based on the URL
                    $page = basename($_SERVER['PHP_SELF'] ?? '');
                    if (strpos($_SERVER['REQUEST_URI'], 'users/index.php')) {
                        echo 'User Management';
                    } elseif (strpos($_SERVER['REQUEST_URI'], 'users/edit.php')) {
                        echo 'Edit User';
                    } elseif (strpos($_SERVER['REQUEST_URI'], 'users/create.php')) {
                        echo 'Create User';
                    } elseif (strpos($_SERVER['REQUEST_URI'], 'activity_logs')) {
                        echo 'Activity Logs';
                    } elseif ($page === 'dashboard.php') {
                        echo 'Dashboard';
                    } else {
                        echo 'Campus Events';
                    }
                    ?>
                </h1>
            </div>
            <div class="header-right">
                <?php if ($isLoggedIn): ?>
                    <!-- Notifications Dropdown -->
                    <div class="user-dropdown dropdown notification-dropdown">
                        <button class="user-dropdown-toggle" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="badge rounded-pill bg-danger" id="notification-count"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" id="notification-list" aria-labelledby="notificationDropdown">
                            <a class="dropdown-item text-center" href="#">No new notifications</a>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" id="clear-all-notifications" href="#">Clear All</a></li>
                        </div>
                    </div>

                    <div class="user-dropdown dropdown">
                        <button class="user-dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                            <span class="d-none d-sm-inline"><?php echo htmlspecialchars($userFullName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <a href="<?php echo $absoluteBaseUrl; ?>views/profile/index.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Profile</span>
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo $absoluteBaseUrl; ?>views/auth/logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Hidden divs for JS -->
        <div id="user-info-id" data-user-id="<?php echo htmlspecialchars($isLoggedIn ? get_current_user_id() : ''); ?>" style="display: none;"></div>
        <div id="pusher-info" data-key="<?php echo PUSHER_APP_KEY; ?>" data-cluster="<?php echo PUSHER_APP_CLUSTER; ?>" style="display: none;"></div>
        <div id="user-status" data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>" style="display: none;"></div>

        <!-- Page Content Container, starts here and ends in footer -->
        <main class="page-container">
            <!-- Alert Messages -->
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
            </div>
            <!-- Content starts here -->
<?php
$debug_timings['header_end'] = microtime(true);
$_SESSION['debug_timings'] = $debug_timings;
?>