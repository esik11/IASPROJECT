<?php
// The header now handles session start, login check, and user info fetching.
require_once __DIR__ . '/includes/header.php';

// Get a database connection
$conn = get_db_connection();
?>

<div class="container-fluid">
    <div class="dashboard-header mb-4">
        <h1 class="page-title">Dashboard</h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>!</p>
    </div>

    <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
            <h2 class="h4 mb-3">Quick Actions</h2>
            </div>
            <?php
        $action_cards = [
                [
                'label' => 'Submit Event Idea',
                'permission' => 'submit_event_idea',
                    'icon' => 'fas fa-lightbulb',
                    'url' => '#'
                ],
                [
                    'label' => 'Create Official Event',
                'permission' => 'create_official_event',
                    'icon' => 'fas fa-plus-circle',
                    'url' => '#'
                ],
                [
                    'label' => 'Reserve Venue',
                'permission' => 'reserve_venue',
                    'icon' => 'fas fa-calendar-check',
                    'url' => '#'
                ],
                [
                    'label' => 'Approve/Reject Events',
                'permission' => 'approve_reject_events',
                    'icon' => 'fas fa-check-double',
                    'url' => '#'
                ],
                [
                    'label' => 'Assign Staff',
                'permission' => 'assign_staff',
                    'icon' => 'fas fa-users-cog',
                    'url' => '#'
                ],
                [
                'label' => 'Manage Users',
                'permission' => 'manage_users',
                'icon' => 'fas fa-users-cog',
                'url' => base_url('views/admin/users/index.php')
            ]
            ];
            
        foreach ($action_cards as $item):
                if (has_permission($item['permission'])): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" class="card-link">
                            <div class="card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="feature-icon mb-3">
                                        <i class="<?php echo htmlspecialchars($item['icon']); ?> fa-3x"></i>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['label']); ?></h5>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif;
            endforeach; ?>
        </div>


    <?php if (has_permission('manage_users')): ?>
        <hr class="my-4">
        <!-- Admin Statistics -->
        <div class="row mb-4">
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Users</h6>
                                <h4 class="mb-0">
                                    <?php
                                    $stmt = $conn->query("SELECT COUNT(*) FROM users");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h4>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Active Users</h6>
                                <h4 class="mb-0">
                                    <?php
                                    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h4>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Today's Activities</h6>
                                <h4 class="mb-0">
                                    <?php
                                    $stmt = $conn->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h4>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-history fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (has_permission('view_activity_logs')): ?>
        <!-- Recent Activity Log -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Activity Logs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->query("
                                        SELECT al.*, u.full_name 
                                        FROM activity_logs al
                                        LEFT JOIN users u ON al.user_id = u.id
                                        ORDER BY al.created_at DESC 
                                        LIMIT 5
                                    ");
                                    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($activities):
                                        foreach ($activities as $activity):
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($activity['action']); ?></span></td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></td>
                                            </tr>
                                        <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No recent activity found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
.card-link {
    text-decoration: none;
    color: inherit;
}
.card-link .card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.card-link .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}
.feature-icon {
    color: var(--primary-color);
}
</style>
