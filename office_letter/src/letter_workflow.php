<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'csrf_helper.php';

$user_id = intval($_SESSION['user_id']);

// Get user information
$userStmt = $conn->prepare("
    SELECT u.role, e.name as employee_name, e.id as employee_id, e.department_id
    FROM Users u
    LEFT JOIN Employee e ON u.employee_id = e.id
    WHERE u.id = ? LIMIT 1
");
$userStmt->bind_param('i', $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();
$role = $userData['role'];
$employee_name = $userData['employee_name'];
$emp_id = $userData['employee_id'];
$dept_id = $userData['department_id'];

// Get letter ID
$letter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$letter_id) {
    header('Location: letters.php');
    exit;
}

// Get letter details with all related information
$letterStmt = $conn->prepare("
    SELECT 
        l.*,
        ls.status_name,
        d.name as department_name,
        e.name as assigned_officer_name,
        m.method_name,
        assignedBy.name as assigned_by_name,
        processedBy.name as processed_by_name,
        completedBy.name as completed_by_name
    FROM Letter l
    LEFT JOIN LetterStatus ls ON l.status_id = ls.id
    LEFT JOIN Department d ON l.department_id = d.id
    LEFT JOIN Employee e ON l.employee_id = e.id
    LEFT JOIN LetterMethod m ON l.method_id = m.id
    LEFT JOIN Users u1 ON l.assigned_by = u1.id
    LEFT JOIN Employee assignedBy ON u1.employee_id = assignedBy.id
    LEFT JOIN Users u2 ON l.processed_by = u2.id
    LEFT JOIN Employee processedBy ON u2.employee_id = processedBy.id
    LEFT JOIN Users u3 ON l.completed_by = u3.id
    LEFT JOIN Employee completedBy ON u3.employee_id = completedBy.id
    WHERE l.id = ? LIMIT 1
");
$letterStmt->bind_param('i', $letter_id);
$letterStmt->execute();
$letterResult = $letterStmt->get_result();
$letter = $letterResult->fetch_assoc();

if (!$letter) {
    header('Location: letters.php');
    exit;
}

// Get all instructions/notes for this letter
$instructionsStmt = $conn->prepare("
    SELECT * FROM LetterInstructions 
    WHERE letter_id = ? 
    ORDER BY created_at ASC
");
$instructionsStmt->bind_param('i', $letter_id);
$instructionsStmt->execute();
$instructionsResult = $instructionsStmt->get_result();

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    
    $action = $_POST['action'] ?? '';
    
    // ASSIGN LETTER (Institution Head only)
    if ($action === 'assign' && $role === 'institution_head') {
        $assign_to = intval($_POST['assign_to']);
        $instruction = $_POST['instruction'] ?? '';
        
        // Get the current letter number and department of the employee being assigned to
        $empDeptStmt = $conn->prepare("
            SELECT e.department_id, d.abbreviation, l.letter_number, l.department_id as current_dept_id
            FROM Employee e
            JOIN Department d ON e.department_id = d.id
            JOIN Letter l ON l.id = ?
            WHERE e.id = ?
            LIMIT 1
        ");
        $empDeptStmt->bind_param('ii', $letter_id, $assign_to);
        $empDeptStmt->execute();
        $empDeptResult = $empDeptStmt->get_result();
        
        $new_letter_number = null;
        $employee_dept_id = null;
        if ($empDeptRow = $empDeptResult->fetch_assoc()) {
            $dept_abbr = $empDeptRow['abbreviation'];
            $current_letter_number = $empDeptRow['letter_number'];
            $employee_dept_id = $empDeptRow['department_id'];
            
            // If letter number doesn't have department abbreviation (created by Chief Management Assistant)
            // Format check: YYMM-ID (4 digits, hyphen, then more digits)
            if ($dept_abbr && !empty($current_letter_number)) {
                // Check if it's in YYMM-ID format (no department prefix)
                if (preg_match('/^\d{4}-\d+$/', $current_letter_number)) {
                    // Format is YYMM-ID, add department abbreviation
                    $new_letter_number = $dept_abbr . '-' . $current_letter_number;
                }
            }
        }
        
        // Update query to include letter_number and department_id if needed
        if ($new_letter_number && $employee_dept_id) {
            $assignStmt = $conn->prepare("
                UPDATE Letter 
                SET employee_id = ?, 
                    assigned_by = ?, 
                    assigned_at = NOW(),
                    department_id = ?,
                    letter_number = ?,
                    status_id = (SELECT id FROM LetterStatus WHERE status_name = 'Assigned' LIMIT 1)
                WHERE id = ?
            ");
            $assignStmt->bind_param('iiisi', $assign_to, $user_id, $employee_dept_id, $new_letter_number, $letter_id);
        } else {
            $assignStmt = $conn->prepare("
                UPDATE Letter 
                SET employee_id = ?, 
                    assigned_by = ?, 
                    assigned_at = NOW(),
                    status_id = (SELECT id FROM LetterStatus WHERE status_name = 'Assigned' LIMIT 1)
                WHERE id = ?
            ");
            $assignStmt->bind_param('iii', $assign_to, $user_id, $letter_id);
        }
        
        if ($assignStmt->execute()) {
            // Add instruction record
            if (!empty($instruction)) {
                $instStmt = $conn->prepare("
                    INSERT INTO LetterInstructions (letter_id, user_id, user_name, user_role, instruction_type, message)
                    VALUES (?, ?, ?, ?, 'assignment', ?)
                ");
                $instStmt->bind_param('iisss', $letter_id, $user_id, $employee_name, $role, $instruction);
                $instStmt->execute();
            }
            $success = 'Letter assigned successfully!';
            header("Location: letter_workflow.php?id=$letter_id&success=assigned");
            exit;
        } else {
            $error = 'Failed to assign letter.';
        }
    }
    
    // ADD INSTRUCTION/NOTE
    elseif ($action === 'add_instruction') {
        $instruction = trim($_POST['instruction'] ?? '');
        $inst_type = $_POST['instruction_type'] ?? 'note';
        
        if (!empty($instruction)) {
            $instStmt = $conn->prepare("
                INSERT INTO LetterInstructions (letter_id, user_id, user_name, user_role, instruction_type, message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $instStmt->bind_param('iissss', $letter_id, $user_id, $employee_name, $role, $inst_type, $instruction);
            
            if ($instStmt->execute()) {
                $success = 'Instruction/note added successfully!';
                header("Location: letter_workflow.php?id=$letter_id&success=note_added");
                exit;
            }
        }
    }
    
    // UPDATE STATUS
    elseif ($action === 'update_status') {
        $new_status = $_POST['new_status'];
        $note = trim($_POST['status_note'] ?? '');
        $date_sent = $_POST['date_sent'] ?? null;
        
        // Update status and date_sent if provided
        if (!empty($date_sent)) {
            $updateStmt = $conn->prepare("UPDATE Letter SET status_id = ?, date_sent = ? WHERE id = ?");
            $updateStmt->bind_param('isi', $new_status, $date_sent, $letter_id);
        } else {
            $updateStmt = $conn->prepare("UPDATE Letter SET status_id = ? WHERE id = ?");
            $updateStmt->bind_param('ii', $new_status, $letter_id);
        }
        
        if ($updateStmt->execute()) {
            // Add tracking based on status
            if ($new_status == 3) { // Processed
                $conn->query("UPDATE Letter SET processed_by = $user_id, processed_at = NOW() WHERE id = $letter_id");
            } elseif ($new_status == 5) { // Completed
                $conn->query("UPDATE Letter SET completed_by = $user_id, completed_at = NOW() WHERE id = $letter_id");
            }
            
            // Add note if provided
            if (!empty($note)) {
                $instStmt = $conn->prepare("
                    INSERT INTO LetterInstructions (letter_id, user_id, user_name, user_role, instruction_type, message)
                    VALUES (?, ?, ?, ?, 'update', ?)
                ");
                $instStmt->bind_param('iisss', $letter_id, $user_id, $employee_name, $role, $note);
                $instStmt->execute();
            }
            
            $success = 'Status updated successfully!';
            header("Location: letter_workflow.php?id=$letter_id&success=status_updated");
            exit;
        }
    }
    
    // UPLOAD ATTACHMENT (Multiple Attachments Support)
    elseif ($action === 'upload_attachment' && isset($_FILES['attachment'])) {
        $upload_dir = '../uploads/letters/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['attachment'];
        $attachment_label = trim($_POST['attachment_label'] ?? '');
        $original_filename = basename($file['name']);
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $stored_filename = time() . '_' . $original_filename;
        $filepath = $upload_dir . $stored_filename;
        $file_size = $file['size'];
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Insert into LetterAttachments table
            $insertStmt = $conn->prepare("
                INSERT INTO LetterAttachments 
                (letter_id, file_name, file_path, file_size, file_type, attachment_label, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->bind_param('isssssi', 
                $letter_id, 
                $original_filename, 
                $stored_filename, 
                $file_size, 
                $file_extension, 
                $attachment_label, 
                $user_id
            );
            
            if ($insertStmt->execute()) {
                $success = 'Attachment uploaded successfully!';
                header("Location: letter_workflow.php?id=$letter_id&success=attachment_uploaded");
                exit;
            } else {
                $error = 'Failed to save attachment information.';
            }
        } else {
            $error = 'Failed to upload file.';
        }
    }
    
    // DELETE ATTACHMENT
    elseif ($action === 'delete_attachment') {
        $attachment_id = intval($_POST['attachment_id']);
        
        // Get attachment details
        $attStmt = $conn->prepare("SELECT file_path, letter_id FROM LetterAttachments WHERE id = ? AND letter_id = ?");
        $attStmt->bind_param('ii', $attachment_id, $letter_id);
        $attStmt->execute();
        $attResult = $attStmt->get_result();
        
        if ($attachment = $attResult->fetch_assoc()) {
            // Delete file
            $file_path = '../uploads/letters/' . $attachment['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $delStmt = $conn->prepare("DELETE FROM LetterAttachments WHERE id = ?");
            $delStmt->bind_param('i', $attachment_id);
            $delStmt->execute();
            
            $success = 'Attachment deleted successfully!';
            header("Location: letter_workflow.php?id=$letter_id&success=attachment_deleted");
            exit;
        }
    }
}

// Get employees for assignment
$employeesStmt = $conn->query("
    SELECT e.id, e.name, e.role, d.name as dept_name 
    FROM Employee e 
    LEFT JOIN Department d ON e.department_id = d.id 
    WHERE e.role = 'subject_officer'
    ORDER BY d.name, e.name
");

// Get statuses - filtered by role
$allowedStatuses = [];
if ($role === 'institution_head') {
    $allowedStatuses = ['Pending', 'Assigned', 'In Progress', 'Under Review', 'Approved', 'Rejected', 'On Hold', 'Completed', 'Archived', 'Cancelled', 'Filed - Information Only'];
} elseif ($role === 'department_head') {
    $allowedStatuses = ['Assigned', 'In Progress', 'Under Review', 'Approved', 'Rejected', 'On Hold', 'Completed', 'Filed - Information Only'];
} elseif ($role === 'subject_officer') {
    $allowedStatuses = ['Assigned', 'In Progress', 'Under Review', 'Completed', 'On Hold', 'Filed - Information Only'];
} elseif ($role === 'postal_subject_officer') {
    $allowedStatuses = ['Pending', 'Assigned', 'In Progress', 'Completed', 'Archived', 'Filed - Information Only'];
} elseif ($role === 'chief_management_assistant') {
    $allowedStatuses = ['Pending', 'Assigned', 'In Progress', 'Under Review', 'Completed', 'Approved', 'On Hold', 'Filed - Information Only'];
} else {
    $allowedStatuses = ['In Progress', 'Completed'];
}

// Debug: Check role and allowed statuses
error_log("User role: " . $role);
error_log("Allowed statuses: " . implode(', ', $allowedStatuses));

$statusPlaceholders = implode(',', array_fill(0, count($allowedStatuses), '?'));
$statusQuery = "SELECT * FROM LetterStatus WHERE status_name IN ($statusPlaceholders) ORDER BY id";
$statusesStmt = $conn->prepare($statusQuery);
if ($statusesStmt) {
    $types = str_repeat('s', count($allowedStatuses));
    $statusesStmt->bind_param($types, ...$allowedStatuses);
    $statusesStmt->execute();
    $statusesStmt = $statusesStmt->get_result();
    
    // Debug: Count results
    error_log("Number of statuses fetched: " . $statusesStmt->num_rows);
} else {
    error_log("Failed to prepare status query, using fallback");
    $statusesStmt = $conn->query("SELECT * FROM LetterStatus ORDER BY id");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter Workflow - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .workflow-container {
            padding: 2rem 0;
        }
        .letter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .instruction-item {
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .instruction-item.assignment {
            border-left-color: #28a745;
        }
        .instruction-item.instruction {
            border-left-color: #ffc107;
        }
        .instruction-item.note {
            border-left-color: #17a2b8;
        }
        .instruction-item.update {
            border-left-color: #6610f2;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="workflow-container">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white"><i class="fas fa-tasks"></i> Letter Workflow Management</h2>
            <a href="letters.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Back to Letters</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Operation completed successfully!
            </div>
        <?php endif; ?>

        <!-- Letter Details Card -->
        <div class="letter-card">
            <h3><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($letter['subject']); ?></h3>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Letter Number:</strong> <?php echo htmlspecialchars($letter['letter_number']); ?></p>
                    <p><strong>Letter Type (ලිපි වර්ගය):</strong> 
                        <?php 
                        $direction = $letter['letter_direction'] ?? 'incoming';
                        $directionLabels = [
                            'incoming' => '<span class="badge bg-info">Incoming (ලැබෙන)</span>',
                            'outgoing_institution' => '<span class="badge bg-success">Outgoing - Institution (ආයතනය යවන)</span>',
                            'outgoing_officer' => '<span class="badge bg-warning">Outgoing - Officer (නිලධාරී යවන)</span>'
                        ];
                        echo $directionLabels[$direction] ?? $direction;
                        ?>
                    </p>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($letter['sender']); ?></p>
                    <p><strong>To:</strong> <?php echo htmlspecialchars($letter['receiver']); ?></p>
                    <?php if (!empty($letter['recipient_organization'])): ?>
                        <p><strong>Recipient Organization:</strong> <?php echo htmlspecialchars($letter['recipient_organization']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($letter['recipient_person'])): ?>
                        <p><strong>Recipient Person:</strong> <?php echo htmlspecialchars($letter['recipient_person']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($letter['reference_letter_number'])): ?>
                        <p><strong>Reference Letter:</strong> 
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($letter['reference_letter_number']); ?></span>
                            <small class="text-muted">(Reply to this letter)</small>
                        </p>
                    <?php endif; ?>
                    <p><strong>Date Received:</strong> <?php echo date('Y-m-d', strtotime($letter['date_received'])); ?></p>
                    <p><strong>Date Sent:</strong> 
                        <?php 
                        if (!empty($letter['date_sent']) && $letter['date_sent'] != '0000-00-00') {
                            echo date('Y-m-d', strtotime($letter['date_sent']));
                        } else {
                            echo '<span class="text-muted">Not sent yet</span>';
                        }
                        ?>
                    </p>
                    <p><strong>Method:</strong> <?php echo htmlspecialchars($letter['method_name']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="status-badge bg-primary text-white">
                            <?php echo htmlspecialchars($letter['status_name']); ?>
                        </span>
                    </p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($letter['department_name'] ?? 'Not Assigned'); ?></p>
                    <p><strong>Assigned Officer:</strong> <?php echo htmlspecialchars($letter['assigned_officer_name'] ?? 'Not Assigned'); ?></p>
                    <?php if ($letter['assigned_by_name']): ?>
                        <p><strong>Assigned By:</strong> <?php echo htmlspecialchars($letter['assigned_by_name']); ?>
                           <small class="text-muted">(<?php echo date('Y-m-d H:i', strtotime($letter['assigned_at'])); ?>)</small>
                        </p>
                    <?php endif; ?>
                    <?php if ($letter['attachment_filename']): ?>
                        <p><strong>Attachment:</strong> 
                            <a href="view_attachment.php?id=<?php echo $letter_id; ?>" target="_blank">
                                <i class="fas fa-file"></i> <?php echo htmlspecialchars($letter['attachment_filename']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Instructions/Notes History -->
        <div class="letter-card">
            <h4><i class="fas fa-comments"></i> Instructions & Notes</h4>
            <hr>
            <?php if ($instructionsResult->num_rows > 0): ?>
                <?php while ($inst = $instructionsResult->fetch_assoc()): ?>
                    <div class="instruction-item <?php echo $inst['instruction_type']; ?>">
                        <div class="d-flex justify-content-between">
                            <strong>
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($inst['user_name']); ?>
                                <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $inst['user_role'])); ?></span>
                            </strong>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($inst['created_at'])); ?>
                            </small>
                        </div>
                        <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($inst['message'])); ?></p>
                        <small class="text-muted">Type: <?php echo ucfirst($inst['instruction_type']); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted">No instructions or notes yet.</p>
            <?php endif; ?>
        </div>

        <!-- Action Cards Based on Role -->
        
        <!-- INSTITUTION HEAD: Assign Letter -->
        <?php if ($role === 'institution_head' && empty($letter['employee_id'])): ?>
        <div class="letter-card">
            <h4><i class="fas fa-user-plus"></i> Assign Letter to Subject Officer</h4>
            <form method="POST">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="assign">
                <div class="mb-3">
                    <label class="form-label">Select Subject Officer</label>
                    <select name="assign_to" class="form-select" required>
                        <option value="">Choose Officer...</option>
                        <?php while ($emp = $employeesStmt->fetch_assoc()): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['name'] . ' - ' . ($emp['dept_name'] ?? 'No Dept')); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Instructions (Optional)</label>
                    <textarea name="instruction" class="form-control" rows="3" placeholder="Enter instructions for the subject officer..."></textarea>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i> Assign Letter</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- SUBJECT OFFICER or DEPARTMENT HEAD: Add Notes/Instructions -->
        <?php if (($role === 'subject_officer' && $letter['employee_id'] == $emp_id) || 
                  ($role === 'department_head' && $letter['department_id'] == $dept_id)): ?>
        
        <!-- Quick Action: File Letter for Information Only -->
        <?php if ($role === 'subject_officer' && $letter['employee_id'] == $emp_id): ?>
        <div class="letter-card" style="border-left: 4px solid #17c671;">
            <h4><i class="fas fa-folder"></i> File Letter (Information Only)</h4>
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle"></i> Use this option for letters received for information purposes only that don't require any action or processing.
            </p>
            <form method="POST">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="new_status" value="14"><!-- Filed - Information Only -->
                <div class="mb-3">
                    <label class="form-label">Filing Note (Optional)</label>
                    <textarea name="status_note" class="form-control" rows="2" placeholder="Add a note about why this letter is filed (e.g., 'For information only - No action required')"></textarea>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-folder-plus"></i> File Letter as Information Only
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="letter-card">
            <h4><i class="fas fa-sticky-note"></i> Add Note/Instruction</h4>
            <form method="POST">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="add_instruction">
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="instruction_type" class="form-select">
                        <option value="note">Note</option>
                        <option value="instruction">Instruction</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="instruction" class="form-control" rows="3" required placeholder="Enter your note or instruction..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Note</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- UPDATE STATUS -->
        <?php if (($role === 'subject_officer' && $letter['employee_id'] == $emp_id) || 
                  ($role === 'department_head' && $letter['department_id'] == $dept_id) ||
                  $role === 'institution_head'): ?>
        <div class="letter-card">
            <h4><i class="fas fa-exchange-alt"></i> Update Status</h4>
            <form method="POST">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="update_status">
                <div class="mb-3">
                    <label class="form-label">New Status</label>
                    <select name="new_status" class="form-select" required>
                        <?php 
                        // Get fresh status results
                        if (is_object($statusesStmt)) {
                            $statusesStmt->data_seek(0);
                            $statusCount = 0;
                            while ($status = $statusesStmt->fetch_assoc()): 
                                $statusCount++;
                                $statusName = htmlspecialchars($status['status_name']);
                                // Add helpful text for different roles
                                if ($role === 'department_head' && strtolower($status['status_name']) === 'approved') {
                                    $statusName .= ' (Forward to Institution Head)';
                                } elseif ($role === 'subject_officer' && strtolower($status['status_name']) === 'under review') {
                                    $statusName .= ' (Send to Department Head)';
                                }
                        ?>
                            <option value="<?php echo $status['id']; ?>" <?php echo ($status['id'] == $letter['status_id']) ? 'selected' : ''; ?>>
                                <?php echo $statusName; ?>
                            </option>
                        <?php 
                            endwhile;
                            // Debug output
                            if ($statusCount == 0) {
                                echo '<option disabled>No statuses available - Contact administrator</option>';
                            }
                        }
                        ?>
                    </select>
                    <small class="text-muted">
                        <?php 
                        if ($role === 'institution_head') {
                            echo 'As Institution Head, you can set any status';
                        } elseif ($role === 'department_head') {
                            echo '<i class="fas fa-info-circle"></i> Select "Approved" to forward to Institution Head for final review';
                        } elseif ($role === 'subject_officer') {
                            echo '<i class="fas fa-info-circle"></i> Select "Under Review" when ready for Department Head to review your work';
                        }
                        ?>
                    </small>
                </div>
                <?php if ($role === 'institution_head'): ?>
                <div class="mb-3">
                    <label class="form-label">Date Sent (Optional)</label>
                    <input type="date" name="date_sent" class="form-control" value="<?php echo !empty($letter['date_sent']) && $letter['date_sent'] != '0000-00-00' ? $letter['date_sent'] : ''; ?>">
                    <small class="text-muted">
                        <i class="fas fa-calendar"></i> Set this when marking letter as Completed
                    </small>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Note (Optional)</label>
                    <textarea name="status_note" class="form-control" rows="2" placeholder="Add a note about this status change..."></textarea>
                    <?php if ($role === 'department_head'): ?>
                    <small class="text-muted">
                        <i class="fas fa-lightbulb"></i> When approving, add notes for Institution Head's review
                    </small>
                    <?php elseif ($role === 'subject_officer'): ?>
                    <small class="text-muted">
                        <i class="fas fa-lightbulb"></i> When sending for review, summarize your work for Department Head
                    </small>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-warning"><i class="fas fa-sync"></i> Update Status</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- ALL ATTACHMENTS DISPLAY -->
        <?php 
        // Fetch all attachments for this letter
        $attachmentsStmt = $conn->prepare("
            SELECT la.*, u.id as uploader_user_id, e.name as uploader_name
            FROM LetterAttachments la
            LEFT JOIN Users u ON la.uploaded_by = u.id
            LEFT JOIN Employee e ON u.employee_id = e.id
            WHERE la.letter_id = ?
            ORDER BY la.uploaded_at DESC
        ");
        $attachmentsStmt->bind_param('i', $letter_id);
        $attachmentsStmt->execute();
        $attachmentsResult = $attachmentsStmt->get_result();
        $attachments = [];
        while ($att = $attachmentsResult->fetch_assoc()) {
            $attachments[] = $att;
        }
        
        if (count($attachments) > 0): ?>
        <div class="letter-card">
            <h4><i class="fas fa-paperclip"></i> Attachments (<?php echo count($attachments); ?>)</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attachments as $att): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($att['attachment_label'] ?: 'Document'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($att['file_name']); ?></td>
                            <td><?php echo number_format($att['file_size'] / 1024, 2); ?> KB</td>
                            <td><?php echo htmlspecialchars($att['uploader_name'] ?: 'Unknown'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($att['uploaded_at'])); ?></td>
                            <td>
                                <a href="view_attachment_multi.php?id=<?php echo $att['id']; ?>" 
                                   class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="view_attachment_multi.php?id=<?php echo $att['id']; ?>&download=1" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <?php if (($role === 'subject_officer' && $letter['employee_id'] == $emp_id) || 
                                          $role === 'institution_head'): ?>
                                <form method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this attachment?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="action" value="delete_attachment">
                                    <input type="hidden" name="attachment_id" value="<?php echo $att['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- UPLOAD NEW ATTACHMENT -->
        <?php if (($role === 'subject_officer' && $letter['employee_id'] == $emp_id) || 
                  ($role === 'department_head' && $letter['department_id'] == $dept_id) ||
                  $role === 'institution_head'): ?>
        <div class="letter-card">
            <h4><i class="fas fa-upload"></i> Upload New Attachment</h4>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="upload_attachment">
                <div class="mb-3">
                    <label class="form-label">Attachment Label</label>
                    <select name="attachment_label" class="form-select" required>
                        <option value="Original Letter">Original Letter (මුල් ලිපිය)</option>
                        <option value="Reply Letter">Reply Letter (පිළිතුරු ලිපිය)</option>
                        <option value="Supporting Document">Supporting Document (ආධාරක ලේඛනය)</option>
                        <option value="Approval Letter">Approval Letter (අනුමත ලිපිය)</option>
                        <option value="Draft Letter">Draft Letter (කෙටුම්පත)</option>
                        <option value="Final Letter">Final Letter (අවසන් ලිපිය)</option>
                        <option value="Other">Other (වෙනත්)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select File</label>
                    <input type="file" name="attachment" class="form-control" required
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                    <small class="text-muted">Allowed: PDF, Word, Images (Max 10MB)</small>
                </div>
                <button type="submit" class="btn btn-info">
                    <i class="fas fa-upload"></i> Upload Attachment
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
