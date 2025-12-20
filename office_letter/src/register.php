<?php
require_once 'session_config.php';
session_start();
require_once '../config/db.php';
require_once 'csrf_helper.php';
require_once 'validation_helper.php';
require_once 'footer_component.php';

// Check if this is Institution Head creating a user
$is_institution_head = false;
$creator_user_id = null;
if (isset($_SESSION['user_id'])) {
    $check_role = $conn->prepare("SELECT role FROM Users WHERE id = ? LIMIT 1");
    $check_role->bind_param('i', $_SESSION['user_id']);
    $check_role->execute();
    $role_result = $check_role->get_result();
    if ($role_row = $role_result->fetch_assoc()) {
        if ($role_row['role'] === 'institution_head') {
            $is_institution_head = true;
            $creator_user_id = $_SESSION['user_id'];
        }
    }
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    
    // Check if department is required based on role
    $role_input = $_POST['role'] ?? '';
    $roles_without_department = ['institution_head', 'chief_management_assistant', 'postal_subject_officer'];
    $department_required = !in_array($role_input, $roles_without_department);
    
    // Validate form data
    $validation_rules = [
        'name' => ['required' => true, 'type' => 'string', 'max_length' => 100],
        'email' => ['required' => true, 'type' => 'email'],
        'password' => ['required' => true, 'type' => 'password'],
        'role' => ['required' => true, 'type' => 'string'],
        'department_id' => ['required' => $department_required, 'type' => 'integer', 'min' => 1]
    ];
    
    $validation = validate_form_data($_POST, $validation_rules);
    
    if (!empty($validation['errors'])) {
        $error = implode('<br>', $validation['errors']);
    } else {
        $name = $validation['data']['name'];
        $email = $validation['data']['email'];
        $password = $validation['data']['password'];
        $role = $validation['data']['role'];
        $department_id = $validation['data']['department_id'] ?? null;
        
        // Convert empty string to NULL for roles that don't need departments
        if (empty($department_id) || $department_id === '') {
            $department_id = null;
        }
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM Employee WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $error = 'Email already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            // Insert into Employee table - handle NULL department_id
            if ($department_id === null) {
                $stmt = $conn->prepare("INSERT INTO Employee (name, email, password, department_id, role) VALUES (?, ?, ?, NULL, ?)");
                $stmt->bind_param('ssss', $name, $email, $hashed, $role);
            } else {
                $stmt = $conn->prepare("INSERT INTO Employee (name, email, password, department_id, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssis', $name, $email, $hashed, $department_id, $role);
            }
            
            if ($stmt) {
                if ($stmt->execute()) {
                    $employee_id = $conn->insert_id;
                    
                    // Determine approval status and who created the account
                    $approved = $is_institution_head ? 1 : 0; // Auto-approve if created by Institution Head
                    $approved_by = $is_institution_head ? $creator_user_id : null;
                    $approved_at = $is_institution_head ? date('Y-m-d H:i:s') : null;
                    
                    // Insert into Users table with approval fields
                    $userStmt = $conn->prepare("
                        INSERT INTO Users (username, password, employee_id, role, email, approved, approved_by, approved_at, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $username = $email;
                    $userStmt->bind_param('ssissiisi', $username, $hashed, $employee_id, $role, $email, $approved, $approved_by, $approved_at, $creator_user_id);
                    
                    if ($userStmt->execute()) {
                        if ($is_institution_head) {
                            $success = 'User account created and approved successfully!';
                        } else {
                            $success = 'Registration successful! Your account is pending approval by Institution Head.';
                        }
                    } else {
                        $error = 'Registration failed: ' . $userStmt->error;
                    }
                } else {
                    $error = 'Registration failed: ' . $stmt->error;
                }
            } else {
                $error = 'Database error: Could not prepare statement.';
            }
        }
    } else {
        $error = 'Database error: Could not prepare statement.';
    }
    } // Close validation block
}
// Fetch departments for dropdown
$departments = $conn->query("SELECT id, name FROM Department");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-wrapper {
            width: 100%;
            max-width: 550px;
            padding: 20px;
        }
        .register-card {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.5s ease-in-out;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem 2rem 2rem;
            text-align: center;
        }
        .register-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 0 0.5rem 0;
            letter-spacing: 1px;
        }
        .register-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .register-body {
            padding: 2rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .links-section {
            padding: 1.5rem 2rem 2rem 2rem;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .links-section a {
            color: #5569d8;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .links-section a:hover {
            color: #442e7a;
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-card">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i> <?php echo strtoupper(APP_NAME); ?></h1>
                <p>Create your account</p>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
            <?php echo csrf_token_field(); ?>
            
            <div class="mb-3">
                <label for="name" class="form-label">
                    <i class="fas fa-user"></i> Full Name
                </label>
                <input type="text" name="name" id="name" class="form-control" 
                       placeholder="Enter your full name" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" name="email" id="email" class="form-control" 
                       placeholder="Enter your email" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" name="password" id="password" class="form-control" 
                       placeholder="Enter your password" required>
            </div>
            
            <div class="mb-3" id="department-wrapper">
                <label for="department_id" class="form-label">
                    <i class="fas fa-building"></i> Department
                </label>
                <select name="department_id" id="department_id" class="form-select">
                    <option value="">Select Department</option>
                    <?php $firstDeptId = null; $firstDeptName = null; $deptList = [];
                    while ($d = $departments->fetch_assoc()) {
                        if ($firstDeptId === null) { $firstDeptId = $d['id']; $firstDeptName = $d['name']; }
                        $deptList[] = $d;
                    } ?>
                    <?php foreach ($deptList as $d) { ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                    <?php } ?>
                </select>
                <small class="form-text text-muted" id="dept-help"></small>
            </div>
            
            <div class="mb-4">
                <label for="role" class="form-label">
                    <i class="fas fa-user-tag"></i> Role
                </label>
                <select name="role" id="role" class="form-select" required onchange="autoSelectDepartment()">
                    <option value="subject_officer">Subject Officer</option>
                    <option value="department_head">Department Head</option>
                    <option value="chief_management_assistant">Chief Management Assistant</option>
                    <option value="institution_head">Institution Head</option>
                </select>
            </div>
            
            <script>
            function autoSelectDepartment() {
                var role = document.getElementById('role').value;
                var deptSelect = document.getElementById('department_id');
                var deptWrapper = document.getElementById('department-wrapper');
                var deptHelp = document.getElementById('dept-help');
                
                // Roles that don't require a department
                var rolesWithoutDept = ['institution_head', 'chief_management_assistant', 'postal_subject_officer'];
                
                if (rolesWithoutDept.includes(role)) {
                    deptSelect.value = '';
                    deptSelect.required = false;
                    deptWrapper.style.display = 'none';
                    deptHelp.textContent = '';
                } else {
                    deptSelect.required = true;
                    deptWrapper.style.display = 'block';
                    deptHelp.textContent = 'Please select the department you belong to';
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                autoSelectDepartment();
            });
            </script>
            
            <button type="submit" class="btn btn-primary btn-register w-100">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
            
            <div class="mt-3 text-center">
                <a href="index.php" style="color: #5569d8; text-decoration: none; font-weight: 700;">
                    <i class="fas fa-sign-in-alt"></i> Already have an account? Login
                </a>
            </div>
        </form>
    </div>
            
    <div class="links-section">
        <p class="mb-0 text-muted">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> <?php echo APP_NAME; ?> | Created and Developed By Group No-05
        </p>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
