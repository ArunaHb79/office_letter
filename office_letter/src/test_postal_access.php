<?php
session_start();
require '../config/db.php';
require 'user_info_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_info = get_user_info($user_id, $conn);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Postal Officer Access Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Postal Officer Access Test</h2>
    
    <div class="card mt-3">
        <div class="card-header bg-primary text-white">
            <h5>Current User Information</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <th>User ID:</th>
                    <td><?php echo $user_id; ?></td>
                </tr>
                <tr>
                    <th>Role (Raw):</th>
                    <td><?php echo htmlspecialchars($user_info['role']); ?></td>
                </tr>
                <tr>
                    <th>Role (Display):</th>
                    <td><?php echo htmlspecialchars($user_info['role_display']); ?></td>
                </tr>
                <tr>
                    <th>Employee Name:</th>
                    <td><?php echo htmlspecialchars($user_info['employee_name']); ?></td>
                </tr>
                <tr>
                    <th>Employee ID:</th>
                    <td><?php echo $user_info['employee_id']; ?></td>
                </tr>
                <tr>
                    <th>Department Name:</th>
                    <td><?php echo htmlspecialchars($user_info['department_name']); ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header bg-success text-white">
            <h5>Access Permissions Test</h5>
        </div>
        <div class="card-body">
            <?php 
            $role = $user_info['role'];
            $can_create_letters = in_array($role, ['department_head', 'subject_officer', 'institution_head', 'postal_subject_officer']);
            ?>
            <p><strong>Can Create Letters:</strong> 
                <?php if ($can_create_letters): ?>
                    <span class="badge bg-success">YES ✓</span>
                <?php else: ?>
                    <span class="badge bg-danger">NO ✗</span>
                <?php endif; ?>
            </p>
            
            <p><strong>Role Match Test:</strong></p>
            <ul>
                <li>institution_head: <?php echo $role === 'institution_head' ? '✓' : '✗'; ?></li>
                <li>postal_subject_officer: <?php echo $role === 'postal_subject_officer' ? '✓' : '✗'; ?></li>
                <li>department_head: <?php echo $role === 'department_head' ? '✓' : '✗'; ?></li>
                <li>subject_officer: <?php echo $role === 'subject_officer' ? '✓' : '✗'; ?></li>
            </ul>
            
            <?php if ($can_create_letters): ?>
                <a href="letter_form.php" class="btn btn-primary mt-3">Go to Letter Form</a>
            <?php else: ?>
                <div class="alert alert-danger mt-3">
                    Your role (<?php echo htmlspecialchars($role); ?>) does not have permission to create letters.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            <h5>Database Role Check</h5>
        </div>
        <div class="card-body">
            <?php
            // Check the actual role in database
            $stmt = $conn->prepare("SELECT role FROM Users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            ?>
            <p><strong>Raw Role from Database:</strong> <?php echo htmlspecialchars($row['role']); ?></p>
            <p><strong>Role Normalization Check:</strong></p>
            <pre><?php 
            echo "Original: " . $row['role'] . "\n";
            echo "Lowercase: " . strtolower($row['role']) . "\n";
            echo "Replace spaces: " . str_replace(' ', '_', $row['role']) . "\n";
            echo "Final: " . strtolower(str_replace(' ', '_', $row['role']));
            ?></pre>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>
