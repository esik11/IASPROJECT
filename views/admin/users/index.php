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

$db = new Database();
$conn = $db->getConnection();

// Fetch employees with roles
$stmt = $conn->prepare("
    SELECT e.*, GROUP_CONCAT(r.role_name) as role_names 
    FROM employees e 
    LEFT JOIN employee_roles er ON e.employee_id = er.employee_id 
    LEFT JOIN roles r ON er.role_id = r.role_id 
    GROUP BY e.employee_id
");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students with roles
$stmt = $conn->prepare("
    SELECT s.*, GROUP_CONCAT(r.role_name) as role_names 
    FROM students s 
    LEFT JOIN student_roles sr ON s.student_id = sr.student_id 
    LEFT JOIN roles r ON sr.role_id = r.role_id 
    GROUP BY s.student_id
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all roles for modals
$stmt = $conn->prepare("SELECT * FROM roles");
$stmt->execute();
$all_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <h1>User Management</h1>

    <div class="action-buttons">
        <button type="button" class="btn btn-primary" id="addEmployeeBtn" data-bs-toggle="modal" data-bs-target="#employeeModal">
            Add New Employee
        </button>
        <button type="button" class="btn btn-secondary" id="addStudentBtn" data-bs-toggle="modal" data-bs-target="#studentModal">
            Add New Student
        </button>
    </div>

    <h2>Employees</h2>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                    <td><?php echo htmlspecialchars($employee['department'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($employee['position'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($employee['role_names'] ?? ''); ?></td>
                    <td>
                        <span class="badge <?php echo $employee['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo htmlspecialchars($employee['status']); ?>
                        </span>
                    </td>
                    <td class="actions">
                        <button type="button" class="btn btn-sm btn-info" 
                                onclick="viewEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
                            View
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
                            Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" 
                                onclick="deleteEmployee(<?php echo $employee['employee_id']; ?>)">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2>Students</h2>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Student Number</th>
                    <th>Department</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['student_number'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($student['department'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($student['role_names'] ?? ''); ?></td>
                    <td>
                        <span class="badge <?php echo $student['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo htmlspecialchars($student['status']); ?>
                        </span>
                    </td>
                    <td class="actions">
                        <button type="button" class="btn btn-sm btn-info" 
                                onclick="viewStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                            View
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                            Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" 
                                onclick="deleteStudent(<?php echo $student['student_id']; ?>)">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Include Bootstrap CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- View Employee Modal -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Name:</strong> <span id="viewEmployeeName"></span>
                </div>
                <div class="mb-3">
                    <strong>Email:</strong> <span id="viewEmployeeEmail"></span>
                </div>
                <div class="mb-3">
                    <strong>Department:</strong> <span id="viewEmployeeDepartment"></span>
                </div>
                <div class="mb-3">
                    <strong>Position:</strong> <span id="viewEmployeePosition"></span>
                </div>
                <div class="mb-3">
                    <strong>Roles:</strong> <span id="viewEmployeeRoles"></span>
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> <span id="viewEmployeeStatus"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Name:</strong> <span id="viewStudentName"></span>
                </div>
                <div class="mb-3">
                    <strong>Email:</strong> <span id="viewStudentEmail"></span>
                </div>
                <div class="mb-3">
                    <strong>Student Number:</strong> <span id="viewStudentNumber"></span>
                </div>
                <div class="mb-3">
                    <strong>Department:</strong> <span id="viewStudentDepartment"></span>
                </div>
                <div class="mb-3">
                    <strong>Roles:</strong> <span id="viewStudentRoles"></span>
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> <span id="viewStudentStatus"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalTitle">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="employeeForm" action="process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="employeeAction" value="create">
                    <input type="hidden" name="type" value="employee">
                    <input type="hidden" name="id" id="employeeId">

                    <div class="mb-3">
                        <label for="employeeFirstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="employeeFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeLastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="employeeLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="employeeEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="employeeUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeePassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="employeePassword" name="password">
                        <small class="text-muted">Leave empty to keep current password when editing</small>
                    </div>
                    <div class="mb-3">
                        <label for="employeeDepartment" class="form-label">Department</label>
                        <select class="form-control" id="employeeDepartment" name="department">
                            <option value="">Select Department</option>
                            <option value="CET">CET - College Of Engineering and Technology</option>
                            <option value="CAS">CAS - College of Arts and Science</option>
                            <option value="CCJ">CCJ - College of Criminal Justice</option>
                            <option value="CBE">CBE - College of Business Education</option>
                            <option value="CTE">CTE - College of Teachers Education</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="employeePosition" class="form-label">Position</label>
                        <input type="text" class="form-control" id="employeePosition" name="position">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roles</label>
                        <div class="role-checkboxes">
                            <?php foreach ($all_roles as $role): 
                                if (!in_array($role['role_name'], ['Student', 'Guest'])): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" 
                                           value="<?php echo $role['role_id']; ?>" 
                                           id="employeeRole<?php echo $role['role_id']; ?>">
                                    <label class="form-check-label" for="employeeRole<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </label>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="employeeStatus" class="form-label">Status</label>
                        <select class="form-control" id="employeeStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalTitle">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="studentForm" action="process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="studentAction" value="create">
                    <input type="hidden" name="type" value="student">
                    <input type="hidden" name="id" id="studentId">

                    <div class="mb-3">
                        <label for="studentFirstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="studentFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentLastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="studentLastName" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="studentEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="studentUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="studentPassword" name="password">
                        <small class="text-muted">Leave empty to keep current password when editing</small>
                    </div>
                    <div class="mb-3">
                        <label for="studentNumber" class="form-label">Student Number</label>
                        <input type="text" class="form-control" id="studentNumber" name="student_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentDepartment" class="form-label">Department</label>
                        <select class="form-control" id="studentDepartment" name="department">
                            <option value="">Select Department</option>
                            <option value="CET">CET - College Of Engineering and Technology</option>
                            <option value="CAS">CAS - College of Arts and Science</option>
                            <option value="CCJ">CCJ - College of Criminal Justice</option>
                            <option value="CBE">CBE - College of Business Education</option>
                            <option value="CTE">CTE - College of Teachers Education</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <div class="role-checkboxes">
                            <?php foreach ($all_roles as $role): 
                                if (in_array($role['role_name'], ['Student', 'Guest'])): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="roles[]" 
                                           value="<?php echo $role['role_id']; ?>" 
                                           id="studentRole<?php echo $role['role_id']; ?>"
                                           <?php echo $role['role_name'] === 'Student' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="studentRole<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </label>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="studentStatus" class="form-label">Status</label>
                        <select class="form-control" id="studentStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this user?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" action="process.php" method="POST" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="type" id="deleteType">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    padding: 2rem;
}

h1 {
    color: #333;
    margin-bottom: 1.5rem;
}

h2 {
    color: #444;
    margin: 2rem 0 1rem;
}

.action-buttons {
    margin-bottom: 2rem;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    margin-left: 0.5rem;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
    margin-right: 0.25rem;
}

.btn-info:hover {
    background-color: #138496;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
    text-align: left;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

.actions {
    white-space: nowrap;
}

.table-responsive {
    display: block;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    border-radius: 0.25rem;
}

.bg-success {
    background-color: #28a745 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.role-checkboxes {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    padding: 0.5rem;
    border-radius: 0.25rem;
}

.form-check {
    margin-bottom: 0.5rem;
}

.form-check:last-child {
    margin-bottom: 0;
}
</style>

<script>
// Employee functions
function viewEmployee(employee) {
    document.getElementById('viewEmployeeName').textContent = employee.first_name + ' ' + employee.last_name;
    document.getElementById('viewEmployeeEmail').textContent = employee.email;
    document.getElementById('viewEmployeeDepartment').textContent = employee.department || 'N/A';
    document.getElementById('viewEmployeePosition').textContent = employee.position || 'N/A';
    document.getElementById('viewEmployeeRoles').textContent = employee.role_names || 'N/A';
    document.getElementById('viewEmployeeStatus').textContent = employee.status;
    
    new bootstrap.Modal(document.getElementById('viewEmployeeModal')).show();
}

function editEmployee(employee) {
    document.getElementById('employeeModalTitle').textContent = 'Edit Employee';
    document.getElementById('employeeAction').value = 'update';
    document.getElementById('employeeId').value = employee.employee_id;
    document.getElementById('employeeFirstName').value = employee.first_name;
    document.getElementById('employeeLastName').value = employee.last_name;
    document.getElementById('employeeEmail').value = employee.email;
    document.getElementById('employeeUsername').value = employee.username;
    document.getElementById('employeePassword').value = '';
    document.getElementById('employeeDepartment').value = employee.department || '';
    document.getElementById('employeePosition').value = employee.position || '';
    document.getElementById('employeeStatus').value = employee.status;

    // Reset all role checkboxes
    document.querySelectorAll('#employeeModal input[name="roles[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Check the roles that the employee has
    if (employee.role_names) {
        const roles = employee.role_names.split(',');
        roles.forEach(role => {
            const checkbox = document.querySelector(`#employeeModal input[data-role="${role.trim()}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }

    new bootstrap.Modal(document.getElementById('employeeModal')).show();
}

function deleteEmployee(id) {
    document.getElementById('deleteType').value = 'employee';
    document.getElementById('deleteId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Student functions
function viewStudent(student) {
    document.getElementById('viewStudentName').textContent = student.first_name + ' ' + student.last_name;
    document.getElementById('viewStudentEmail').textContent = student.email;
    document.getElementById('viewStudentNumber').textContent = student.student_number || 'N/A';
    document.getElementById('viewStudentDepartment').textContent = student.department || 'N/A';
    document.getElementById('viewStudentRoles').textContent = student.role_names || 'N/A';
    document.getElementById('viewStudentStatus').textContent = student.status;
    
    new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
}

function editStudent(student) {
    document.getElementById('studentModalTitle').textContent = 'Edit Student';
    document.getElementById('studentAction').value = 'update';
    document.getElementById('studentId').value = student.student_id;
    document.getElementById('studentFirstName').value = student.first_name;
    document.getElementById('studentLastName').value = student.last_name;
    document.getElementById('studentEmail').value = student.email;
    document.getElementById('studentUsername').value = student.username;
    document.getElementById('studentPassword').value = '';
    document.getElementById('studentNumber').value = student.student_number || '';
    document.getElementById('studentDepartment').value = student.department || '';
    document.getElementById('studentStatus').value = student.status;

    // Reset all role radio buttons
    document.querySelectorAll('#studentModal input[name="roles[]"]').forEach(radio => {
        radio.checked = false;
    });

    // Check the role that the student has
    if (student.role_names) {
        const roles = student.role_names.split(',');
        roles.forEach(role => {
            const radio = document.querySelector(`#studentModal input[data-role="${role.trim()}"]`);
            if (radio) radio.checked = true;
        });
    }

    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

function deleteStudent(id) {
    document.getElementById('deleteType').value = 'student';
    document.getElementById('deleteId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Add new user handlers
document.getElementById('addEmployeeBtn').addEventListener('click', function() {
    document.getElementById('employeeModalTitle').textContent = 'Add New Employee';
    document.getElementById('employeeForm').reset();
    document.getElementById('employeeAction').value = 'create';
    document.getElementById('employeeId').value = '';
    new bootstrap.Modal(document.getElementById('employeeModal')).show();
});

document.getElementById('addStudentBtn').addEventListener('click', function() {
    document.getElementById('studentModalTitle').textContent = 'Add New Student';
    document.getElementById('studentForm').reset();
    document.getElementById('studentAction').value = 'create';
    document.getElementById('studentId').value = '';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>