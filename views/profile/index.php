<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: ' . base_url('views/auth/login.php'));
    exit();
}

$conn = get_db_connection();
$user_id = get_current_user_id();

// --- FORM PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'update_profile') {
    
    // Sanitize and retrieve form data
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        if (empty($full_name) || empty($email)) {
            throw new Exception('Full Name and Email are required.');
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('This email address is already in use by another account.');
        }

        if (!empty($password)) {
            if ($password !== $confirm_password) {
                throw new Exception('Passwords do not match.');
            }
            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters long.');
            }
        }

        $conn->beginTransaction();

        $params = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone, 'id' => $user_id];
        $sql = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone";
        
        if (!empty($password)) {
            $sql .= ", password = :password";
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        logUserActivity('update_profile', 'User updated their own profile.');
        
        $conn->commit();

        $_SESSION['full_name'] = $full_name;
        $_SESSION['success'] = 'Profile updated successfully.';
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }

    // Redirect back to the profile page to prevent form re-submission
    header('Location: ' . base_url('views/profile/index.php'));
    exit();
}
// --- END FORM PROCESSING ---


// --- PAGE DISPLAY ---
require_once __DIR__ . '/../includes/header.php';

// Fetch current user's data for display
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to fetch user data.';
    $user = [];
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Profile</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo base_url('views/profile/index.php'); ?>" method="POST">
                        <input type="hidden" name="form_action" value="update_profile">
                        
                        <div class="row g-3">
                            <!-- Full Name -->
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>

                            <!-- Username (Read-only) -->
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>

                            <div class="col-12">
                                <hr>
                                <h6 class="text-muted">Change Password</h6>
                            </div>
                            
                            <!-- New Password -->
                            <div class="col-md-6">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>

                            <!-- Confirm New Password -->
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 