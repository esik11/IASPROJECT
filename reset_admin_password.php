<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// --- IMPORTANT ---
// This is a temporary script to reset the admin password.
// Delete this file immediately after use.

$new_password = 'admin123'; // The password you want to set
$admin_username = 'admin';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Hash the new password using the standard PHP function
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password for the admin user
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashed_password, $admin_username]);

    if ($stmt->rowCount() > 0) {
        echo "<h1>Success!</h1>";
        echo "<p>The password for username '<strong>" . htmlspecialchars($admin_username) . "</strong>' has been reset to '<strong>" . htmlspecialchars($new_password) . "</strong>'.</p>";
        echo "<p style='color:red;'><strong>Please delete this file (`reset_admin_password.php`) immediately.</strong></p>";
    } else {
        echo "<h1>Error!</h1>";
        echo "<p>Could not find a user with the username '<strong>" . htmlspecialchars($admin_username) . "</strong>'.</p>";
        echo "<p>Please ensure the admin user exists in the 'users' table.</p>";
    }

} catch (Exception $e) {
    echo "<h1>An error occurred:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
} 