<?php
// controllers/UserController.php
require_once '../models/User.php';
require_once '../models/Role.php';
require_once '../config/database.php';

class UserController {
    private $user;
    private $role;

    public function __construct() {
        $this->user = new User();
        $this->role = new Role();
    }

    // Display all users
    public function index() {
        requireAdmin();
        $stmt = $this->user->read();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../views/admin/users/index.php';
    }

    // Show create user form
    public function create() {
        requireAdmin();
        $role_stmt = $this->role->read();
        $roles = $role_stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../views/admin/users/create.php';
    }

    // Store new user
    public function store() {
        requireAdmin();
        $errors = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validate input
            if (empty($_POST['username'])) {
                $errors[] = "Username is required";
            } elseif ($this->user->usernameExists($_POST['username'])) {
                $errors[] = "Username already exists";
            }

            if (empty($_POST['email'])) {
                $errors[] = "Email is required";
            } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            } elseif ($this->user->emailExists($_POST['email'])) {
                $errors[] = "Email already exists";
            }

            if (empty($_POST['password'])) {
                $errors[] = "Password is required";
            } elseif (strlen($_POST['password']) < 6) {
                $errors[] = "Password must be at least 6 characters";
            }

            if (empty($_POST['first_name'])) {
                $errors[] = "First name is required";
            }

            if (empty($_POST['last_name'])) {
                $errors[] = "Last name is required";
            }

            if (empty($errors)) {
                // Set user properties
                $this->user->username = $_POST['username'];
                $this->user->email = $_POST['email'];
                $this->user->password_hash = $_POST['password'];
                $this->user->first_name = $_POST['first_name'];
                $this->user->last_name = $_POST['last_name'];
                $this->user->phone = $_POST['phone'] ?? '';
                $this->user->user_type = $_POST['user_type'];
                $this->user->status = $_POST['status'] ?? 'active';

                // Create user
                if ($this->user->create()) {
                    // Assign roles if selected
                    if (!empty($_POST['roles'])) {
                        foreach ($_POST['roles'] as $role_id) {
                            $this->user->assignRole($this->user->user_id, $role_id, $_SESSION['user_id']);
                        }
                    }
                    
                    $_SESSION['success'] = "User created successfully";
                    header('Location: /controllers/UserController.php?action=index');
                    exit();
                } else {
                    $errors[] = "Error creating user";
                }
            }
        }

        // If there are errors, show create form with errors
        $role_stmt = $this->role->read();
        $roles = $role_stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../views/admin/users/create.php';
    }

    // Show edit user form
    public function edit() {
        requireAdmin();
        if (isset($_GET['id'])) {
            $this->user->user_id = $_GET['id'];
            if ($this->user->readOne()) {
                $role_stmt = $this->role->read();
                $roles = $role_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get user's current roles
                $user_roles = $this->user->getUserRoles($this->user->user_id);
                
                require_once '../views/admin/users/edit.php';
            } else {
                $_SESSION['error'] = "User not found";
                header('Location: /controllers/UserController.php?action=index');
                exit();
            }
        }
    }

    // Update user
    public function update() {
        requireAdmin();
        $errors = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
            $this->user->user_id = $_POST['user_id'];
            
            // Validate input
            if (empty($_POST['username'])) {
                $errors[] = "Username is required";
            } elseif ($this->user->usernameExists($_POST['username'], $this->user->user_id)) {
                $errors[] = "Username already exists";
            }

            if (empty($_POST['email'])) {
                $errors[] = "Email is required";
            } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            } elseif ($this->user->emailExists($_POST['email'], $this->user->user_id)) {
                $errors[] = "Email already exists";
            }

            if (empty($errors)) {
                // Set user properties
                $this->user->username = $_POST['username'];
                $this->user->email = $_POST['email'];
                $this->user->first_name = $_POST['first_name'];
                $this->user->last_name = $_POST['last_name'];
                $this->user->phone = $_POST['phone'] ?? '';
                $this->user->user_type = $_POST['user_type'];
                $this->user->status = $_POST['status'];

                // Update user
                if ($this->user->update()) {
                    $_SESSION['success'] = "User updated successfully";
                    header('Location: /controllers/UserController.php?action=index');
                    exit();
                } else {
                    $errors[] = "Error updating user";
                }
            }
        }

        // If there are errors, show edit form with errors
        $this->edit();
    }

    // Delete user
    public function delete() {
        requireAdmin();
        if (isset($_GET['id'])) {
            $this->user->user_id = $_GET['id'];
            
            // Don't allow deletion of current user
            if ($this->user->user_id == $_SESSION['user_id']) {
                $_SESSION['error'] = "Cannot delete your own account";
            } elseif ($this->user->delete()) {
                $_SESSION['success'] = "User deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting user";
            }
        }
        
        header('Location: /controllers/UserController.php?action=index');
        exit();
    }

    // Show user details
    public function show() {
        requireAdmin();
        if (isset($_GET['id'])) {
            $this->user->user_id = $_GET['id'];
            if ($this->user->readOne()) {
                $user_roles = $this->user->getUserRoles($this->user->user_id);
                require_once '../views/admin/users/show.php';
            } else {
                $_SESSION['error'] = "User not found";
                header('Location: /controllers/UserController.php?action=index');
                exit();
            }
        }
    }
}

// Handle routing
if (isset($_GET['action'])) {
    $controller = new UserController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'index':
            $controller->index();
            break;
        case 'create':
            $controller->create();
            break;
        case 'store':
            $controller->store();
            break;
        case 'edit':
            $controller->edit();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'show':
            $controller->show();
            break;
        default:
            $controller->index();
            break;
    }
} else {
    $controller = new UserController();
    $controller->index();
}
?>