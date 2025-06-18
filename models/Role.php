<?php
// models/User.php
require_once '../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public $user_id;
    public $username;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $phone;
    public $user_type;
    public $status;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password_hash, first_name, last_name, phone, user_type, status) 
                  VALUES (:username, :email, :password_hash, :first_name, :last_name, :phone, :user_type, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':user_type', $this->user_type);
        $stmt->bindParam(':status', $this->status);
        
        if ($stmt->execute()) {
            $this->user_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Read all users
    public function read() {
        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                  FROM " . $this->table . " u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  GROUP BY u.user_id
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single user
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->phone = $row['phone'];
            $this->user_type = $row['user_type'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET username = :username, email = :email, first_name = :first_name, 
                      last_name = :last_name, phone = :phone, user_type = :user_type, status = :status 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':user_type', $this->user_type);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        return $stmt->execute();
    }

    // Authenticate user
    public function authenticate($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username AND status = 'active' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($password, $row['password_hash'])) {
            $this->user_id = $row['user_id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->user_type = $row['user_type'];
            return true;
        }
        return false;
    }

    // Get user roles
    public function getUserRoles($user_id) {
        $query = "SELECT r.role_name FROM roles r 
                  JOIN user_roles ur ON r.role_id = ur.role_id 
                  WHERE ur.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $roles = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = $row['role_name'];
        }
        return $roles;
    }

    // Assign role to user
    public function assignRole($user_id, $role_id, $assigned_by) {
        $query = "INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (:user_id, :role_id, :assigned_by)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':assigned_by', $assigned_by);
        return $stmt->execute();
    }

    // Remove role from user
    public function removeRole($user_id, $role_id) {
        $query = "DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':role_id', $role_id);
        return $stmt->execute();
    }

    // Check if username exists
    public function usernameExists($username, $exclude_user_id = null) {
        $query = "SELECT user_id FROM " . $this->table . " WHERE username = :username";
        if ($exclude_user_id) {
            $query .= " AND user_id != :exclude_user_id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        if ($exclude_user_id) {
            $stmt->bindParam(':exclude_user_id', $exclude_user_id);
        }
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Check if email exists
    public function emailExists($email, $exclude_user_id = null) {
        $query = "SELECT user_id FROM " . $this->table . " WHERE email = :email";
        if ($exclude_user_id) {
            $query .= " AND user_id != :exclude_user_id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        if ($exclude_user_id) {
            $stmt->bindParam(':exclude_user_id', $exclude_user_id);
        }
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>