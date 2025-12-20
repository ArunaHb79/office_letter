<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'user_info_helper.php';
require_once 'activity_logger.php';

// Get user information
$user_id = intval($_SESSION['user_id']);
$user_info = get_user_info($user_id, $conn);

if (!$user_info) {
    header('Location: index.php');
    exit;
}

$role = $user_info['role'];
$emp_id = $user_info['employee_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: letters.php?error=invalid_id');
    exit();
}

$letter_id = intval($_GET['id']);

// Check if letter exists and get its details
$checkStmt = $conn->prepare("SELECT l.*, e.department_id FROM Letter l JOIN Employee e ON l.employee_id = e.id WHERE l.id = ?");
$checkStmt->bind_param('i', $letter_id);
$checkStmt->execute();
$letterResult = $checkStmt->get_result();

if ($letterResult->num_rows === 0) {
    header('Location: letters.php?error=letter_not_found');
    exit();
}

$letter = $letterResult->fetch_assoc();

// Role-based access control for deletion
$can_delete = false;

if ($role === 'institution_head') {
    // Institution head can delete any letter
    $can_delete = true;
} elseif ($role === 'department_head') {
    // Department head can delete letters in their department
    $userDeptStmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ?");
    $userDeptStmt->bind_param('i', $emp_id);
    $userDeptStmt->execute();
    $userDeptResult = $userDeptStmt->get_result();
    
    if ($userDeptResult->num_rows > 0) {
        $userDept = $userDeptResult->fetch_assoc();
        $can_delete = ($letter['department_id'] == $userDept['department_id']);
    }
} elseif ($role === 'subject_officer') {
    // Subject officer can only delete their own letters
    $can_delete = ($letter['employee_id'] == $emp_id);
}

if (!$can_delete) {
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h5>Access Denied</h5>';
    echo '<p>You do not have permission to delete this letter.</p>';
    echo '<ul>';
    if ($role === 'subject_officer') {
        echo '<li>Subject officers can only delete letters assigned to themselves</li>';
    } elseif ($role === 'department_head') {
        echo '<li>Department heads can only delete letters within their department</li>';
    }
    echo '</ul>';
    echo '</div>';
    echo '<a href="letters.php" class="btn btn-secondary">Back to Letters</a>';
    echo '</div>';
    exit();
}

// Log the deletion with full letter context
$logger = new ActivityLogger($conn, $user_id);
$logger->logLetterDeleted($letter_id, [
    'letter_number' => $letter['letter_number'],
    'subject' => $letter['subject'],
    'sender' => $letter['sender'],
    'receiver' => $letter['receiver'],
    'department_id' => $letter['department_id'],
    'deleted_by_role' => $role
]);

// Proceed with deletion
$deleteStmt = $conn->prepare("DELETE FROM Letter WHERE id = ?");
$deleteStmt->bind_param('i', $letter_id);

if ($deleteStmt->execute()) {
    header('Location: letters.php?success=Letter deleted successfully');
    exit();
} else {
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">Error deleting letter. Please try again.</div>';
    echo '<a href="letters.php" class="btn btn-secondary mt-3">Back to Letters</a>';
    echo '</div>';
    exit();
}
?>