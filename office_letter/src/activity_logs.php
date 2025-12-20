<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
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

// Check if user can view activity logs
if (!ActivityLogger::canViewLogs($role)) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Build query based on role and filters
$where_conditions = ["1=1"];
$params = [];
$param_types = '';

// Exclude invalid actions from all queries
$where_conditions[] = "al.action IS NOT NULL AND al.action != '' AND al.action != '0' AND al.action != 'unknown_action'";

// Role-based filtering
if ($role === 'department_head') {
    // Department heads can only see logs from users in their department
    $user_dept_id = $user_info['employee_id'];
    $where_conditions[] = "e.department_id = (SELECT department_id FROM employee WHERE id = ?)";
    $params[] = $user_dept_id;
    $param_types .= 'i';
}

// Apply filters from GET parameters
if (!empty($_GET['action'])) {
    $where_conditions[] = "al.action = ?";
    $params[] = $_GET['action'];
    $param_types .= 's';
}

// Category filter
if (!empty($_GET['category'])) {
    $category = $_GET['category'];
    $category_actions = [
        'letter_operations' => ['letter_created', 'letter_updated', 'letter_updated_manual', 'letter_deleted', 'letter_viewed', 'letter_assigned', 'status_changed', 'letters_searched'],
        'attachments' => ['attachment_added', 'attachment_removed', 'attachment_replaced', 'attachment_viewed', 'attachment_downloaded'],
        'authentication' => ['user_login', 'user_logout', 'login_failed'],
        'security' => ['password_changed', 'password_reset_requested', 'password_reset_completed', 'unauthorized_access'],
        'administration' => ['role_changed', 'department_changed', 'data_exported', 'configuration_changed']
    ];
    
    if (isset($category_actions[$category])) {
        $placeholders = str_repeat('?,', count($category_actions[$category]) - 1) . '?';
        $where_conditions[] = "al.action IN ($placeholders)";
        foreach ($category_actions[$category] as $action) {
            $params[] = $action;
            $param_types .= 's';
        }
    }
}

if (!empty($_GET['user_id']) && ($role === 'institution_head' || $role === 'department_head')) {
    $filter_user_id = intval($_GET['user_id']);
    
    // For department heads, verify the user belongs to their department
    if ($role === 'department_head') {
        $verify_user_dept = $conn->prepare("SELECT e.department_id FROM users u JOIN employee e ON u.employee_id = e.id WHERE u.id = ?");
        $verify_user_dept->bind_param('i', $filter_user_id);
        $verify_user_dept->execute();
        $verify_result = $verify_user_dept->get_result();
        
        if ($verify_result && $verify_row = $verify_result->fetch_assoc()) {
            $user_dept_result = $conn->query("SELECT department_id FROM employee WHERE id = " . intval($user_info['employee_id']));
            $user_dept = $user_dept_result->fetch_assoc();
            
            if ($verify_row['department_id'] == $user_dept['department_id']) {
                $where_conditions[] = "al.user_id = ?";
                $params[] = $filter_user_id;
                $param_types .= 'i';
            }
        }
    } else {
        // Institution head can filter by any user
        $where_conditions[] = "al.user_id = ?";
        $params[] = $filter_user_id;
        $param_types .= 'i';
    }
}

if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $where_conditions[] = "DATE(al.timestamp) BETWEEN ? AND ?";
    $params[] = $_GET['date_from'];
    $params[] = $_GET['date_to'];
    $param_types .= 'ss';
}

// Build final query
$sql = "
    SELECT 
        al.*,
        u.username,
        u.role as user_role,
        e.name as employee_name,
        l.letter_number,
        l.subject as letter_subject,
        d.name as department_name
    FROM activitylog al
    JOIN users u ON al.user_id = u.id
    JOIN employee e ON u.employee_id = e.id
    LEFT JOIN letter l ON al.letter_id = l.id
    LEFT JOIN department d ON COALESCE(l.department_id, e.department_id) = d.id
";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY al.timestamp DESC LIMIT 500";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Get available actions for filter (exclude invalid actions)
$actionsQuery = "SELECT DISTINCT action FROM activitylog WHERE action IS NOT NULL AND action != '' AND action != '0' AND action != 'unknown_action' ORDER BY action";
$actionsResult = $conn->query($actionsQuery);

// Get available users based on role
$usersResult = null;
if ($role === 'institution_head') {
    $usersQuery = "SELECT u.id, u.username, e.name FROM users u JOIN employee e ON u.employee_id = e.id ORDER BY e.name";
    $usersResult = $conn->query($usersQuery);
} elseif ($role === 'department_head') {
    // Department heads see only users from their department
    $usersQuery = "SELECT u.id, u.username, e.name 
                   FROM users u 
                   JOIN employee e ON u.employee_id = e.id 
                   WHERE e.department_id = (SELECT department_id FROM employee WHERE id = ?)
                   ORDER BY e.name";
    $usersStmt = $conn->prepare($usersQuery);
    $usersStmt->bind_param('i', $user_info['employee_id']);
    $usersStmt->execute();
    $usersResult = $usersStmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs - Office Letter Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php echo get_user_info_styles(); ?>
    <style>
        .activity-badge {
            font-size: 0.75em;
        }
        .json-preview {
            max-height: 200px;
            overflow-y: auto;
            font-size: 0.85em;
        }
        .activity-icon {
            width: 20px;
            text-align: center;
        }
        .text-muted {
            color: #4a4a4a !important;
            font-weight: 600 !important;
        }
        .table td, .table th {
            color: #1a1a1a !important;
            font-weight: 600;
        }
        .table thead th {
            color: #fff !important;
            font-weight: 700;
        }
        strong {
            color: #1a1a1a;
            font-weight: 700;
        }
        small {
            font-weight: 600;
        }
        .card-header h6 {
            color: #fff;
            font-weight: 700;
        }
        .form-label {
            color: #1a1a1a;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <!-- User Information -->
    <?php echo display_user_info_compact($user_info); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-history"></i> Activity Logs</h4>
        <div>
            <a href="user_activity_summary.php" class="btn btn-outline-info btn-sm me-2">
                <i class="fas fa-chart-line"></i> User Summary
            </a>
            <span class="badge bg-info"><?php echo $result->num_rows; ?> records</span>
            <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $role)); ?> View</span>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-filter"></i> Filters</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="letter_operations" <?php echo ($_GET['category'] ?? '') === 'letter_operations' ? 'selected' : ''; ?>>📄 Letter Operations</option>
                        <option value="attachments" <?php echo ($_GET['category'] ?? '') === 'attachments' ? 'selected' : ''; ?>>📎 Attachments</option>
                        <option value="authentication" <?php echo ($_GET['category'] ?? '') === 'authentication' ? 'selected' : ''; ?>>🔐 Login/Logout</option>
                        <option value="security" <?php echo ($_GET['category'] ?? '') === 'security' ? 'selected' : ''; ?>>🔒 Security</option>
                        <option value="administration" <?php echo ($_GET['category'] ?? '') === 'administration' ? 'selected' : ''; ?>>⚙️ Admin</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="action" class="form-label">Specific Action</label>
                    <select name="action" id="action" class="form-select">
                        <option value="">All Actions</option>
                        <?php while ($actionRow = $actionsResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($actionRow['action']); ?>" 
                                    <?php echo ($_GET['action'] ?? '') === $actionRow['action'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($actionRow['action']))); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <?php if (($role === 'institution_head' || $role === 'department_head') && $usersResult): ?>
                <div class="col-md-3">
                    <label for="user_id" class="form-label">User</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">All Users <?php echo $role === 'department_head' ? '(in Department)' : ''; ?></option>
                        <?php while ($userRow = $usersResult->fetch_assoc()): ?>
                            <option value="<?php echo $userRow['id']; ?>" 
                                    <?php echo ($_GET['user_id'] ?? '') == $userRow['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($userRow['name'] . ' (' . $userRow['username'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                </div>
                
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
                
                <div class="col-12">
                    <a href="activity_logs.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Activity Log Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="activityTable" class="table table-sm table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Letter</th>
                            <th>Department</th>
                            <th>IP</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $result->data_seek(0); // Reset result pointer
                        while ($row = $result->fetch_assoc()): 
                            $actionIcon = match($row['action']) {
                                'user_login' => 'fas fa-sign-in-alt text-success',
                                'user_logout' => 'fas fa-sign-out-alt text-warning',
                                'letter_created' => 'fas fa-plus-circle text-primary',
                                'letter_updated', 'letter_updated_manual' => 'fas fa-edit text-warning',
                                'letter_deleted' => 'fas fa-trash text-danger',
                                'letter_viewed' => 'fas fa-eye text-info',
                                'letter_assigned' => 'fas fa-user-tag text-info',
                                'status_changed' => 'fas fa-exchange-alt text-warning',
                                'department_changed' => 'fas fa-building text-info',
                                'letters_searched' => 'fas fa-search text-primary',
                                'data_exported' => 'fas fa-file-export text-success',
                                'attachment_added' => 'fas fa-paperclip text-success',
                                'attachment_removed' => 'fas fa-times-circle text-danger',
                                'attachment_replaced' => 'fas fa-sync text-warning',
                                'attachment_viewed' => 'fas fa-eye text-info',
                                'attachment_downloaded' => 'fas fa-download text-primary',
                                'login_failed' => 'fas fa-exclamation-triangle text-danger',
                                'unauthorized_access' => 'fas fa-ban text-danger',
                                'password_changed' => 'fas fa-key text-success',
                                'password_reset_requested' => 'fas fa-unlock text-warning',
                                'password_reset_completed' => 'fas fa-check-circle text-success',
                                'role_changed' => 'fas fa-user-shield text-warning',
                                'configuration_changed' => 'fas fa-cogs text-info',
                                default => 'fas fa-circle text-secondary'
                            };
                            
                            $badgeColor = match($row['action']) {
                                'user_login', 'password_changed', 'password_reset_completed', 'attachment_added', 'data_exported' => 'success',
                                'letter_created', 'letters_searched', 'attachment_downloaded' => 'primary',
                                'letter_updated', 'letter_updated_manual', 'user_logout', 'status_changed', 'attachment_replaced', 'password_reset_requested', 'role_changed' => 'warning',
                                'letter_deleted', 'login_failed', 'unauthorized_access', 'attachment_removed' => 'danger',
                                'letter_viewed', 'letter_assigned', 'attachment_viewed', 'department_changed', 'configuration_changed' => 'info',
                                default => 'secondary'
                            };
                            
                            // Format action name for display
                            $actionDisplay = match($row['action']) {
                                'letter_updated_manual' => 'Letter Updated',
                                'attachment_downloaded' => 'Download',
                                'attachment_viewed' => 'View Attachment',
                                'attachment_added' => 'Attachment Added',
                                'attachment_removed' => 'Attachment Removed',
                                'attachment_replaced' => 'Attachment Replaced',
                                'password_reset_requested' => 'Reset Request',
                                'password_reset_completed' => 'Reset Complete',
                                'letters_searched' => 'Search Letters',
                                'data_exported' => 'Data Export',
                                'configuration_changed' => 'Config Change',
                                default => str_replace('_', ' ', ucwords($row['action'], '_'))
                            };
                        ?>
                        <tr>
                            <td>
                                <small>
                                    <?php echo date('M j, H:i', strtotime($row['timestamp'])); ?><br>
                                    <span class="text-muted"><?php echo date('Y', strtotime($row['timestamp'])); ?></span>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="activity-icon me-2">
                                        <i class="fas fa-user-circle text-muted"></i>
                                    </div>
                                    <div>
                                        <strong class="small"><?php echo htmlspecialchars($row['employee_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="<?php echo $actionIcon; ?> activity-icon me-2"></i>
                                    <span class="badge bg-<?php echo $badgeColor; ?> activity-badge">
                                        <?php echo htmlspecialchars($actionDisplay); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['letter_number']): ?>
                                    <div>
                                        <strong class="small"><?php echo htmlspecialchars($row['letter_number']); ?></strong>
                                        <?php if ($row['letter_subject']): ?>
                                            <br><small class="text-muted">
                                                <?php echo htmlspecialchars(substr($row['letter_subject'], 0, 30)); ?>
                                                <?php echo strlen($row['letter_subject']) > 30 ? '...' : ''; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">N/A</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($row['department_name'] ?: 'System'); ?></small>
                            </td>
                            <td>
                                <small class="font-monospace"><?php echo htmlspecialchars($row['ip_address']); ?></small>
                            </td>
                            <td>
                                <?php if ($row['old_values'] || $row['new_values']): ?>
                                    <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $row['id']; ?>">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Detail Modal -->
                        <?php if ($row['old_values'] || $row['new_values']): ?>
                        <div class="modal fade" id="detailModal<?php echo $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="<?php echo $actionIcon; ?> me-2"></i>
                                            Activity Details
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <strong>User:</strong> <?php echo htmlspecialchars($row['employee_name']); ?>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Time:</strong> <?php echo date('Y-m-d H:i:s', strtotime($row['timestamp'])); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($row['old_values']): ?>
                                        <div class="mb-3">
                                            <h6 class="text-danger"><i class="fas fa-minus-circle"></i> Previous Values:</h6>
                                            <div class="json-preview p-3 rounded" style="background-color: #f8f9fa; color: #1a1a1a; font-weight: 600; border: 1px solid #d0d0d0;">
                                                <pre class="mb-0" style="color: #1a1a1a; font-weight: 600;"><?php echo htmlspecialchars(json_encode(json_decode($row['old_values']), JSON_PRETTY_PRINT)); ?></pre>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['new_values']): ?>
                                        <div class="mb-3">
                                            <h6 class="text-success"><i class="fas fa-plus-circle"></i> New Values:</h6>
                                            <div class="json-preview p-3 rounded" style="background-color: #f8f9fa; color: #1a1a1a; font-weight: 600; border: 1px solid #d0d0d0;">
                                                <pre class="mb-0" style="color: #1a1a1a; font-weight: 600;"><?php echo htmlspecialchars(json_encode(json_decode($row['new_values']), JSON_PRETTY_PRINT)); ?></pre>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <strong>IP Address:</strong><br>
                                                    <span class="font-monospace"><?php echo htmlspecialchars($row['ip_address']); ?></span>
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <strong>User Agent:</strong><br>
                                                    <?php echo htmlspecialchars(substr($row['user_agent'], 0, 80)); ?>
                                                    <?php echo strlen($row['user_agent']) > 80 ? '...' : ''; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#activityTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "Search activities:",
            lengthMenu: "Show _MENU_ activities per page",
            info: "Showing _START_ to _END_ of _TOTAL_ activities",
            infoEmpty: "No activities found",
            infoFiltered: "(filtered from _MAX_ total activities)"
        },
        columnDefs: [
            { targets: [6], orderable: false }, // Details column
            { targets: [5], className: "text-center" }, // IP column
            { targets: [2], className: "text-center" }  // Action column
        ]
    });
    
    // Auto-refresh every 30 seconds if no filters are applied
    <?php if (empty($_GET)): ?>
    setInterval(function() {
        window.location.reload();
    }, 30000);
    <?php endif; ?>
});
</script>

<!-- Footer -->
<?php render_dashboard_footer(); ?>
</body>
</html>