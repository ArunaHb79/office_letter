<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $conn->prepare("SELECT u.id, u.username, u.role, u.employee_id, e.name as employee_name FROM Users u LEFT JOIN Employee e ON u.employee_id = e.id WHERE u.id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Role Check</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Current User Role Information</h2>
    <div class="card mt-3">
        <div class="card-body">
            <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Role (from database):</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($user['role']); ?></span></p>
            <p><strong>Employee ID:</strong> <?php echo $user['employee_id'] ?? 'NULL'; ?></p>
            <p><strong>Employee Name:</strong> <?php echo htmlspecialchars($user['employee_name'] ?? 'No employee record'); ?></p>
            
            <hr>
            
            <p><strong>Role after normalization:</strong> <?php echo strtolower(str_replace(' ', '_', $user['role'])); ?></p>
            
            <hr>
            
            <p><strong>Access Check:</strong></p>
            <?php 
            $normalized_role = strtolower(str_replace(' ', '_', $user['role']));
            $can_create = in_array($normalized_role, ['department_head', 'subject_officer', 'institution_head', 'postal_subject_officer', 'chief_management_assistant']);
            $can_assign = in_array($normalized_role, ['department_head', 'institution_head', 'postal_subject_officer']);
            ?>
            <p>Can create letters: <?php echo $can_create ? '<span class="badge bg-success">YES</span>' : '<span class="badge bg-danger">NO</span>'; ?></p>
            <p>Can assign letters: <?php echo $can_assign ? '<span class="badge bg-success">YES</span>' : '<span class="badge bg-warning">NO</span>'; ?></p>
            
            <?php if ($normalized_role === 'chief_management_assistant'): ?>
                <div class="alert alert-warning mt-3">
                    <strong>Chief Management Assistant:</strong> Can add received letters but cannot assign them to employees. Can only update letters assigned to you by Institution Head.
                </div>
            <?php endif; ?>
            
            <?php if ($can_create): ?>
                <a href="letter_form.php" class="btn btn-success mt-3">Try Accessing Letter Form</a>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>
