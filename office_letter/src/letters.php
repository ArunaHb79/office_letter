<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'user_info_helper.php';
require_once 'footer_component.php';
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

// Base SQL query
$sql = "SELECT Letter.*, Department.abbreviation, Employee.name AS emp_name, LetterMethod.method_name, LetterStatus.status_name
    FROM Letter
    LEFT JOIN Department ON Letter.department_id = Department.id
    LEFT JOIN Employee ON Letter.employee_id = Employee.id
    JOIN LetterMethod ON Letter.method_id = LetterMethod.id
    JOIN LetterStatus ON Letter.status_id = LetterStatus.id";

// Role-based WHERE conditions
$where_conditions = [];
$params = [];
$param_types = '';

if ($role === 'institution_head' || $role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
    // Institution head and postal subject officer: see all letters (no role restriction)
} elseif ($role === 'department_head') {
    // Department head: see department letters
    $empStmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ? LIMIT 1");
    $empStmt->bind_param('i', $emp_id);
    $empStmt->execute();
    $empRes = $empStmt->get_result();
    if ($empRes && $empRes->num_rows > 0) {
        $deptRow = $empRes->fetch_assoc();
        $user_dept_id = $deptRow['department_id'];
        $where_conditions[] = "Letter.department_id = ?";
        $params[] = $user_dept_id;
        $param_types .= 'i';
    }
} elseif ($role === 'subject_officer') {
    // Subject officer: see own letters
    $where_conditions[] = "Letter.employee_id = ?";
    $params[] = $emp_id;
    $param_types .= 'i';
} else {
    // Other roles: restrict access
    header('Location: dashboard.php');
    exit;
}

// Apply filter conditions from GET parameters

// Special filter for unassigned letters (Institution Head only)
if (!empty($_GET['filter']) && $_GET['filter'] === 'unassigned' && $role === 'institution_head') {
    $where_conditions[] = "Letter.employee_id IS NULL";
    $where_conditions[] = "LetterStatus.status_name IN ('Pending', 'Received')";
}

if (!empty($_GET['department_id']) && is_numeric($_GET['department_id'])) {
    $filter_dept_id = intval($_GET['department_id']);
    
    // For department heads, ensure they can only filter within their department
    if ($role === 'department_head') {
        if (isset($user_dept_id) && $filter_dept_id == $user_dept_id) {
            // Already filtered by user's department, no additional condition needed
        } else {
            // Invalid department filter for this user, ignore it
        }
    } elseif ($role === 'institution_head') {
        // Institution head can filter by any department
        $where_conditions[] = "Letter.department_id = ?";
        $params[] = $filter_dept_id;
        $param_types .= 'i';
    }
    // Subject officers cannot filter by department (already restricted to own letters)
}

if (!empty($_GET['status'])) {
    $status = $_GET['status'];
    // Convert display name back to actual status name
    if ($status === 'Department Head Approved') {
        $status = 'Approved';
    }
    $where_conditions[] = "LetterStatus.status_name = ?";
    $params[] = $status;
    $param_types .= 's';
}

if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $date_from = $_GET['date_from'];
    $date_to = $_GET['date_to'];
    $where_conditions[] = "Letter.date_received BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $param_types .= 'ss';
}

// Build final query
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}
$sql .= " ORDER BY Letter.date_received DESC";

// Log search activity if filters are applied
if (!empty($_GET['department_id']) || !empty($_GET['status']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])) {
    $logger = new ActivityLogger($conn, $user_id);
    $search_criteria = [];
    if (!empty($_GET['department_id'])) $search_criteria['department_id'] = $_GET['department_id'];
    if (!empty($_GET['status'])) $search_criteria['status'] = $_GET['status'];
    if (!empty($_GET['date_from'])) $search_criteria['date_from'] = $_GET['date_from'];
    if (!empty($_GET['date_to'])) $search_criteria['date_to'] = $_GET['date_to'];
    
    $logger->log('letters_searched', null, null, [
        'search_criteria' => $search_criteria,
        'filters_applied' => count($search_criteria)
    ]);
}

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Letters</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <?php echo get_user_info_styles(); ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .letters-container {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 2.5rem;
            margin-top: 20px;
            margin-bottom: 40px;
            max-width: 100%;
        }
        .letters-heading {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: bold;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 1.5rem;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(255,255,255,0.8);
        }
        /* Make table responsive and readable */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #lettersTable {
            font-size: 0.9rem;
            width: 100% !important;
        }
        #lettersTable thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            display: table-header-group !important;
            visibility: visible !important;
        }
        #lettersTable th {
            white-space: nowrap;
            font-weight: 700;
            padding: 12px 8px;
            color: white !important;
            background: transparent !important;
            border: none !important;
        }
        #lettersTable td {
            padding: 10px 8px;
            vertical-align: middle;
        }
        /* Improve button spacing */
        .btn-sm {
            margin: 2px;
        }
        /* Filter section improvements */
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        /* Custom Notes Overlay - No Flashing */
        #notesOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        #notesOverlay.active {
            display: flex;
        }
        .notes-modal-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .notes-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notes-modal-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .notes-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            line-height: 1;
            opacity: 0.8;
        }
        .notes-close-btn:hover {
            opacity: 1;
        }
        .notes-modal-body {
            padding: 1.5rem;
        }
        .notes-title, .notes-subject {
            margin-bottom: 0.75rem;
        }
        .notes-text {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #0dcaf0;
            line-height: 1.7;
        }
        .notes-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            text-align: right;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <div class="container-fluid px-4" style="max-width: 1600px;">
        <div class="letters-container">
            <div class="letters-heading"><?php echo strtoupper(APP_NAME); ?></div>
            
            <!-- User Information -->
            <?php echo display_user_info_compact($user_info); ?>
            
            <h4 class="mb-4 text-center">
                Letters
                <?php if (!empty($_GET['filter']) && $_GET['filter'] === 'unassigned'): ?>
                    <span class="badge bg-warning text-dark ms-2">
                        <i class="fas fa-user-slash"></i> Showing Unassigned Letters Only
                    </span>
                <?php endif; ?>
            </h4>
            
            <!-- Unassigned Filter Info -->
            <?php if (!empty($_GET['filter']) && $_GET['filter'] === 'unassigned' && $role === 'institution_head'): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-filter"></i> 
                            <strong>Filter Active:</strong> Showing only letters that need to be assigned to officers.
                        </div>
                        <a href="letters.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear Filter
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Success and Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    $error_msg = $_GET['error'];
                    if ($error_msg === 'invalid_id') {
                        echo 'Invalid letter ID provided.';
                    } elseif ($error_msg === 'letter_not_found') {
                        echo 'Letter not found.';
                    } else {
                        echo htmlspecialchars($error_msg);
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Role-based access notice -->
            <?php if ($role === 'postal_subject_officer'): ?>
                <div class="alert alert-primary">
                    <strong>Postal Subject Officer View:</strong> You can see all letters and manage letters you created.
                </div>
            <?php elseif ($role === 'chief_management_assistant'): ?>
                <div class="alert alert-warning">
                    <strong>Chief Management Assistant View:</strong> You can see all letters and edit letters assigned to you. You can add new received letters but cannot assign them.
                </div>
            <?php elseif ($role === 'subject_officer'): ?>
                <div class="alert alert-info">
                    <strong>Subject Officer View:</strong> You can see and manage only letters assigned to you.
                </div>
            <?php elseif ($role === 'department_head'): ?>
                <div class="alert alert-info">
                    <strong>Department Head View:</strong> You can see and manage all letters in your department.
                </div>
            <?php elseif ($role === 'institution_head'): ?>
                <div class="alert alert-success">
                    <strong>Institution Head View:</strong> You have full access to all letters across all departments.
                </div>
            <?php endif; ?>
            
            <a href="letter_form.php" class="btn btn-primary mb-3"><i class="fas fa-plus-circle"></i> Add New Letter</a>
            
            <div class="filter-section">
                <h5 class="mb-3"><i class="fas fa-filter"></i> Filter Letters</h5>
                <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="department_id" class="form-label">Department</label>
                <select name="department_id" id="department_id" class="form-select">
                    <option value="">All</option>
                    <?php
                    // Role-based department filter options
                    if ($role === 'institution_head') {
                        // Institution head can filter by any department
                        $deptStmt = $conn->query("SELECT id, name FROM Department ORDER BY name");
                        while ($dept = $deptStmt->fetch_assoc()) {
                            $selected = (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : '';
                            echo "<option value='{$dept['id']}' $selected>" . htmlspecialchars($dept['name']) . "</option>";
                        }
                    } elseif ($role === 'department_head') {
                        // Department head can only see their own department (no filter needed)
                        if (isset($user_dept_id)) {
                            $deptStmt = $conn->prepare("SELECT id, name FROM Department WHERE id = ?");
                            $deptStmt->bind_param('i', $user_dept_id);
                            $deptStmt->execute();
                            $deptResult = $deptStmt->get_result();
                            if ($dept = $deptResult->fetch_assoc()) {
                                $selected = (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : '';
                                echo "<option value='{$dept['id']}' $selected>" . htmlspecialchars($dept['name']) . "</option>";
                            }
                        }
                        echo "<option disabled>-- Limited to your department --</option>";
                    } else {
                        // Subject officers can't filter by department
                        echo "<option disabled>-- Not available for your role --</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All</option>
                    <?php
                    // Get actual status values from database
                    $statusStmt = $conn->query("SELECT DISTINCT status_name FROM LetterStatus ORDER BY status_name");
                    while ($status_row = $statusStmt->fetch_assoc()) {
                        $selected = (isset($_GET['status']) && $_GET['status'] == $status_row['status_name']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($status_row['status_name']) . "' $selected>" . 
                             htmlspecialchars($status_row['status_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="letters.php" class="btn btn-secondary">Clear Filters</a>
                <span class="ms-3 text-muted">
                    <?php 
                    $total_count = $result->num_rows;
                    echo "Showing {$total_count} letter(s)";
                    
                    // Show active filters
                    $active_filters = [];
                    if (!empty($_GET['department_id'])) {
                        $dept_name = '';
                        $dept_stmt = $conn->prepare("SELECT name FROM Department WHERE id = ?");
                        $dept_stmt->bind_param('i', $_GET['department_id']);
                        $dept_stmt->execute();
                        $dept_result = $dept_stmt->get_result();
                        if ($dept_row = $dept_result->fetch_assoc()) {
                            $active_filters[] = "Department: " . htmlspecialchars($dept_row['name']);
                        }
                    }
                    if (!empty($_GET['status'])) {
                        $active_filters[] = "Status: " . htmlspecialchars($_GET['status']);
                    }
                    if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
                        $active_filters[] = "Date: " . htmlspecialchars($_GET['date_from']) . " to " . htmlspecialchars($_GET['date_to']);
                    }
                    
                    if (!empty($active_filters)) {
                        echo " | Filters: " . implode(", ", $active_filters);
                    }
                    ?>
                </span>
            </div>
        </form>
        </div>
        
        <div class="table-responsive">
        <table id="lettersTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Subject</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Department</th>
                    <th>Employee</th>
                    <th>Date Received</th>
                    <th>Date Sent</th>
                    <th>Notes</th>
                    <th>Attachment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td>
                        <?php
                        // If letter_number is missing or not in requested format, generate it for display
                        $letter_number = $row['letter_number'];
                        $abbr = $row['abbreviation'];
                        $id = $row['id'];
                        $date_received = $row['date_received'];
                        $year = date('y', strtotime($date_received));
                        $month = date('m', strtotime($date_received));
                        
                        // If no department abbreviation (unassigned letter), just show letter_number as is
                        if (empty($abbr)) {
                            echo htmlspecialchars($letter_number);
                        } else {
                            $expected_format = $abbr . '-' . $year . $month . '-' . $id;
                            if (empty($letter_number) || strpos($letter_number, $abbr . '-') !== 0) {
                                echo htmlspecialchars($expected_format);
                            } else {
                                echo htmlspecialchars($letter_number);
                            }
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><?php echo htmlspecialchars($row['sender']); ?></td>
                    <td><?php echo htmlspecialchars($row['receiver']); ?></td>
                    <td><?php echo htmlspecialchars($row['method_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['status_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['abbreviation']); ?></td>
                    <td>
                        <?php if (!empty($row['emp_name'])): ?>
                            <?php echo htmlspecialchars($row['emp_name']); ?>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-user-slash"></i> Unassigned
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['date_received']); ?></td>
                    <td><?php echo htmlspecialchars($row['date_sent']); ?></td>
                    <td>
                        <?php if (!empty($row['notes'])): ?>
                            <?php
                            // Generate proper letter number for notes display
                            $letter_number = $row['letter_number'];
                            $abbr = $row['abbreviation'];
                            $letter_id = $row['id'];
                            $date_received = $row['date_received'];
                            $year = date('y', strtotime($date_received));
                            $month = date('m', strtotime($date_received));
                            
                            // If no department abbreviation (unassigned letter), use letter_number as is
                            if (empty($abbr)) {
                                $display_letter_number = $letter_number;
                            } else {
                                $expected_format = $abbr . '-' . $year . $month . '-' . $letter_id;
                                $display_letter_number = (empty($letter_number) || strpos($letter_number, $abbr . '-') !== 0) ? $expected_format : $letter_number;
                            }
                            ?>
                            <button type="button" class="btn btn-sm btn-outline-info notes-view-btn" 
                                    onclick="showNotes(<?php echo $row['id']; ?>)">
                                <i class="fas fa-sticky-note"></i> View
                            </button>
                            
                            <!-- Hidden notes content -->
                            <div id="notesContent<?php echo $row['id']; ?>" style="display: none;">
                                <div class="notes-title">
                                    <strong>Letter:</strong> <?php echo htmlspecialchars($display_letter_number); ?>
                                </div>
                                <div class="notes-subject">
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($row['subject']); ?>
                                </div>
                                <hr>
                                <div class="notes-text">
                                    <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($row['attachment_filename'])): ?>
                            <a href="view_attachment.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="<?php echo htmlspecialchars($row['attachment_filename']); ?>">
                                <i class="fas fa-file-pdf"></i> View
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        // Role-based edit permissions
                        $can_edit = false;
                        $can_delete = false;
                        
                        if ($role === 'institution_head') {
                            // Institution head can edit/delete all letters
                            $can_edit = true;
                            $can_delete = true;
                        } elseif ($role === 'postal_subject_officer') {
                            // Postal subject officer can edit all letters (for reassignment)
                            $can_edit = true;
                            $can_delete = false; // Cannot delete
                        } elseif ($role === 'chief_management_assistant') {
                            // Chief Management Assistant can only edit letters assigned to them
                            $can_edit = ($row['employee_id'] == $emp_id);
                            $can_delete = false; // Cannot delete
                        } elseif ($role === 'department_head') {
                            // Department head can edit/delete letters in their department
                            $can_edit = true;
                            $can_delete = true;
                        } elseif ($role === 'subject_officer') {
                            // Subject officer can only edit/delete their own letters
                            $can_edit = ($row['employee_id'] == $emp_id);
                            $can_delete = ($row['employee_id'] == $emp_id);
                        }
                        ?>
                        
                        <a href="letter_workflow.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Manage Workflow">
                            <i class="fas fa-tasks"></i> Workflow
                        </a>
                        
                        <?php 
                        // Show quick assign button for unassigned letters (Institution Head only)
                        if ($role === 'institution_head' && empty($row['employee_id'])): 
                        ?>
                            <a href="letter_workflow.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Assign this letter">
                                <i class="fas fa-user-plus"></i> Assign
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($can_edit): ?>
                            <a href="letter_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                        
                        <?php if ($can_delete): ?>
                            <a href="delete_letter.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        </div>
        
        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
    </div>
    
    <!-- Custom Notes Overlay -->
    <div id="notesOverlay" onclick="closeNotesIfClickedOutside(event)">
        <div class="notes-modal-box" onclick="event.stopPropagation()">
            <div class="notes-modal-header">
                <h5><i class="fas fa-sticky-note"></i> Notes/Instructions</h5>
                <button class="notes-close-btn" onclick="closeNotes()">&times;</button>
            </div>
            <div class="notes-modal-body" id="notesModalContent">
                <!-- Content will be inserted here -->
            </div>
            <div class="notes-modal-footer">
                <button class="btn btn-secondary" onclick="closeNotes()">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        function showNotes(letterId) {
            const content = document.getElementById('notesContent' + letterId);
            const modalContent = document.getElementById('notesModalContent');
            const overlay = document.getElementById('notesOverlay');
            
            if (content && modalContent) {
                modalContent.innerHTML = content.innerHTML;
                overlay.classList.add('active');
            }
        }
        
        function closeNotes() {
            const overlay = document.getElementById('notesOverlay');
            overlay.classList.remove('active');
        }
        
        function closeNotesIfClickedOutside(event) {
            if (event.target.id === 'notesOverlay') {
                closeNotes();
            }
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeNotes();
            }
        });
    </script>
    
    <script>
        $(document).ready(function() {
            $('#lettersTable').DataTable();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Footer -->
    <?php render_dashboard_footer(); ?>
</body>
</html>