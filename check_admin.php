<?php
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "Admin user does not exist. Creating admin user...\n";
        
        // Create admin user
        $password_hash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, full_name, email, user_type, department, position, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'admin',
            $password_hash,
            'System Administrator',
            'admin@campus.edu',
            'admin',
            'IT',
            'Administrator',
            'active'
        ]);
        
        $admin_id = $conn->lastInsertId();
        
        // Get Admin role ID
        $stmt = $conn->prepare("SELECT id FROM roles WHERE name = 'Admin'");
        $stmt->execute();
        $admin_role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin_role) {
            // Create Admin role if it doesn't exist
            $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
            $stmt->execute(['Admin', 'System administrator with full access']);
            $admin_role_id = $conn->lastInsertId();
        } else {
            $admin_role_id = $admin_role['id'];
        }
        
        // Assign admin role
        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)");
        $stmt->execute([$admin_id, $admin_role_id, $admin_id]);
        
        echo "Admin user created successfully!\n";
    } else {
        echo "Admin user exists. Checking roles...\n";
        
        // Check if admin has Admin role
        $stmt = $conn->prepare("
            SELECT r.name 
            FROM roles r 
            INNER JOIN user_roles ur ON r.id = ur.role_id 
            WHERE ur.user_id = ? AND r.name = 'Admin'
        ");
        $stmt->execute([$admin['id']]);
        $has_admin_role = $stmt->fetch();
        
        if (!$has_admin_role) {
            echo "Admin user missing Admin role. Adding role...\n";
            
            // Get Admin role ID
            $stmt = $conn->prepare("SELECT id FROM roles WHERE name = 'Admin'");
            $stmt->execute();
            $admin_role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin_role) {
                // Create Admin role if it doesn't exist
                $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
                $stmt->execute(['Admin', 'System administrator with full access']);
                $admin_role_id = $conn->lastInsertId();
            } else {
                $admin_role_id = $admin_role['id'];
            }
            
            // Assign admin role
            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)");
            $stmt->execute([$admin['id'], $admin_role_id, $admin['id']]);
            
            echo "Admin role added successfully!\n";
        } else {
            echo "Admin user setup is correct!\n";
        }
    }
    
    echo "\nYou can now log in with:\n";
    echo "Username: admin\n";
    echo "Password: password\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?> 