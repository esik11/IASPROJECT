<?php
// controllers/AuthController.php
require_once '../models/User.php';
require_once '../config/database.php';

class AuthController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    // Show login form
    public function login() {
        if (isLoggedIn()) {
            header('Location: /views/dashboard.php');
            exit();
        }
        require_once '../views/auth/login.php';
    }

    // Process login
    public function authenticate() {
        $errors = array();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username)) {
                $errors[] = "Username is required";
            }
            
            if (empty($password)) {
                $errors[] = "Password is required";
            }
            
            if (empty($errors)) {
                if ($this->user->authenticate($username, $password)) {
                    // Set session variables
                    $_SESSION['user_id'] = $this->user->user_id;
                    $_SESSION['username'] = $this->user->username;
                    $_SESSION['first_name'] = $this->user->first_name;
                    $_SESSION['last_name'] = $this->user->last_name;
                    $_SESSION['user_type'] = $this->user->user_type;
                    $_SESSION['roles'] = $this->user->getUserRoles($this->user->user_id);
                    
                    // Redirect to dashboard
                    header('Location: /views/dashboard.php');
                    exit();
                } else {
                    $errors[] = "Invalid username or password";
                }
            }
        }
        
        // Show login form with errors
        require_once '../views/auth/login.php';
    }

    // Logout
    public function logout() {
        session_destroy();
        header('Location: /views/auth/login.php');
        exit();
    }
}

// Handle routing
if (isset($_GET['action'])) {
    $controller = new AuthController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'login':
            $controller->login();
            break;
        case 'authenticate':
            $controller->authenticate();
            break;
        case 'logout':
            $controller->logout();
            break;
        default:
            $controller->login();
            break;
    }
} else {
    $controller = new AuthController();
    $controller->login();
}
?>