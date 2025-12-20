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

// Get attachment ID
$attachment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$attachment_id) {
    header('HTTP/1.0 400 Bad Request');
    die('Invalid attachment ID');
}

// Fetch attachment with letter information
$sql = "SELECT la.*, l.id as letter_id, l.department_id, l.employee_id as letter_employee_id
        FROM LetterAttachments la
        JOIN Letter l ON la.letter_id = l.id
        WHERE la.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $attachment_id);
$stmt->execute();
$result = $stmt->get_result();
$attachment = $result->fetch_assoc();

if (!$attachment) {
    header('HTTP/1.0 404 Not Found');
    die('Attachment not found');
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

if ($role === 'institution_head' || $role === 'postal_subject_officer') {
    // Institution head and postal officer can view all attachments
    $can_view = true;
} elseif ($role === 'chief_management_assistant') {
    // Chief Management Assistant can view all attachments
    $can_view = true;
} elseif ($role === 'department_head') {
    // Department head can view department attachments
    $can_view = ($attachment['department_id'] == $user_dept_id);
} elseif ($role === 'subject_officer') {
    // Subject officer can view attachments of letters assigned to them
    $can_view = ($attachment['letter_employee_id'] == $emp_id);
} else {
    $can_view = false;
}

if (!$can_view) {
    header('HTTP/1.0 403 Forbidden');
    die('You do not have permission to view this attachment');
}

// Build file path
$file_path = '../uploads/letters/' . $attachment['file_path'];

if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    die('File not found on server: ' . htmlspecialchars($attachment['file_path']));
}

// Get file info
$file_extension = strtolower($attachment['file_type']);
$file_size = filesize($file_path);

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Check if download is requested
$download = isset($_GET['download']) && $_GET['download'] == '1';

// Log attachment access
$logger = new ActivityLogger($conn, $user_id);
$logger->log($download ? 'attachment_downloaded' : 'attachment_viewed', $attachment['letter_id'], null, [
    'attachment_id' => $attachment_id,
    'filename' => $attachment['file_name'],
    'label' => $attachment['attachment_label'],
    'file_size' => $file_size,
    'file_type' => $file_extension
]);

// Set headers
header('Content-Type: ' . $content_type);
header('Content-Length: ' . $file_size);
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

if ($download) {
    // Force download
    header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
} else {
    // Display inline (in browser)
    header('Content-Disposition: inline; filename="' . $attachment['file_name'] . '"');
}

// Output file
readfile($file_path);
exit;
