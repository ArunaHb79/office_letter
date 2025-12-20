<?php
require_once '../config/db.php';
require_once 'auth.php';  // This will start the session and set the role

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get user role from session (set by auth.php)
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Debug log
error_log("get_letter_number.php - Role from session: '$role'");

if (isset($_GET['department_id'])) {
    $department_id = intval($_GET['department_id']);
    $letter_id = isset($_GET['letter_id']) ? intval($_GET['letter_id']) : 0;
    
    $year = date('y'); // last two digits of year
    $month = date('m'); // two digit month
    
    if ($letter_id > 0) {
        // Editing mode: use letter ID for the number
        if ($role === 'chief_management_assistant') {
            // Chief Management Assistant: YYMM-ID format (no department abbreviation)
            $result = $year . $month . '-' . $letter_id;
        } else {
            // Other roles: DEPT-YYMM-ID format
            $abbr = '';
            $deptStmt = $conn->prepare("SELECT abbreviation FROM Department WHERE id = ? LIMIT 1");
            $deptStmt->bind_param('i', $department_id);
            $deptStmt->execute();
            $deptResult = $deptStmt->get_result();
            if ($deptRow = $deptResult->fetch_assoc()) {
                $abbr = $deptRow['abbreviation'];
            }
            $result = $abbr ? $abbr . '-' . $year . $month . '-' . $letter_id : '';
        }
    } else {
        // New letter mode: use next auto increment ID
        $idResult = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Letter'");
        $nextId = ($idRow = $idResult->fetch_assoc()) ? $idRow['AUTO_INCREMENT'] : rand(1000,9999);
        
        if ($role === 'chief_management_assistant') {
            // Chief Management Assistant: YYMM-ID format (no department abbreviation)
            $result = $year . $month . '-' . $nextId;
            error_log("CMA - Generating: $result");
        } else {
            // Other roles: DEPT-YYMM-ID format
            $abbr = '';
            $deptStmt = $conn->prepare("SELECT abbreviation FROM Department WHERE id = ? LIMIT 1");
            $deptStmt->bind_param('i', $department_id);
            $deptStmt->execute();
            $deptResult = $deptStmt->get_result();
            if ($deptRow = $deptResult->fetch_assoc()) {
                $abbr = $deptRow['abbreviation'];
            }
            $result = $abbr && $nextId ? $abbr . '-' . $year . $month . '-' . $nextId : '';
            error_log("Other role ($role) - Generating: $result");
        }
    }
    
    echo $result;
} else {
    echo 'ERROR: No department_id provided';
}
?>