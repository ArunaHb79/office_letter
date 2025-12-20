<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'user_info_helper.php';
require_once 'activity_logger.php';
require_once 'footer_component.php';

// Helper function to get users in department
function getUsersInDepartment($conn, $employee_id) {
    $stmt = $conn->prepare("
        SELECT u.id 
        FROM users u 
        JOIN employee e ON u.employee_id = e.id 
        WHERE e.department_id = (SELECT department_id FROM employee WHERE id = ?)
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_ids = [];
    while ($row = $result->fetch_assoc()) {
        $user_ids[] = $row['id'];
    }
    return $user_ids;
}

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

// Date filter setup
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Default to current month start
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Default to today
$selected_user_id = $_GET['user_id'] ?? '';

// Build base query conditions for role-based filtering
$where_conditions = [];
$params = [];
$param_types = '';

// Date filtering - always first
$where_conditions[] = "DATE(al.timestamp) BETWEEN ? AND ?";
$params[] = $date_from;
$params[] = $date_to;
$param_types .= 'ss';

// Role-based filtering
if ($role === 'department_head') {
    // Department heads can only see users from their department
    $where_conditions[] = "e.department_id = (SELECT department_id FROM employee WHERE id = ?)";
    $params[] = $user_info['employee_id'];
    $param_types .= 'i';
}

// Specific user filtering
if (!empty($selected_user_id) && ($role === 'institution_head' || 
    ($role === 'department_head' && in_array($selected_user_id, getUsersInDepartment($conn, $user_info['employee_id']))))) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = intval($selected_user_id);
    $param_types .= 'i';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get user activity summary
$summary_sql = "
    SELECT 
        u.id as user_id,
        u.username,
        e.name as employee_name,
        u.role,
        d.name as department_name,
        COUNT(al.id) as total_activities,
        COUNT(CASE WHEN al.action LIKE '%login%' OR al.action LIKE '%logout%' THEN 1 END) as login_activities,
        COUNT(CASE WHEN al.action LIKE '%letter%' AND al.action NOT LIKE '%attachment%' THEN 1 END) as letter_activities,
        COUNT(CASE WHEN al.action LIKE '%attachment%' THEN 1 END) as attachment_activities,
        COUNT(CASE WHEN al.action IN ('letter_created', 'letter_updated', 'letter_updated_manual') THEN 1 END) as letter_modifications,
        COUNT(CASE WHEN al.action = 'letters_searched' THEN 1 END) as search_activities,
        COUNT(CASE WHEN al.action = 'login_failed' THEN 1 END) as failed_logins,
        COUNT(CASE WHEN al.action IN ('password_changed', 'unauthorized_access') THEN 1 END) as security_events,
        MAX(al.timestamp) as last_activity,
        MIN(al.timestamp) as first_activity
    FROM users u
    JOIN employee e ON u.employee_id = e.id
    JOIN department d ON e.department_id = d.id
    LEFT JOIN activitylog al ON u.id = al.user_id 
    $where_clause
    GROUP BY u.id, u.username, e.name, u.role, d.name
    ORDER BY total_activities DESC, e.name ASC
";

// Prepare parameters for summary query
$summary_params = $params; // Use the already built params array
$summary_param_types = $param_types; // Use the already built param types

$summary_stmt = $conn->prepare($summary_sql);
if ($summary_stmt === false) {
    die("Error preparing summary query: " . $conn->error);
}
$summary_stmt->bind_param($summary_param_types, ...$summary_params);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();

// Get activity type breakdown
$activity_breakdown_sql = "
    SELECT 
        al.action,
        COUNT(*) as count,
        u.username,
        e.name as employee_name
    FROM activitylog al
    JOIN users u ON al.user_id = u.id
    JOIN employee e ON u.employee_id = e.id
    WHERE DATE(al.timestamp) BETWEEN ? AND ?
";

// Add role-based filtering for breakdown query
if ($role === 'department_head') {
    $activity_breakdown_sql .= " AND e.department_id = (SELECT department_id FROM employee WHERE id = ?)";
}

// Add specific user filtering for breakdown query
if (!empty($selected_user_id) && ($role === 'institution_head' || 
    ($role === 'department_head' && in_array($selected_user_id, getUsersInDepartment($conn, $user_info['employee_id']))))) {
    $activity_breakdown_sql .= " AND al.user_id = ?";
}

$activity_breakdown_sql .= "
    GROUP BY al.action, u.id, u.username, e.name
    ORDER BY count DESC, al.action ASC
";

$breakdown_params = [$date_from, $date_to];
$breakdown_param_types = 'ss';

// Add parameters for role-based filtering
if ($role === 'department_head') {
    $breakdown_params[] = $user_info['employee_id'];
    $breakdown_param_types .= 'i';
}

// Add parameter for specific user filtering
if (!empty($selected_user_id) && ($role === 'institution_head' || 
    ($role === 'department_head' && in_array($selected_user_id, getUsersInDepartment($conn, $user_info['employee_id']))))) {
    $breakdown_params[] = intval($selected_user_id);
    $breakdown_param_types .= 'i';
}

$breakdown_stmt = $conn->prepare($activity_breakdown_sql);
if ($breakdown_stmt === false) {
    die("Error preparing breakdown query: " . $conn->error);
}
$breakdown_stmt->bind_param($breakdown_param_types, ...$breakdown_params);
$breakdown_stmt->execute();
$breakdown_result = $breakdown_stmt->get_result();

// Get available users for filtering
$users_sql = "
    SELECT u.id, u.username, e.name as employee_name, d.name as department_name
    FROM users u
    JOIN employee e ON u.employee_id = e.id
    JOIN department d ON e.department_id = d.id
";

if ($role === 'department_head') {
    $users_sql .= " WHERE e.department_id = (SELECT department_id FROM employee WHERE id = ?)";
    $users_stmt = $conn->prepare($users_sql);
    $users_stmt->bind_param("i", $user_info['employee_id']);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
} else {
    $users_result = $conn->query($users_sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Activity Summary - Office Letter Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <?php echo get_user_info_styles(); ?>
    <style>
        .summary-card {
            transition: transform 0.2s ease-in-out;
        }
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .activity-badge {
            font-size: 0.75em;
        }
        .chart-container {
            position: relative;
            height: 300px;
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
        .badge.bg-light {
            background-color: #e9ecef !important;
            color: #1a1a1a !important;
            font-weight: 700;
            border: 1px solid #d0d0d0;
        }
        .badge.text-dark {
            color: #1a1a1a !important;
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
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
        }
        .stats-label {
            font-size: 0.9rem;
            color: #fff;
            font-weight: 700;
            opacity: 0.95;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <!-- User Information -->
    <?php echo display_user_info_compact($user_info); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-chart-line"></i> User Activity Summary</h4>
        <div>
            <a href="activity_logs.php" class="btn btn-outline-primary btn-sm me-2">
                <i class="fas fa-list"></i> View Logs
            </a>
            <span class="badge bg-info">Period: <?php echo $date_from; ?> to <?php echo $date_to; ?></span>
            <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $role)); ?> View</span>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-filter"></i> Summary Filters</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($date_from); ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($date_to); ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label for="user_id" class="form-label">Specific User (Optional)</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">All Users</option>
                        <?php while ($user_row = $users_result->fetch_assoc()): ?>
                            <option value="<?php echo $user_row['id']; ?>" 
                                    <?php echo $selected_user_id == $user_row['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user_row['employee_name'] . ' (' . $user_row['username'] . ') - ' . $user_row['department_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Generate Summary
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Overall Statistics -->
    <div class="row mb-4">
        <?php
        $total_users = $summary_result->num_rows;
        $active_users = 0;
        $total_activities = 0;
        $total_failed_logins = 0;
        
        // Calculate totals
        $summary_result->data_seek(0);
        while ($row = $summary_result->fetch_assoc()) {
            if ($row['total_activities'] > 0) $active_users++;
            $total_activities += $row['total_activities'];
            $total_failed_logins += $row['failed_logins'];
        }
        $summary_result->data_seek(0); // Reset pointer
        ?>
        
        <div class="col-md-3">
            <div class="card summary-card bg-primary text-white">
                <div class="card-body text-center">
                    <div class="stats-number"><?php echo $total_users; ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card summary-card bg-success text-white">
                <div class="card-body text-center">
                    <div class="stats-number"><?php echo $active_users; ?></div>
                    <div class="stats-label">Active Users</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card summary-card bg-info text-white">
                <div class="card-body text-center">
                    <div class="stats-number"><?php echo $total_activities; ?></div>
                    <div class="stats-label">Total Activities</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card summary-card bg-warning text-white">
                <div class="card-body text-center">
                    <div class="stats-number"><?php echo $total_failed_logins; ?></div>
                    <div class="stats-label">Failed Logins</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- User Activity Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie"></i> User Activity Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="userActivityChart"></canvas>
                    </div>
                    <div id="userActivityFallback" style="display:none;" class="text-center p-4">
                        <div class="alert alert-info">
                            <h6>User Activity Summary</h6>
                            <p id="activitySummaryText"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Summary Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-users"></i> Detailed User Activity Summary</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="userSummaryTable">
                    <thead class="table-dark">
                        <tr>
                            <th>User</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Total</th>
                            <th class="text-center" title="Authentication Activities"><i class="fas fa-sign-in-alt"></i></th>
                            <th class="text-center" title="Letter Activities"><i class="fas fa-envelope"></i></th>
                            <th class="text-center" title="Attachment Activities"><i class="fas fa-paperclip"></i></th>
                            <th class="text-center" title="Search Activities"><i class="fas fa-search"></i></th>
                            <th class="text-center" title="Failed Logins"><i class="fas fa-exclamation-triangle"></i></th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $summary_result->data_seek(0);
                        while ($row = $summary_result->fetch_assoc()): 
                            $activity_level = '';
                            $badge_color = '';
                            if ($row['total_activities'] == 0) {
                                $activity_level = 'Inactive';
                                $badge_color = 'secondary';
                            } elseif ($row['total_activities'] < 5) {
                                $activity_level = 'Low';
                                $badge_color = 'warning';
                            } elseif ($row['total_activities'] < 20) {
                                $activity_level = 'Medium';
                                $badge_color = 'info';
                            } else {
                                $activity_level = 'High';
                                $badge_color = 'success';
                            }
                            
                            $role_color = match($row['role']) {
                                'institution_head' => 'danger',
                                'department_head' => 'warning', 
                                'subject_officer' => 'primary',
                                default => 'secondary'
                            };
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['employee_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?php echo htmlspecialchars($row['department_name']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $role_color; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($row['role']))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $badge_color; ?> me-2">
                                    <?php echo $row['total_activities']; ?>
                                </span>
                                <small class="text-muted"><?php echo $activity_level; ?></small>
                            </td>
                            <td class="text-center">
                                <?php if ($row['login_activities'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $row['login_activities']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['letter_activities'] > 0): ?>
                                    <span class="badge bg-primary"><?php echo $row['letter_activities']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['attachment_activities'] > 0): ?>
                                    <span class="badge bg-info"><?php echo $row['attachment_activities']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['search_activities'] > 0): ?>
                                    <span class="badge bg-secondary"><?php echo $row['search_activities']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['failed_logins'] > 0): ?>
                                    <span class="badge bg-danger"><?php echo $row['failed_logins']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['last_activity']): ?>
                                    <small>
                                        <?php echo date('M j, H:i', strtotime($row['last_activity'])); ?><br>
                                        <span class="text-muted"><?php echo date('Y', strtotime($row['last_activity'])); ?></span>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">No activity</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="activity_logs.php?user_id=<?php echo $row['user_id']; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                                       class="btn btn-outline-primary" title="View User Activities">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <button class="btn btn-outline-info" onclick="showUserDetails(<?php echo $row['user_id']; ?>)" title="User Details">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="mt-4">
        <a href="activity_logs.php" class="btn btn-outline-primary">
            <i class="fas fa-list"></i> View Detailed Activity Logs
        </a>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library not loaded');
        $('#userActivityChart').hide();
        $('#userActivityFallback').show();
        $('#activitySummaryText').text('Chart.js library failed to load');
        $('#activityTypeChart').hide();
        $('#activityTypeFallback').show();
        $('#activityTypesSummaryText').text('Chart.js library failed to load');
        return;
    }
    
    // User Activity Chart Data (safe generation)
    <?php
    // Generate chart data safely
    $summary_result->data_seek(0);
    $safe_chart_labels = [];
    $safe_chart_data = [];
    $safe_chart_colors = [];
    $color_palette = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#C9CBCF'];
    $color_index = 0;
    
    while ($row = $summary_result->fetch_assoc()) {
        if ($row['total_activities'] > 0) {
            $safe_chart_labels[] = $row['employee_name'];
            $safe_chart_data[] = intval($row['total_activities']);
            $safe_chart_colors[] = $color_palette[$color_index % count($color_palette)];
            $color_index++;
        }
    }
    
    // Fallback if no data
    if (empty($safe_chart_labels)) {
        $safe_chart_labels[] = 'No Activity';
        $safe_chart_data[] = 1;
        $safe_chart_colors[] = '#E7E9ED';
    }
    ?>
    
    var userActivityData = {
        labels: <?php echo json_encode($safe_chart_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($safe_chart_data); ?>,
            backgroundColor: <?php echo json_encode($safe_chart_colors); ?>,
            borderWidth: 1
        }]
    };
    
    // Activity Type Chart Data (safe generation)
    <?php
    $breakdown_result->data_seek(0);
    $safe_type_counts = [];
    
    while ($row = $breakdown_result->fetch_assoc()) {
        $action = $row['action'];
        
        // Skip invalid actions like '0', 'unknown_action', NULL, or empty (case-insensitive)
        if (empty($action) || $action === '0' || strtolower($action) === 'unknown_action' || strtolower($action) === 'system_action') {
            continue;
        }
        
        if (!isset($safe_type_counts[$action])) {
            $safe_type_counts[$action] = 0;
        }
        $safe_type_counts[$action] += intval($row['count']);
    }
    
    if (empty($safe_type_counts)) {
        $safe_type_counts['no_activity'] = 1;
    }
    
    $safe_type_labels = array_map(function($action) {
        return str_replace('_', ' ', ucfirst($action));
    }, array_keys($safe_type_counts));
    
    $safe_type_values = array_values($safe_type_counts);
    ?>
    
    <?php
    // Prepare categorized data for chart
    $chart_labels = [];
    $chart_values = [];
    $chart_colors = [];
    
    $color_map = [
        'Authentication' => 'rgba(102, 126, 234, 0.8)',
        'Letter Management' => 'rgba(40, 167, 69, 0.8)',
        'Attachments' => 'rgba(23, 162, 184, 0.8)',
        'Status Updates' => 'rgba(255, 193, 7, 0.8)',
        'Search & View' => 'rgba(111, 66, 193, 0.8)',
        'Security' => 'rgba(220, 53, 69, 0.8)',
        'Other' => 'rgba(108, 117, 125, 0.8)'
    ];
    
    foreach ($categorized_counts as $category => $count) {
        if ($count > 0) {
            $chart_labels[] = $category;
            $chart_values[] = $count;
            $chart_colors[] = $color_map[$category] ?? 'rgba(108, 117, 125, 0.8)';
        }
    }
    ?>
    
    // Create User Activity Chart
    try {
        var canvasElement = document.getElementById('userActivityChart');
        if (canvasElement) {
            var userActivityChart = new Chart(canvasElement, {
                type: 'pie',
                data: userActivityData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' activities (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
            console.log('User Activity Chart created successfully');
        } else {
            console.error('Canvas element userActivityChart not found');
        }
    } catch (error) {
        console.error('Error creating User Activity Chart:', error);
        $('#userActivityChart').hide();
        $('#userActivityFallback').show();
        $('#activitySummaryText').text('Chart could not be loaded');
    }
});

function showUserDetails(userId) {
    var dateFrom = '<?php echo addslashes($date_from); ?>';
    var dateTo = '<?php echo addslashes($date_to); ?>';
    window.location.href = 'activity_logs.php?user_id=' + userId + '&date_from=' + dateFrom + '&date_to=' + dateTo;
}
</script>

<!-- Footer -->
<?php render_dashboard_footer(); ?>
</body>
</html>