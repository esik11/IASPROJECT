<?php
require_once __DIR__ . '/../../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['roles']) || !in_array('Admin', $_SESSION['roles'])) {
    header('Location: /IASPROJECT/views/auth/login.php');
    exit();
}

$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? '';

if (!in_array($action, ['create', 'update', 'delete']) || !in_array($type, ['employee', 'student'])) {
    $_SESSION['error'] = "Invalid action or user type.";
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if ($action === 'create') {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate required fields
        $required = ['username', 'password', 'email', 'first_name', 'last_name', 'roles'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required.");
            }
        }

        // Validate roles based on user type
        $role_ids = $_POST['roles'];
        if ($type === 'student') {
            // For students, verify only Student or Guest roles are selected
            $stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_id IN (" . implode(',', $role_ids) . ") AND role_name NOT IN ('Student', 'Guest')");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Students can only be assigned Student or Guest roles.");
            }
        } else {
            // For employees, verify Student or Guest roles are not selected
            $stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_id IN (" . implode(',', $role_ids) . ") AND role_name IN ('Student', 'Guest')");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Employees cannot be assigned Student or Guest roles.");
            }
        }

        // Check if username exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE username = ? UNION ALL SELECT COUNT(*) FROM students WHERE username = ?");
        $stmt->execute([$_POST['username'], $_POST['username']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already exists.");
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE email = ? UNION ALL SELECT COUNT(*) FROM students WHERE email = ?");
        $stmt->execute([$_POST['email'], $_POST['email']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Email already exists.");
        }

        // Hash password
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insert user based on type
        if ($type === 'employee') {
            $stmt = $conn->prepare("
                INSERT INTO employees (username, password_hash, email, first_name, last_name, phone, department, position, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['username'],
                $password_hash,
                $_POST['email'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['phone'] ?? null,
                $_POST['department'] ?? null,
                $_POST['position'] ?? null,
                $_POST['status']
            ]);
            $user_id = $conn->lastInsertId();

            // Assign roles
            $stmt = $conn->prepare("INSERT INTO employee_roles (employee_id, role_id, assigned_by) VALUES (?, ?, ?)");
            foreach ($role_ids as $role_id) {
                $stmt->execute([$user_id, $role_id, $_SESSION['employee_id']]);
            }
        } else {
            // Validate student number
            if (empty($_POST['student_number'])) {
                throw new Exception("Student number is required.");
            }

            // Check if student number exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_number = ?");
            $stmt->execute([$_POST['student_number']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Student number already exists.");
            }

            $stmt = $conn->prepare("
                INSERT INTO students (username, password_hash, email, first_name, last_name, phone, student_number, department, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['username'],
                $password_hash,
                $_POST['email'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['phone'] ?? null,
                $_POST['student_number'],
                $_POST['department'] ?? null,
                $_POST['status']
            ]);
            $user_id = $conn->lastInsertId();

            // Assign roles
            $stmt = $conn->prepare("INSERT INTO student_roles (student_id, role_id, assigned_by) VALUES (?, ?, ?)");
            foreach ($role_ids as $role_id) {
                $stmt->execute([$user_id, $role_id, $_SESSION['employee_id']]);
            }
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = ucfirst($type) . " created successfully.";
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php');
        exit();
    }
} elseif ($action === 'update') {
    try {
        // Start transaction
        $conn->beginTransaction();

        $id = $_POST['id'] ?? '';
        if (!$id) {
            throw new Exception("ID is required.");
        }

        // Validate required fields
        $required = ['username', 'email', 'first_name', 'last_name', 'roles'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required.");
            }
        }

        // Validate roles based on user type
        $role_ids = $_POST['roles'];
        if ($type === 'student') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_id IN (" . implode(',', $role_ids) . ") AND role_name NOT IN ('Student', 'Guest')");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Students can only be assigned Student or Guest roles.");
            }
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_id IN (" . implode(',', $role_ids) . ") AND role_name IN ('Student', 'Guest')");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Employees cannot be assigned Student or Guest roles.");
            }
        }

        // Check if username exists (excluding current user)
        if ($type === 'employee') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE username = ? AND employee_id != ? UNION ALL SELECT COUNT(*) FROM students WHERE username = ?");
            $stmt->execute([$_POST['username'], $id, $_POST['username']]);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE username = ? UNION ALL SELECT COUNT(*) FROM students WHERE username = ? AND student_id != ?");
            $stmt->execute([$_POST['username'], $_POST['username'], $id]);
        }
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already exists.");
        }

        // Check if email exists (excluding current user)
        if ($type === 'employee') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE email = ? AND employee_id != ? UNION ALL SELECT COUNT(*) FROM students WHERE email = ?");
            $stmt->execute([$_POST['email'], $id, $_POST['email']]);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE email = ? UNION ALL SELECT COUNT(*) FROM students WHERE email = ? AND student_id != ?");
            $stmt->execute([$_POST['email'], $_POST['email'], $id]);
        }
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Email already exists.");
        }

        // Update user based on type
        if ($type === 'employee') {
            $sql = "UPDATE employees SET 
                    username = ?, 
                    email = ?, 
                    first_name = ?, 
                    last_name = ?, 
                    phone = ?, 
                    department = ?, 
                    position = ?, 
                    status = ?";
            $params = [
                $_POST['username'],
                $_POST['email'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['phone'] ?? null,
                $_POST['department'] ?? null,
                $_POST['position'] ?? null,
                $_POST['status']
            ];

            // Add password to update if provided
            if (!empty($_POST['password'])) {
                $sql .= ", password_hash = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE employee_id = ?";
            $params[] = $id;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Update roles
            $stmt = $conn->prepare("DELETE FROM employee_roles WHERE employee_id = ?");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("INSERT INTO employee_roles (employee_id, role_id, assigned_by) VALUES (?, ?, ?)");
            foreach ($role_ids as $role_id) {
                $stmt->execute([$id, $role_id, $_SESSION['employee_id']]);
            }
        } else {
            // Validate student number if provided
            if (!empty($_POST['student_number'])) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_number = ? AND student_id != ?");
                $stmt->execute([$_POST['student_number'], $id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Student number already exists.");
                }
            }

            $sql = "UPDATE students SET 
                    username = ?, 
                    email = ?, 
                    first_name = ?, 
                    last_name = ?, 
                    phone = ?, 
                    student_number = ?,
                    department = ?, 
                    status = ?";
            $params = [
                $_POST['username'],
                $_POST['email'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['phone'] ?? null,
                $_POST['student_number'],
                $_POST['department'] ?? null,
                $_POST['status']
            ];

            // Add password to update if provided
            if (!empty($_POST['password'])) {
                $sql .= ", password_hash = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE student_id = ?";
            $params[] = $id;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Update roles
            $stmt = $conn->prepare("DELETE FROM student_roles WHERE student_id = ?");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("INSERT INTO student_roles (student_id, role_id, assigned_by) VALUES (?, ?, ?)");
            foreach ($role_ids as $role_id) {
                $stmt->execute([$id, $role_id, $_SESSION['employee_id']]);
            }
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = ucfirst($type) . " updated successfully.";
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php');
        exit();
    }
} elseif ($action === 'delete') {
    try {
        // Start transaction
        $conn->beginTransaction();

        $id = $_POST['id'] ?? '';
        if (!$id) {
            throw new Exception("ID is required.");
        }

        // Delete user based on type
        if ($type === 'employee') {
            // Check if trying to delete self
            if ($id == $_SESSION['employee_id']) {
                throw new Exception("You cannot delete your own account.");
            }

            $stmt = $conn->prepare("DELETE FROM employee_roles WHERE employee_id = ?");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $conn->prepare("DELETE FROM student_roles WHERE student_id = ?");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->execute([$id]);
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = ucfirst($type) . " deleted successfully.";
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php');
        exit();
    }
}

// If we get here, something went wrong
$_SESSION['error'] = "Invalid request.";
header('Location: index.php');
exit(); 