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
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}

$role = $user_info['role'];
$emp_id = $user_info['employee_id'];

// Get letter ID
$letter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$letter_id) {
    header('HTTP/1.0 400 Bad Request');
    die('Invalid letter ID');
}

// Fetch letter with attachment
$sql = "SELECT l.*, e.department_id, l.attachment_filename, l.attachment_path
        FROM Letter l
        LEFT JOIN Employee e ON l.employee_id = e.id
        WHERE l.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $letter_id);
$stmt->execute();
$result = $stmt->get_result();
$letter = $result->fetch_assoc();

if (!$letter) {
    header('HTTP/1.0 404 Not Found');
    die('Letter not found');
}

// Check if letter has attachment
if (empty($letter['attachment_path'])) {
    header('HTTP/1.0 404 Not Found');
    die('No attachment found');
}

// Role-based access control
$can_view = false;

// Get user's department
$user_dept_id = null;
if ($emp_id) {
    $dept_stmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ? LIMIT 1");
    $dept_stmt->bind_param('i', $emp_id);
    $dept_stmt->execute();
    $dept_res = $dept_stmt->get_result();
    if ($dept_row = $dept_res->fetch_assoc()) {
        $user_dept_id = $dept_row['department_id'];
    }
}

if ($role === 'institution_head') {
    // Institution head can view all attachments
    $can_view = true;
} elseif ($role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
    // Postal Subject Officer and Chief Management Assistant can view all attachments
    $can_view = true;
} elseif ($role === 'department_head') {
    // Department head can view department attachments
    $can_view = ($letter['department_id'] == $user_dept_id);
} elseif ($role === 'subject_officer') {
    // Subject officer can view own attachments
    $can_view = ($letter['employee_id'] == $emp_id);
} else {
    $can_view = false;
}

if (!$can_view) {
    header('HTTP/1.0 403 Forbidden');
    die('You do not have permission to view this attachment');
}

// Build file path
// Handle both cases: filename only OR full path already stored
$attachment_path = $letter['attachment_path'];
if (strpos($attachment_path, '../uploads/letters/') === 0) {
    // Full path already stored in database
    $file_path = $attachment_path;
} else {
    // Only filename stored, build full path
    $file_path = '../uploads/letters/' . $attachment_path;
}

if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    die('File not found on server');
}

// Get file info
$file_extension = strtolower(pathinfo($letter['attachment_filename'], PATHINFO_EXTENSION));
$file_size = filesize($file_path);

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Check if download is requested
$download = isset($_GET['download']) && $_GET['download'] == '1';

// Log attachment access
$logger = new ActivityLogger($conn, $user_id);
$logger->log($download ? 'attachment_downloaded' : 'attachment_viewed', $letter_id, null, [
    'filename' => $letter['attachment_filename'],
    'file_size' => $file_size,
    'file_type' => $file_extension,
    'letter_number' => $letter['letter_number'] ?? 'N/A'
]);

// Set headers
header('Content-Type: ' . $content_type);
header('Content-Length: ' . $file_size);
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

if ($download) {
    // Force download
    header('Content-Disposition: attachment; filename="' . $letter['attachment_filename'] . '"');
} else {
    // Display inline (in browser)
    header('Content-Disposition: inline; filename="' . $letter['attachment_filename'] . '"');
}

// Output file
readfile($file_path);
exit;
