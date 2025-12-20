<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'user_info_helper.php';
require_once 'footer_component.php';

// Get user information
$user_id = intval($_SESSION['user_id']);
$user_info = get_user_info($user_id, $conn);

if (!$user_info) {
    header('Location: index.php');
    exit;
}

$role = $user_info['role'];
$emp_id = $user_info['employee_id'];

// Role-based filtering for summary
$where = '';
$bind_params = [];
$param_types = '';

if ($role === 'department_head') {
    // Department head sees only their department's summary
    $empStmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ? LIMIT 1");
    $empStmt->bind_param('i', $emp_id);
    $empStmt->execute();
    $empRes = $empStmt->get_result();
    if ($empRes && $empRes->num_rows > 0) {
        $deptRow = $empRes->fetch_assoc();
        $dept_id = $deptRow['department_id'];
        $where = "WHERE Letter.department_id = ?";
        $bind_params[] = $dept_id;
        $param_types = 'i';
    }
} elseif ($role === 'subject_officer') {
    // Subject officer sees only their own letters summary
    $where = "WHERE Letter.employee_id = ?";
    $bind_params[] = $emp_id;
    $param_types = 'i';
}
// Institution head sees all departments (no WHERE clause needed)

$sql = "SELECT Department.name AS dept_name, COUNT(*) AS total_letters
        FROM Letter
        JOIN Department ON Letter.department_id = Department.id
        $where
        GROUP BY Department.id, Department.name
        ORDER BY Department.name";
        
if (!empty($bind_params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// --- Letters by Status (Category) ---
$status_sql = "SELECT 
                    LetterStatus.id AS status_id,
                    LetterStatus.status_name,
                    COUNT(*) AS total_letters,
                    Department.name AS dept_name,
                    Department.abbreviation AS dept_abbr
                FROM Letter
                JOIN LetterStatus ON Letter.status_id = LetterStatus.id
                JOIN Department ON Letter.department_id = Department.id
                $where
                GROUP BY LetterStatus.id, LetterStatus.status_name, Department.id, Department.name, Department.abbreviation
                ORDER BY LetterStatus.status_name, Department.name";

if (!empty($bind_params)) {
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param($param_types, ...$bind_params);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
} else {
    $status_result = $conn->query($status_sql);
}

// Organize by status
$letters_by_status = [];
while ($row = $status_result->fetch_assoc()) {
    $status = $row['status_name'];
    if (!isset($letters_by_status[$status])) {
        $letters_by_status[$status] = [];
    }
    $letters_by_status[$status][] = $row;
}

// --- Overall Statistics ---
$total_sql = "SELECT 
                COUNT(*) AS grand_total,
                COUNT(DISTINCT department_id) AS total_departments,
                COUNT(DISTINCT employee_id) AS total_employees
              FROM Letter
              $where";

if (!empty($bind_params)) {
    $total_stmt = $conn->prepare($total_sql);
    $total_stmt->bind_param($param_types, ...$bind_params);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $totals = $total_result->fetch_assoc();
} else {
    $totals = $conn->query($total_sql)->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <?php echo get_user_info_styles(); ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .summary-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .main-card {
            background: rgba(255,255,255,0.98);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2.5rem;
            animation: fadeIn 0.5s ease-in-out;
        }
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .page-header h1 {
            color: #5569d8;
            font-weight: bold;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .page-header p {
            color: #4a4a4a;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .stats-card.role-department_head {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stats-card.role-subject_officer {
            background: linear-gradient(135deg, #fc5c7d 0%, #6a82fb 100%);
        }
        .stats-card.role-institution_head {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .stats-card h6 {
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        .stats-card h3 {
            font-weight: bold;
            margin: 0;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            border: none;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem 1.5rem;
        }
        .card-header h5 {
            margin: 0;
            font-weight: 700;
            color: white !important;
        }
        .card-body {
            background-color: #fff;
            color: #1a1a1a;
        }
        .card-body * {
            color: #1a1a1a;
        }
        .filter-form {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #d0d0d0;
        }
        .filter-form label {
            color: #1a1a1a;
            font-weight: 700;
        }
        .filter-form select,
        .filter-form input {
            color: #1a1a1a;
            font-weight: 600;
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table thead th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .activity-item {
            border-left: 4px solid #667eea;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
            transform: translateY(-2px);
            color: white;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <div class="summary-wrapper">
        <div class="main-card">
            <!-- User Information -->
            <?php echo display_user_info_compact($user_info); ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Letter Summary & Analytics</h1>
                <p>Comprehensive overview of your letter management system</p>
            </div>
            
            <!-- Role-based summary notice -->
            <?php if ($role === 'subject_officer'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Subject Officer Summary:</strong> Viewing letters assigned to you.
                </div>
            <?php elseif ($role === 'department_head'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Department Head Summary:</strong> Viewing all letters in your department.
                </div>
            <?php elseif ($role === 'institution_head'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-info-circle"></i> <strong>Institution Head Summary:</strong> Viewing all letters across all departments.
                </div>
            <?php endif; ?>
            
            <!-- Overall Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card role-<?php echo $role; ?>">
                        <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
                        <h6>Total Letters</h6>
                        <h3><?php echo number_format($totals['grand_total']); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card role-<?php echo $role; ?>">
                        <div class="icon"><i class="fas fa-building"></i></div>
                        <h6>Departments</h6>
                        <h3><?php echo number_format($totals['total_departments']); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card role-<?php echo $role; ?>">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <h6>Assigned Employees</h6>
                        <h3><?php echo number_format($totals['total_employees']); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Letters by Status (Category) -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tasks"></i> Letters by Category (Status)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($letters_by_status) === 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No letters found.
                        </div>
                    <?php else: ?>
                        <?php 
                        $status_colors = [
                            'Pending' => 'warning',
                            'In Progress' => 'info',
                            'Completed' => 'success',
                            'Archived' => 'secondary',
                            'On Hold' => 'danger',
                            'Cancelled' => 'dark'
                        ];
                        
                        foreach ($letters_by_status as $status_name => $departments): 
                            $color = $status_colors[$status_name] ?? 'primary';
                            $total_in_status = array_sum(array_column($departments, 'total_letters'));
                        ?>
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <span class="badge bg-<?php echo $color; ?> me-2"><?php echo htmlspecialchars($status_name); ?></span>
                                <small class="text-muted">(<?php echo $total_in_status; ?> letters)</small>
                            </h6>
                            <div class="row">
                                <?php foreach ($departments as $dept): ?>
                                <div class="col-md-4 col-lg-3 mb-3">
                                    <div class="card h-100 border-<?php echo $color; ?>">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-<?php echo $color; ?>">
                                                <i class="fas fa-sitemap"></i>
                                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                                            </h6>
                                            <p class="card-text">
                                                <span class="badge bg-<?php echo $color; ?> rounded-pill fs-5">
                                                    <?php echo $dept['total_letters']; ?>
                                                </span>
                                            </p>
                                            <small class="text-muted"><?php echo htmlspecialchars($dept['dept_abbr']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Department Letters Summary Table -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-building"></i> Letters by Department (Overview)</h5>
                </div>
                <div class="card-body">
                    <table id="summaryTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-sitemap"></i> Department</th>
                                <th><i class="fas fa-envelope"></i> Total Letters</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result->data_seek(0); // Reset pointer
                            while ($row = $result->fetch_assoc()) { 
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['dept_name']); ?></strong></td>
                                <td><span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($row['total_letters']); ?></span></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        
        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    </div>
    
    <script>
        $(document).ready(function() {
            $('#summaryTable').DataTable({
                "pageLength": 10,
                "order": [[1, "desc"]],
                "language": {
                    "search": "Search departments:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ departments"
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Footer -->
    <?php render_dashboard_footer(); ?>
</body>
</html>