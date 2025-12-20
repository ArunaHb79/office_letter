<?php
require_once 'session_config.php';
require_once 'session_helper.php';
start_secure_session();
validate_session();

// Always set role from Users table
require_once '../config/db.php';
$user_id = intval($_SESSION['user_id']);
$roleStmt = $conn->prepare("SELECT role FROM Users WHERE id = ? LIMIT 1");
$roleStmt->bind_param('i', $user_id);
$roleStmt->execute();
$roleRes = $roleStmt->get_result();
if ($roleRes && $roleRes->num_rows > 0) {
    $_SESSION['role'] = strtolower(str_replace(' ', '_', $roleRes->fetch_assoc()['role']));
}
?>