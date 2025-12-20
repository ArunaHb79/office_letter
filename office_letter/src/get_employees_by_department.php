<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'user_info_helper.php';

// Get user information
$user_id = intval($_SESSION['user_id']);
$user_info = get_user_info($user_id, $conn);

if (!$user_info) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$role = $user_info['role'];
$emp_id = $user_info['employee_id'];

if (isset($_GET['department_id'])) {
    $department_id = intval($_GET['department_id']);
    
    // Get user's department for restrictions
    $user_dept_id = null;
    if ($emp_id) {
        $deptStmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ? LIMIT 1");
        $deptStmt->bind_param('i', $emp_id);
        $deptStmt->execute();
        $deptRes = $deptStmt->get_result();
        if ($deptRow = $deptRes->fetch_assoc()) {
            $user_dept_id = $deptRow['department_id'];
        }
    }
    
    // Build query based on role
    if ($role === 'institution_head') {
        // Institution head can see employees from selected department
        $sql = "SELECT e.id, e.name, d.name as dept_name 
                FROM Employee e 
                LEFT JOIN Department d ON e.department_id = d.id 
                WHERE e.department_id = ? 
                ORDER BY e.name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $department_id);
    } elseif ($role === 'department_head') {
        // Department head can only see employees from their department
        if ($department_id == $user_dept_id) {
            $sql = "SELECT e.id, e.name, d.name as dept_name 
                    FROM Employee e 
                    LEFT JOIN Department d ON e.department_id = d.id 
                    WHERE e.department_id = ? 
                    ORDER BY e.name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $department_id);
        } else {
            echo json_encode(['error' => 'Access denied to other departments']);
            exit;
        }
    } else {
        // Subject officers can only assign to themselves
        $sql = "SELECT e.id, e.name, d.name as dept_name 
                FROM Employee e 
                LEFT JOIN Department d ON e.department_id = d.id 
                WHERE e.id = ? 
                ORDER BY e.name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $emp_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'dept_name' => $row['dept_name'] ?? ''
        ];
    }
    
    echo json_encode(['employees' => $employees]);
} else {
    echo json_encode(['error' => 'No department_id provided']);
}
?>
