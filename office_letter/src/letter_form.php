<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'csrf_helper.php';
require_once 'user_info_helper.php';
require_once 'activity_logger.php';
require_once 'footer_component.php';

// Get user information
$user_id = intval($_SESSION['user_id']);
$user_info = get_user_info($user_id, $conn);

if (!$user_info) {
    header('Location: index.php');
    exit;
}

$role = $user_info['role'];
$employee_id = $user_info['employee_id'];
$user_dept_id = null;

// Get user's department for restrictions (postal officers may not have department)
if ($employee_id) {
    $deptStmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ? LIMIT 1");
    $deptStmt->bind_param('i', $employee_id);
    $deptStmt->execute();
    $deptRes = $deptStmt->get_result();
    if ($deptRow = $deptRes->fetch_assoc()) {
        $user_dept_id = $deptRow['department_id'];
    }
}

// Role-based access control for letter creation/editing
$can_create_letters = in_array($role, ['department_head', 'subject_officer', 'institution_head', 'postal_subject_officer', 'chief_management_assistant']);
$can_assign_letters = in_array($role, ['department_head', 'institution_head', 'postal_subject_officer']); // Chief Management Assistant cannot assign
$can_edit_all_letters = in_array($role, ['institution_head']);
$can_edit_dept_letters = in_array($role, ['department_head']);

if (!$can_create_letters) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing = $id > 0;

if ($editing) {
    // Check if user can edit this specific letter
    $sql = "SELECT l.*, e.department_id as letter_dept_id 
            FROM Letter l 
            LEFT JOIN Employee e ON l.employee_id = e.id 
            WHERE l.id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $letter = $result->fetch_assoc();
    
    if (!$letter) {
        header('Location: letters.php?error=letter_not_found');
        exit;
    }
    
    // Access control for editing
    $can_edit_this_letter = false;
    
    if ($can_edit_all_letters) {
        $can_edit_this_letter = true; // Institution head can edit any letter
    } elseif ($role === 'postal_subject_officer') {
        $can_edit_this_letter = true; // Postal officer can edit all letters (for institution head to assign)
    } elseif ($can_edit_dept_letters && $letter['letter_dept_id'] == $user_dept_id) {
        $can_edit_this_letter = true; // Department head can edit dept letters
    } elseif ($role === 'chief_management_assistant' && $employee_id && $letter['employee_id'] == $employee_id) {
        $can_edit_this_letter = true; // Chief Management Assistant can edit letters assigned to them
    } elseif ($employee_id && $letter['employee_id'] == $employee_id) {
        $can_edit_this_letter = true; // Users can edit their own letters
    }
    
    if (!$can_edit_this_letter) {
        header('Location: letters.php?error=edit_access_denied');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    
    // Collect and validate form data
    $subject = trim($_POST['subject']);
    $sender = trim($_POST['sender']);
    $receiver = isset($_POST['receiver']) ? trim($_POST['receiver']) : null;
    $method_id = intval($_POST['method_id']);
    $date_received = $_POST['date_received'];
    $date_sent = $_POST['date_sent'];
    $department_id = intval($_POST['department_id']);
    $form_employee_id = intval($_POST['employee_id']);
    $status_id = intval($_POST['status_id']);
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Chief Management Assistant creates letters without department - it will be set during assignment
    if ($role === 'chief_management_assistant' && !$editing) {
        $department_id = null;
    }
    
    // NEW: Letter direction and recipient information
    $letter_direction = $_POST['letter_direction'] ?? 'incoming';
    $recipient_organization = isset($_POST['recipient_organization']) ? trim($_POST['recipient_organization']) : null;
    $recipient_person = isset($_POST['recipient_person']) ? trim($_POST['recipient_person']) : null;
    $reference_letter_number = isset($_POST['reference_letter_number']) ? trim($_POST['reference_letter_number']) : null;
    
    // For postal subject officer creating new letters, employee_id should be NULL
    if ($role === 'postal_subject_officer' && !$editing && $form_employee_id == 0) {
        $form_employee_id = null;
    }
    
    // Chief Management Assistant CANNOT assign letters - force employee_id to NULL for new letters
    if ($role === 'chief_management_assistant' && !$editing) {
        $form_employee_id = null; // Always NULL for new letters
    }
    
    // When editing, Chief Management Assistant can only update if letter is assigned to them
    // They cannot change the assignment
    if ($role === 'chief_management_assistant' && $editing) {
        // Keep the original employee assignment - they cannot change it
        $form_employee_id = $letter['employee_id'];
    }
    
    // Auto-set status to "Assigned" when a letter is assigned to an employee
    if ($form_employee_id !== null && $form_employee_id > 0) {
        // Get "Assigned" status ID
        $assignedStatusQuery = $conn->query("SELECT id FROM LetterStatus WHERE status_name = 'Assigned' LIMIT 1");
        if ($assignedStatusRow = $assignedStatusQuery->fetch_assoc()) {
            $status_id = $assignedStatusRow['id'];
        }
    }
    
    // File upload handling
    $attachment_filename = null;
    $attachment_path = null;
    $file_upload_error = null;
    $remove_attachment = isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == '1';
    
    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        $file_type = $_FILES['attachment']['type'];
        $file_size = $_FILES['attachment']['size'];
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $original_filename = $_FILES['attachment']['name'];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            $file_upload_error = 'Only JPG, PNG, GIF, and PDF files are allowed.';
        }
        // Validate file size
        elseif ($file_size > $max_size) {
            $file_upload_error = 'File size must not exceed 10MB.';
        }
        // Process upload
        else {
            // Create unique filename
            $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
            $unique_filename = uniqid('letter_', true) . '_' . time() . '.' . $file_extension;
            $upload_dir = '../uploads/letters/';
            $upload_path = $upload_dir . $unique_filename;
            
            // Create directory if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $attachment_filename = $original_filename;
                $attachment_path = $unique_filename; // Store only filename, not full path
            } else {
                $file_upload_error = 'Failed to upload file. Please try again.';
            }
        }
    } elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $file_upload_error = $upload_errors[$_FILES['attachment']['error']] ?? 'Unknown upload error';
    }
    
    // Role-based validation
    $validation_errors = [];
    
    // Add file upload error if exists
    if ($file_upload_error) {
        $validation_errors[] = $file_upload_error;
    }
    
    // Department restrictions
    if ($role === 'postal_subject_officer') {
        // Postal subject officer can create letters for any department
        // No department or employee restrictions
    } elseif ($role === 'chief_management_assistant') {
        // Chief Management Assistant can create letters for any department
        // But CANNOT assign them - assignment must be done by Institution Head
        // When creating new letters, employee_id is forced to NULL
        // When editing, can only edit letters assigned to them and cannot change assignment
    } elseif ($role === 'subject_officer') {
        // Subject officers can only create letters for their own department
        if ($department_id != $user_dept_id) {
            $validation_errors[] = 'You can only create letters for your department.';
        }
        // Subject officers can only assign letters to themselves
        if ($form_employee_id != $employee_id) {
            $validation_errors[] = 'You can only assign letters to yourself.';
        }
    } elseif ($role === 'department_head') {
        // Department heads can create letters for their department
        if ($department_id != $user_dept_id) {
            $validation_errors[] = 'You can only create letters for your department.';
        }
        // Verify the selected employee belongs to the department
        if ($form_employee_id) {
            $empCheckStmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ? LIMIT 1");
            $empCheckStmt->bind_param('i', $form_employee_id);
            $empCheckStmt->execute();
            $empCheckRes = $empCheckStmt->get_result();
            if ($empCheckRow = $empCheckRes->fetch_assoc()) {
                if ($empCheckRow['department_id'] != $user_dept_id) {
                    $validation_errors[] = 'Selected employee does not belong to your department.';
                }
            }
        }
    }
    // Institution head and postal subject officer have no restrictions
    
    // Basic validation
    if (empty($subject)) $validation_errors[] = 'Subject is required.';
    if (empty($date_received)) $validation_errors[] = 'Date received is required.';
    
    if (!empty($validation_errors)) {
        $error = implode('<br>', $validation_errors);
    } else {

    // Generate letter number based on department
    if (!$editing) {
        // New letter: generate new number
        $idResult = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Letter'");
        $nextId = ($idRow = $idResult->fetch_assoc()) ? $idRow['AUTO_INCREMENT'] : rand(1000,9999);
        $year = date('y');
        $month = date('m');
        
        // For Chief Management Assistant: Create letter number without department abbreviation (YYMM-ID)
        // For others: Include department abbreviation (DEPT-YYMM-ID)
        if ($role === 'chief_management_assistant') {
            $letter_number = $year . $month . '-' . $nextId;
        } else {
            $deptStmt = $conn->prepare("SELECT abbreviation FROM Department WHERE id = ? LIMIT 1");
            $deptStmt->bind_param('i', $department_id);
            $deptStmt->execute();
            $deptResult = $deptStmt->get_result();
            $abbr = '';
            if ($deptRow = $deptResult->fetch_assoc()) {
                $abbr = $deptRow['abbreviation'];
            }
            $letter_number = $abbr && $nextId ? $abbr . '-' . $year . $month . '-' . $nextId : '';
        }
        
        if (empty($letter_number)) {
            $error = 'Letter number could not be generated. Please check department selection.';
        }
    } else {
        // Editing: Check if department changed, regenerate number if needed
        $old_dept_stmt = $conn->prepare("SELECT department_id, letter_number FROM Letter WHERE id = ? LIMIT 1");
        $old_dept_stmt->bind_param('i', $id);
        $old_dept_stmt->execute();
        $old_dept_result = $old_dept_stmt->get_result();
        $old_dept_row = $old_dept_result->fetch_assoc();
        
        // Debug output
        error_log("OLD DEPT ID: " . $old_dept_row['department_id'] . " | NEW DEPT ID: " . $department_id);
        
        if ($old_dept_row && intval($old_dept_row['department_id']) != intval($department_id)) {
            // Department changed - regenerate letter number with new department abbreviation
            error_log("DEPARTMENT CHANGED - Regenerating letter number");
            $deptStmt = $conn->prepare("SELECT abbreviation FROM Department WHERE id = ? LIMIT 1");
            $deptStmt->bind_param('i', $department_id);
            $deptStmt->execute();
            $deptResult = $deptStmt->get_result();
            $abbr = '';
            if ($deptRow = $deptResult->fetch_assoc()) {
                $abbr = $deptRow['abbreviation'];
            }
            // Use letter ID for the sequential number part
            $year = date('y');
            $month = date('m');
            $letter_number = $abbr . '-' . $year . $month . '-' . $id;
            error_log("NEW LETTER NUMBER: " . $letter_number);
        } else {
            // Department not changed - keep original letter number
            error_log("DEPARTMENT NOT CHANGED - Keeping original");
            $letter_number = $old_dept_row['letter_number'];
        }
    }

    if (!isset($error)) {
        // Initialize activity logger
        $logger = new ActivityLogger($conn, $user_id);
        
        if ($editing) {
            // Get old values before update for logging
            $old_stmt = $conn->prepare("SELECT * FROM Letter WHERE id = ?");
            $old_stmt->bind_param('i', $id);
            $old_stmt->execute();
            $old_letter = $old_stmt->get_result()->fetch_assoc();
            
            // Handle attachment removal
            if ($remove_attachment && !empty($old_letter['attachment_path'])) {
                $old_file_path = '../uploads/letters/' . $old_letter['attachment_path'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
                $attachment_filename = null;
                $attachment_path = null;
            } elseif (!$attachment_path && !$remove_attachment) {
                // Keep existing attachment if no new file uploaded
                $attachment_filename = $old_letter['attachment_filename'];
                $attachment_path = $old_letter['attachment_path'];
            } elseif ($attachment_path && !empty($old_letter['attachment_path'])) {
                // Delete old file when new file is uploaded
                $old_file_path = '../uploads/letters/' . $old_letter['attachment_path'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            
            $sql = "UPDATE Letter SET subject=?, attachment_filename=?, attachment_path=?, sender=?, receiver=?, method_id=?, date_received=?, date_sent=?, department_id=?, employee_id=?, letter_number=?, status_id=?, notes=?, letter_direction=?, recipient_organization=?, recipient_person=?, reference_letter_number=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssissiiisssssi', $subject, $attachment_filename, $attachment_path, $sender, $receiver, $method_id, $date_received, $date_sent, $department_id, $form_employee_id, $letter_number, $status_id, $notes, $letter_direction, $recipient_organization, $recipient_person, $reference_letter_number, $id);
            $stmt->execute();
            
            // Log the letter update with comprehensive field tracking
            if ($stmt->affected_rows > 0) {
                $new_values = [
                    'subject' => $subject,
                    'sender' => $sender,
                    'receiver' => $receiver,
                    'letter_number' => $letter_number,
                    'department_id' => $department_id,
                    'employee_id' => $form_employee_id,
                    'status_id' => $status_id,
                    'method_id' => $method_id,
                    'date_received' => $date_received,
                    'date_sent' => $date_sent,
                    'attachment' => $attachment_filename ?? 'none',
                    'notes' => $notes ?? ''
                ];
                $old_values = [
                    'subject' => $old_letter['subject'],
                    'sender' => $old_letter['sender'],
                    'receiver' => $old_letter['receiver'],
                    'letter_number' => $old_letter['letter_number'],
                    'department_id' => $old_letter['department_id'],
                    'employee_id' => $old_letter['employee_id'],
                    'status_id' => $old_letter['status_id'],
                    'method_id' => $old_letter['method_id'],
                    'date_received' => $old_letter['date_received'],
                    'date_sent' => $old_letter['date_sent'],
                    'attachment' => $old_letter['attachment_filename'] ?? 'none',
                    'notes' => $old_letter['notes'] ?? ''
                ];
                
                // Track specific changes for detailed logging
                $changes = [];
                foreach ($new_values as $key => $value) {
                    if ($old_values[$key] != $value) {
                        $changes[$key] = ['old' => $old_values[$key], 'new' => $value];
                    }
                }
                
                // Log with changes summary
                $logger->log('letter_updated', $id, $old_values, array_merge($new_values, ['changes_count' => count($changes), 'changed_fields' => array_keys($changes)]));
                
                // Log specific change types
                if (isset($changes['department_id'])) {
                    $logger->logDepartmentChanged($form_employee_id, $old_letter['department_id'], $department_id);
                }
                if (isset($changes['employee_id'])) {
                    $logger->logLetterAssigned($id, $old_letter['employee_id'], $form_employee_id);
                }
                if (isset($changes['status_id'])) {
                    $logger->logStatusChanged($id, $old_letter['status_id'], $status_id);
                }
                if (isset($changes['attachment'])) {
                    if ($remove_attachment) {
                        $logger->log('attachment_removed', $id, ['filename' => $old_letter['attachment_filename']], null);
                    } elseif ($attachment_filename && $old_letter['attachment_filename']) {
                        $logger->log('attachment_replaced', $id, ['old_file' => $old_letter['attachment_filename']], ['new_file' => $attachment_filename]);
                    } elseif ($attachment_filename) {
                        $logger->log('attachment_added', $id, null, ['filename' => $attachment_filename]);
                    }
                }
            }
        } else {
            // Insert letter with placeholder letter number first
            $sql = "INSERT INTO Letter (subject, attachment_filename, attachment_path, sender, receiver, method_id, date_received, date_sent, department_id, employee_id, letter_number, status_id, notes, letter_direction, recipient_organization, recipient_person, reference_letter_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssissiisisssss', $subject, $attachment_filename, $attachment_path, $sender, $receiver, $method_id, $date_received, $date_sent, $department_id, $form_employee_id, $letter_number, $status_id, $notes, $letter_direction, $recipient_organization, $recipient_person, $reference_letter_number);
            $stmt->execute();
            
            // After insert, update with correct letter number using actual ID
            if ($stmt->affected_rows > 0) {
                $new_letter_id = $conn->insert_id;
                
                // Generate correct letter number with actual letter ID
                $year = date('y');
                $month = date('m');
                
                // For Chief Management Assistant: Generate YYMM-ID format (no department abbreviation)
                if ($role === 'chief_management_assistant') {
                    $correct_letter_number = $year . $month . '-' . $new_letter_id;
                } else {
                    // For other roles: Generate DEPT-YYMM-ID format
                    $deptStmt = $conn->prepare("SELECT abbreviation FROM Department WHERE id = ? LIMIT 1");
                    $deptStmt->bind_param('i', $department_id);
                    $deptStmt->execute();
                    $deptResult = $deptStmt->get_result();
                    $abbr = '';
                    if ($deptRow = $deptResult->fetch_assoc()) {
                        $abbr = $deptRow['abbreviation'];
                    }
                    $correct_letter_number = $abbr . '-' . $year . $month . '-' . $new_letter_id;
                }
                
                // Update letter with correct number
                $updateStmt = $conn->prepare("UPDATE Letter SET letter_number = ? WHERE id = ?");
                $updateStmt->bind_param('si', $correct_letter_number, $new_letter_id);
                $updateStmt->execute();
                
                // Log the letter creation
                $logger->logLetterCreated($new_letter_id, [
                    'letter_number' => $correct_letter_number,
                    'subject' => $subject,
                    'sender' => $sender,
                    'receiver' => $receiver,
                    'department_id' => $department_id,
                    'employee_id' => $form_employee_id,
                    'status_id' => $status_id,
                    'method_id' => $method_id,
                    'date_received' => $date_received,
                    'has_attachment' => $attachment_filename ? 'yes' : 'no',
                    'attachment_filename' => $attachment_filename ?? 'none',
                    'notes' => $notes ?? ''
                ]);
            }
        }
        header('Location: letters.php');
        exit;
    }
    } // Close validation else block
}

// Fetch dropdown data based on role restrictions
$methods = $conn->query("SELECT * FROM LetterMethod");
$statuses = $conn->query("SELECT * FROM LetterStatus");

// Department restrictions
if ($role === 'institution_head' || $role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
    // Institution head and postal subject officer can see all departments
    $departments_result = $conn->query("SELECT * FROM Department ORDER BY name");
} else {
    // Others can only see their own department
    $departments_result = $conn->prepare("SELECT * FROM Department WHERE id = ? ORDER BY name");
    $departments_result->bind_param('i', $user_dept_id);
    $departments_result->execute();
    $departments_result = $departments_result->get_result();
}

$departments = array();
while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row;
}

// Employee restrictions
if ($role === 'institution_head') {
    // Institution head can assign to any employee
    $employees = $conn->query("SELECT e.*, d.name as dept_name FROM Employee e LEFT JOIN Department d ON e.department_id = d.id ORDER BY e.name");
} elseif ($role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
    // Postal subject officer: create placeholder - Institution Head will assign
    // Return empty result to show select box but disabled
    $employees = $conn->query("SELECT e.*, d.name as dept_name FROM Employee e LEFT JOIN Department d ON e.department_id = d.id ORDER BY e.name");
} elseif ($role === 'department_head') {
    // Department head can assign to employees in their department
    $employeesStmt = $conn->prepare("SELECT e.*, d.name as dept_name FROM Employee e LEFT JOIN Department d ON e.department_id = d.id WHERE e.department_id = ? ORDER BY e.name");
    $employeesStmt->bind_param('i', $user_dept_id);
    $employeesStmt->execute();
    $employees = $employeesStmt->get_result();
} else {
    // Subject officers can only assign to themselves
    $employeesStmt = $conn->prepare("SELECT e.*, d.name as dept_name FROM Employee e LEFT JOIN Department d ON e.department_id = d.id WHERE e.id = ? ORDER BY e.name");
    $employeesStmt->bind_param('i', $employee_id);
    $employeesStmt->execute();
    $employees = $employeesStmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $editing ? 'Edit Letter' : 'Add Letter'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php echo get_user_info_styles(); ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .form-container {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 2.5rem;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .form-section h5 {
            color: #5569d8;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 700;
            margin-bottom: 8px;
            color: #1a1a1a;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 0.6rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
<div class="container-fluid px-4" style="max-width: 1400px;">
    <div class="form-container">
    <!-- User Information -->
    <?php echo display_user_info_compact($user_info); ?>
    
    <h2 class="mb-4"><i class="fas fa-<?php echo $editing ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $editing ? 'Edit Letter' : 'Add New Letter'; ?></h2>
    
    <!-- Role-based restrictions notice -->
    <?php if ($role === 'postal_subject_officer'): ?>
        <div class="alert alert-primary">
            <strong>Postal Subject Officer Permissions:</strong> You can create letters for any department. Institution Head will assign them to employees.
        </div>
    <?php elseif ($role === 'chief_management_assistant'): ?>
        <div class="alert alert-warning">
            <strong>Chief Management Assistant Permissions:</strong> You can add received letters for all departments, but you cannot assign them to employees. Only Institution Head can assign letters. You can update letters that are assigned to you.
        </div>
    <?php elseif ($role === 'subject_officer'): ?>
        <div class="alert alert-info">
            <strong>Subject Officer Permissions:</strong> You can create letters for your department and assign them to yourself.
        </div>
    <?php elseif ($role === 'department_head'): ?>
        <div class="alert alert-info">
            <strong>Department Head Permissions:</strong> You can create letters for your department and assign them to any employee in your department.
        </div>
    <?php elseif ($role === 'institution_head'): ?>
        <div class="alert alert-success">
            <strong>Institution Head Permissions:</strong> You have full access to create and assign letters across all departments.
        </div>
    <?php endif; ?>
    
    <?php if (isset($error) && $error): ?>
        <div class="alert alert-danger">Error: <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($success) && $success): ?>
        <div class="alert alert-success">Success: <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="post" class="row g-3" enctype="multipart/form-data">
        <?php echo csrf_token_field(); ?>
        <?php if ($editing): ?>
            <input type="hidden" id="original_dept_id" value="<?php echo $letter['department_id']; ?>">
            <input type="hidden" id="letter_id_value" value="<?php echo $id; ?>">
        <?php endif; ?>
        
        <!-- Basic Information Section -->
        <div class="col-12">
            <div class="form-section">
                <h5><i class="fas fa-info-circle"></i> Basic Information</h5>
                <div class="row g-3">
        <div class="col-md-6">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" required value="<?php echo $editing ? htmlspecialchars($letter['subject']) : ''; ?>">
        </div>
        <div class="col-md-6">
            <label for="letter_number" class="form-label">Letter Number</label>
            <input type="text" name="letter_number" id="letter_number" class="form-control" required value="<?php 
                if ($editing) {
                    echo htmlspecialchars($letter['letter_number']);
                } else {
                    // Server-side generation for new letter
                    $year = date('y');
                    $month = date('m');
                    $idResult = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Letter'");
                    $nextId = ($idRow = $idResult->fetch_assoc()) ? $idRow['AUTO_INCREMENT'] : rand(1000,9999);
                    
                    // For Chief Management Assistant: Generate YYMM-ID format (no department abbreviation)
                    if ($role === 'chief_management_assistant') {
                        echo $year . $month . '-' . $nextId;
                    } else {
                        // For other roles: Generate DEPT-YYMM-ID format
                        $defaultDeptId = (count($departments) > 0) ? $departments[0]['id'] : 1;
                        $deptId = isset($_POST['department_id']) ? $_POST['department_id'] : $defaultDeptId;
                        $abbr = '';
                        foreach ($departments as $dept) {
                            if ($dept['id'] == $deptId) {
                                $abbr = $dept['abbreviation'];
                                break;
                            }
                        }
                        echo $abbr && $nextId ? $abbr . '-' . $year . $month . '-' . $nextId : '';
                    }
                }
            ?>">
        </div>        </div>
            </div>
        </div>
        
        <!-- Attachment Section -->
        <div class="col-12">
            <div class="form-section">
                <h5><i class="fas fa-paperclip"></i> Attachment</h5>        <div class="col-md-12">
            <label for="attachment" class="form-label">Attachment (Image or PDF)</label>
            <?php if ($editing && !empty($letter['attachment_filename'])): ?>
                <div class="mb-2">
                    <strong>Current file:</strong> 
                    <a href="view_attachment.php?id=<?php echo $letter['id']; ?>" target="_blank" class="text-primary">
                        <i class="fas fa-file"></i> <?php echo htmlspecialchars($letter['attachment_filename']); ?>
                    </a>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="remove_attachment">
                        <label class="form-check-label text-danger" for="remove_attachment">
                            Remove current attachment
                        </label>
                    </div>
                </div>
            <?php endif; ?>
            <input type="file" name="attachment" id="attachment" class="form-control" accept="image/jpeg,image/png,image/gif,application/pdf">
            <small class="text-muted">Allowed: JPG, PNG, GIF, PDF (Max 10MB)</small>
        </div>
            </div>
        </div>
        
        <!-- Sender and Receiver Section -->
        <div class="col-12">
            <div class="form-section">
                <h5><i class="fas fa-exchange-alt"></i> Sender & Receiver</h5>
                <div class="row g-3">
        <div id="sender-container" class="col-md-6">
            <label for="sender" class="form-label">Sender</label>
            <?php 
            // For new letters with outgoing direction, default sender to Assistant Commissioner
            $sender_value = '';
            if ($editing) {
                $sender_value = htmlspecialchars($letter['sender']);
            }
            ?>
            <input type="text" name="sender" id="sender" class="form-control" value="<?php echo $sender_value; ?>">
        </div>
        <?php if ($editing || $role !== 'chief_management_assistant'): ?>
        <div id="receiver-container" class="col-md-6">
            <label for="receiver" class="form-label">Receiver</label>
            <?php 
            // For new letters with incoming direction, default receiver to Assistant Commissioner
            $receiver_value = '';
            if ($editing) {
                $receiver_value = htmlspecialchars($letter['receiver']);
            }
            ?>
            <input type="text" name="receiver" id="receiver" class="form-control" value="<?php echo $receiver_value; ?>">
        </div>
        <?php else: ?>
            <!-- Hidden receiver field for chief management assistant -->
            <input type="hidden" name="receiver" id="receiver" value="">
        <?php endif; ?>
        </div>
            </div>
        </div>
        
        <!-- Letter Direction & Recipients Section (NEW) -->
        <div class="col-12">
            <div class="form-section">
                <h5><i class="fas fa-directions"></i> Letter Direction & Recipients</h5>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="letter_direction" class="form-label">Letter Type (ලිපි වර්ගය)</label>
                        <select name="letter_direction" id="letter_direction" class="form-select" required>
                            <option value="incoming" <?php if ($editing && $letter['letter_direction'] == 'incoming') echo 'selected'; ?>>
                                Incoming (ලැබෙන ලිපිය)
                            </option>
                            <option value="outgoing_institution" <?php if ($editing && $letter['letter_direction'] == 'outgoing_institution') echo 'selected'; ?>>
                                Outgoing - Institution (ආයතනය විසින් යවන ලිපිය)
                            </option>
                            <option value="outgoing_officer" <?php if ($editing && $letter['letter_direction'] == 'outgoing_officer') echo 'selected'; ?>>
                                Outgoing - Officer (නිලධාරී විසින් යවන ලිපිය)
                            </option>
                        </select>
                    </div>
                    
                    <!-- Recipient fields (shown for outgoing letters) -->
                    <div class="col-md-6 recipient-fields" style="display: none;">
                        <label for="recipient_organization" class="form-label">Recipient Organization (ලැබෙන ආයතනය)</label>
                        <input type="text" name="recipient_organization" id="recipient_organization" class="form-control" 
                               value="<?php echo $editing ? htmlspecialchars($letter['recipient_organization'] ?? '') : ''; ?>"
                               placeholder="e.g., Ministry of Education">
                    </div>
                    <div class="col-md-6 recipient-fields" style="display: none;">
                        <label for="recipient_person" class="form-label">Recipient Person (ලැබෙන පුද්ගලයා)</label>
                        <input type="text" name="recipient_person" id="recipient_person" class="form-control" 
                               value="<?php echo $editing ? htmlspecialchars($letter['recipient_person'] ?? '') : ''; ?>"
                               placeholder="e.g., Director General">
                    </div>
                    
                    <!-- Reference letter (for replies) -->
                    <div class="col-md-12 reference-field" style="display: none;">
                        <label for="reference_letter_number" class="form-label">Reference Letter Number (යොමු ලිපි අංකය)</label>
                        <input type="text" name="reference_letter_number" id="reference_letter_number" class="form-control" 
                               value="<?php echo $editing ? htmlspecialchars($letter['reference_letter_number'] ?? '') : ''; ?>"
                               placeholder="e.g., ABC-2512-123 (if this is a reply)">
                        <small class="text-muted">Enter the original letter number if this is a reply</small>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Show/hide recipient fields based on letter direction
        document.getElementById('letter_direction').addEventListener('change', function() {
            const recipientFields = document.querySelectorAll('.recipient-fields');
            const referenceField = document.querySelectorAll('.reference-field');
            const senderField = document.getElementById('sender');
            const receiverField = document.getElementById('receiver');
            const senderContainer = document.getElementById('sender-container');
            const receiverContainer = document.getElementById('receiver-container');
            const defaultAuthority = 'Assistant Commissioner of Local Government';
            const isEditing = <?php echo $editing ? 'true' : 'false'; ?>;
            
            if (this.value.startsWith('outgoing')) {
                recipientFields.forEach(field => field.style.display = 'block');
                referenceField.forEach(field => field.style.display = 'block');
                // Show both sender and receiver fields
                if (receiverContainer) receiverContainer.style.display = 'block';
                if (senderContainer) senderContainer.className = 'col-md-6';
                // For outgoing letters, sender is always Assistant Commissioner (readonly)
                if (senderField) {
                    senderField.value = defaultAuthority;
                    senderField.readOnly = true;
                    senderField.style.backgroundColor = '#e9ecef';
                }
                // Receiver should be editable and blank for new letters
                if (receiverField) {
                    if (!isEditing) receiverField.value = '';
                    receiverField.readOnly = false;
                    receiverField.style.backgroundColor = '';
                }
            } else if (this.value === 'incoming') {
                recipientFields.forEach(field => field.style.display = 'none');
                referenceField.forEach(field => field.style.display = 'none');
                // Show both sender and receiver fields
                if (receiverContainer) receiverContainer.style.display = 'block';
                if (senderContainer) senderContainer.className = 'col-md-6';
                // For incoming letters, receiver is always Assistant Commissioner (readonly)
                if (receiverField) {
                    receiverField.value = defaultAuthority;
                    receiverField.readOnly = true;
                    receiverField.style.backgroundColor = '#e9ecef';
                }
                // Sender should be editable and blank for new letters
                if (senderField) {
                    if (!isEditing) senderField.value = '';
                    senderField.readOnly = false;
                    senderField.style.backgroundColor = '';
                }
            } else {
                recipientFields.forEach(field => field.style.display = 'none');
                referenceField.forEach(field => field.style.display = 'none');
                // Show receiver field for other cases
                if (receiverContainer) receiverContainer.style.display = 'block';
                if (senderContainer) senderContainer.className = 'col-md-6';
                // Make both fields editable for other cases
                if (senderField) {
                    senderField.readOnly = false;
                    senderField.style.backgroundColor = '';
                }
                if (receiverField) {
                    receiverField.readOnly = false;
                    receiverField.style.backgroundColor = '';
                }
            }
        });
        
        // Trigger on page load for edit mode
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('letter_direction').dispatchEvent(new Event('change'));
        });
        </script>
        
        <!-- Letter Details Section -->
        <div class="col-12">
            <div class="form-section">
                <h5><i class="fas fa-list-ul"></i> Letter Details</h5>
                <div class="row g-3">
        <div class="col-md-4">
            <label for="method_id" class="form-label">Method</label>
            <select name="method_id" id="method_id" class="form-select" required>
                <?php while ($m = $methods->fetch_assoc()) { ?>
                    <option value="<?php echo $m['id']; ?>" <?php if ($editing && $letter['method_id'] == $m['id']) echo 'selected'; ?>><?php echo htmlspecialchars($m['method_name']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="status_id" class="form-label">Status</label>
            <select name="status_id" id="status_id" class="form-select" required>
                <?php 
                // For new letters created by Chief Management Assistant, default to "Pending" for Institution Head to assign
                $default_status_name = !$editing && in_array($role, ['chief_management_assistant', 'postal_subject_officer']) ? 'Pending' : null;
                
                while ($s = $statuses->fetch_assoc()) { 
                    $is_selected = false;
                    if ($editing) {
                        $is_selected = ($letter['status_id'] == $s['id']);
                    } elseif ($default_status_name && $s['status_name'] == $default_status_name) {
                        $is_selected = true;
                    }
                ?>
                    <option value="<?php echo $s['id']; ?>" <?php if ($is_selected) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($s['status_name']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <?php if ($editing || $role !== 'chief_management_assistant'): ?>
        <div class="col-md-4">
            <label for="department_id" class="form-label">
                Department
                <?php if ($role !== 'institution_head'): ?>
                    <small class="text-muted">(Restricted to your department)</small>
                <?php endif; ?>
            </label>
            <select name="department_id" id="department_id" class="form-select" required>
                <?php foreach ($departments as $d) { ?>
                    <option value="<?php echo $d['id']; ?>" 
                        <?php 
                        if ($editing && $letter['department_id'] == $d['id']) echo 'selected';
                        elseif (!$editing && $d['id'] == $user_dept_id) echo 'selected';
                        ?>
                    ><?php echo htmlspecialchars($d['name']); ?></option>
                <?php } ?>
            </select>
        </div>
        <?php else: ?>
            <!-- Department will be NULL for CMA - assigned by Institution Head later -->
            <input type="hidden" name="department_id" id="department_id" value="0">
        <?php endif; ?>        </div>
            </div>
        </div>
        
        <!-- Assignment and Dates Section -->
        <div class="col-12">
            <div class="form-section">
                <h5><i class="fas fa-calendar-alt"></i> Assignment & Dates</h5>
                <div class="row g-3">        <div class="col-md-6">
            <label for="employee_id" class="form-label">
                Assigned Employee
                <?php if ($role === 'postal_subject_officer'): ?>
                    <small class="text-danger">(Will be assigned by Institution Head)</small>
                <?php elseif ($role === 'chief_management_assistant'): ?>
                    <small class="text-warning">(Cannot assign - will be assigned by Institution Head)</small>
                <?php endif; ?>
            </label>
            <?php if (($role === 'postal_subject_officer' || $role === 'chief_management_assistant') && !$editing): ?>
                <input type="text" class="form-control" value="To be assigned by Institution Head" disabled>
                <input type="hidden" name="employee_id" value="0">
            <?php elseif ($role === 'chief_management_assistant' && $editing): ?>
                <!-- Chief Management Assistant editing: show assigned employee but make it readonly -->
                <?php 
                $assigned_emp_name = 'Not Assigned';
                if ($letter['employee_id']) {
                    $emp_stmt = $conn->prepare("SELECT name FROM Employee WHERE id = ?");
                    $emp_stmt->bind_param('i', $letter['employee_id']);
                    $emp_stmt->execute();
                    $emp_res = $emp_stmt->get_result();
                    if ($emp_row = $emp_res->fetch_assoc()) {
                        $assigned_emp_name = $emp_row['name'];
                    }
                }
                ?>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($assigned_emp_name); ?>" disabled>
                <input type="hidden" name="employee_id" value="<?php echo $letter['employee_id'] ?? 0; ?>">
                <small class="text-muted">You cannot change the assignment</small>
            <?php else: ?>
                <select name="employee_id" id="employee_id" class="form-select" required <?php echo ($role === 'postal_subject_officer' && $editing) ? '' : ''; ?>>
                    <?php while ($e = $employees->fetch_assoc()) { ?>
                        <option value="<?php echo $e['id']; ?>" 
                            <?php 
                            if ($editing && $letter['employee_id'] == $e['id']) echo 'selected';
                            elseif (!$editing && $e['id'] == $employee_id && $role === 'subject_officer') echo 'selected';
                            ?>
                    >
                        <?php echo htmlspecialchars($e['name']); ?>
                        <?php if (($role === 'institution_head' || $role === 'chief_management_assistant') && isset($e['dept_name'])): ?>
                            <small>(<?php echo htmlspecialchars($e['dept_name']); ?>)</small>
                        <?php endif; ?>
                    </option>
                    <?php } ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="col-md-<?php echo (!$editing && $role === 'chief_management_assistant') ? '12' : '3'; ?>">
            <label for="date_received" class="form-label">Date Received</label>
            <input type="date" name="date_received" id="date_received" class="form-control" required 
                   value="<?php echo $editing ? htmlspecialchars($letter['date_received']) : date('Y-m-d'); ?>">
        </div>
        <?php if ($editing || $role !== 'chief_management_assistant'): ?>
        <div class="col-md-3">
            <label for="date_sent" class="form-label">Date Sent</label>
            <input type="date" name="date_sent" id="date_sent" class="form-control" 
                   value="<?php echo $editing ? htmlspecialchars($letter['date_sent']) : ''; ?>">
        </div>
        <?php endif; ?>
        </div>
            </div>
        </div>
        
        <!-- Notes/Instructions Section -->
        <div class="col-12">
            <div class="form-section">
                <h5><i class="fas fa-sticky-note"></i> Notes / Instructions</h5>
        <div class="col-12">
            <label for="notes" class="form-label">
                <i class="fas fa-sticky-note"></i> Notes / Instructions
            </label>
            <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Add instructions for assignment, processing, or sending this letter..."><?php echo $editing && isset($letter['notes']) ? htmlspecialchars($letter['notes']) : ''; ?></textarea>
            <small class="text-muted">
                Use this field to add special instructions when assigning letters to members or when sending letters. 
                For example: "Urgent - Please respond by Friday", "CC to Department Head", "Requires approval before action", etc.
            </small>
        </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="col-12 mt-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> <?php echo $editing ? 'Update' : 'Add'; ?> Letter</button>
            <a href="letters.php" class="btn btn-secondary btn-lg"><i class="fas fa-times"></i> Cancel</a>
        </div>
    </form>
    
    <div class="mt-4">
        <a href="letters.php" class="btn btn-outline-dark"><i class="fas fa-arrow-left"></i> Back to Letters</a>
    </div>
</div>
<script>
// JavaScript to update letter number and employees when department changes
document.addEventListener('DOMContentLoaded', function() {
    var deptSelect = document.getElementById('department_id');
    var letterNumberInput = document.getElementById('letter_number');
    var employeeSelect = document.getElementById('employee_id');
    var editing = <?php echo json_encode($editing); ?>;
    var letterId = <?php echo json_encode($id); ?>;
    var currentEmployeeId = <?php echo $editing && isset($letter['employee_id']) ? $letter['employee_id'] : 'null'; ?>;
    
    if (deptSelect && letterNumberInput) {
        deptSelect.addEventListener('change', function() {
            var deptId = this.value;
            console.log('Department changed to:', deptId);
            
            if (deptId) {
                // Update letter number
                var xhr1 = new XMLHttpRequest();
                var url1 = 'get_letter_number.php?department_id=' + deptId;
                if (editing && letterId) {
                    url1 += '&letter_id=' + letterId;
                }
                
                xhr1.open('GET', url1, true);
                xhr1.onload = function() {
                    if (xhr1.status === 200) {
                        letterNumberInput.value = xhr1.responseText;
                    }
                };
                xhr1.send();
                
                // Update employee list
                var xhr2 = new XMLHttpRequest();
                var url2 = 'get_employees_by_department.php?department_id=' + deptId;
                
                xhr2.open('GET', url2, true);
                xhr2.onload = function() {
                    if (xhr2.status === 200) {
                        try {
                            var response = JSON.parse(xhr2.responseText);
                            if (response.employees) {
                                // Clear current options
                                employeeSelect.innerHTML = '';
                                
                                // Add new options
                                response.employees.forEach(function(emp) {
                                    var option = document.createElement('option');
                                    option.value = emp.id;
                                    option.textContent = emp.name;
                                    if (emp.dept_name) {
                                        option.textContent += ' (' + emp.dept_name + ')';
                                    }
                                    // Keep current employee selected if in new list
                                    if (currentEmployeeId && emp.id == currentEmployeeId) {
                                        option.selected = true;
                                    }
                                    employeeSelect.appendChild(option);
                                });
                                
                                console.log('Loaded ' + response.employees.length + ' employees');
                            } else if (response.error) {
                                console.error('Error loading employees:', response.error);
                            }
                        } catch (e) {
                            console.error('Failed to parse employee data:', e);
                        }
                    }
                };
                xhr2.send();
            } else {
                letterNumberInput.value = '';
                employeeSelect.innerHTML = '<option value="">Select department first</option>';
            }
        });
    }
});
</script>

<!-- Footer -->
<?php render_dashboard_footer(); ?>
</body>
</html>