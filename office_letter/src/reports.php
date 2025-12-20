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

// Get filter parameters
$report_type = $_GET['type'] ?? 'letters_summary';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Build query based on role and report type
$reportData = [];
$reportTitle = '';
$reportSubtitle = '';

// LETTERS SUMMARY REPORT
if ($report_type === 'letters_summary') {
    $reportTitle = 'Letters Summary Report';
    $reportSubtitle = "Period: $date_from to $date_to";
    
    $query = "
        SELECT 
            l.id,
            l.letter_number,
            l.subject,
            l.sender,
            l.receiver,
            l.date_received,
            l.date_sent,
            ls.status_name,
            d.name as department_name,
            e.name as assigned_officer,
            m.method_name
        FROM Letter l
        LEFT JOIN LetterStatus ls ON l.status_id = ls.id
        LEFT JOIN Department d ON l.department_id = d.id
        LEFT JOIN Employee e ON l.employee_id = e.id
        LEFT JOIN LetterMethod m ON l.method_id = m.id
        WHERE l.date_received BETWEEN ? AND ?
    ";
    
    // Add role-based filters
    if ($role === 'subject_officer') {
        $query .= " AND l.employee_id = $emp_id";
    } elseif ($role === 'department_head') {
        $query .= " AND l.department_id = $dept_id";
    }
    
    // Add optional filters
    if ($status_filter) {
        $query .= " AND l.status_id = " . intval($status_filter);
    }
    if ($department_filter && in_array($role, ['institution_head', 'chief_management_assistant'])) {
        $query .= " AND l.department_id = " . intval($department_filter);
    }
    
    $query .= " ORDER BY l.date_received DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ss', $date_from, $date_to);
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $reportData = [];
    }
}

// LETTERS BY STATUS REPORT
elseif ($report_type === 'status_report') {
    $reportTitle = 'Letters by Status Report';
    $reportSubtitle = "Period: $date_from to $date_to";
    
    $query = "
        SELECT 
            ls.status_name,
            COUNT(l.id) as letter_count,
            GROUP_CONCAT(DISTINCT d.name SEPARATOR ', ') as departments
        FROM LetterStatus ls
        LEFT JOIN Letter l ON l.status_id = ls.id 
            AND l.date_received BETWEEN ? AND ?
    ";
    
    if ($role === 'subject_officer') {
        $query .= " AND l.employee_id = $emp_id";
    } elseif ($role === 'department_head') {
        $query .= " AND l.department_id = $dept_id";
    }
    
    $query .= "
        LEFT JOIN Department d ON l.department_id = d.id
        GROUP BY ls.id, ls.status_name
        ORDER BY letter_count DESC
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ss', $date_from, $date_to);
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $reportData = [];
    }
}

// DEPARTMENT PERFORMANCE REPORT (Institution Head & Chief Mgmt Assistant only)
elseif ($report_type === 'department_performance' && in_array($role, ['institution_head', 'chief_management_assistant'])) {
    $reportTitle = 'Department Performance Report';
    $reportSubtitle = "Period: $date_from to $date_to";
    
    $query = "
        SELECT 
            d.name as department_name,
            COUNT(l.id) as total_letters,
            SUM(CASE WHEN ls.status_name = 'Completed' THEN 1 ELSE 0 END) as completed_letters,
            SUM(CASE WHEN ls.status_name = 'Pending' THEN 1 ELSE 0 END) as pending_letters,
            SUM(CASE WHEN ls.status_name = 'In Progress' THEN 1 ELSE 0 END) as in_progress_letters,
            COUNT(DISTINCT l.employee_id) as officers_assigned
        FROM Department d
        LEFT JOIN Letter l ON l.department_id = d.id 
            AND l.date_received BETWEEN ? AND ?
        LEFT JOIN LetterStatus ls ON l.status_id = ls.id
        GROUP BY d.id, d.name
        ORDER BY total_letters DESC
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ss', $date_from, $date_to);
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $reportData = [];
    }
}

// USER ACTIVITY REPORT (Institution Head only)
elseif ($report_type === 'user_activity' && $role === 'institution_head') {
    $reportTitle = 'User Activity Report';
    $reportSubtitle = "Period: $date_from to $date_to";
    
    $query = "
        SELECT 
            e.name as employee_name,
            e.role,
            d.name as department_name,
            COUNT(DISTINCT l.id) as letters_assigned,
            COUNT(DISTINCT li.id) as instructions_given,
            u.last_login
        FROM Employee e
        LEFT JOIN Users u ON u.employee_id = e.id
        LEFT JOIN Department d ON e.department_id = d.id
        LEFT JOIN Letter l ON l.employee_id = e.id 
            AND l.date_received BETWEEN ? AND ?
        LEFT JOIN LetterInstructions li ON li.user_id = u.id 
            AND li.created_at BETWEEN ? AND ?
        GROUP BY e.id, e.name, e.role, d.name, u.last_login
        ORDER BY letters_assigned DESC
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $date_from_time = $date_from . ' 00:00:00';
        $date_to_time = $date_to . ' 23:59:59';
        $stmt->bind_param('ssss', $date_from, $date_to, $date_from_time, $date_to_time);
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $reportData = [];
    }
}

// MY PERFORMANCE REPORT (For any user)
elseif ($report_type === 'my_performance') {
    $reportTitle = 'My Performance Report';
    $reportSubtitle = "Period: $date_from to $date_to | Officer: $employee_name";
    
    $query = "
        SELECT 
            l.letter_number,
            l.subject,
            l.date_received,
            l.date_sent,
            ls.status_name,
            DATEDIFF(IFNULL(l.date_sent, CURDATE()), l.date_received) as processing_days,
            (SELECT COUNT(*) FROM LetterInstructions WHERE letter_id = l.id AND user_id = ?) as my_notes_count
        FROM Letter l
        LEFT JOIN LetterStatus ls ON l.status_id = ls.id
        WHERE l.employee_id = ?
            AND l.date_received BETWEEN ? AND ?
        ORDER BY l.date_received DESC
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('iiss', $user_id, $emp_id, $date_from, $date_to);
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $reportData = [];
    }
}

// Get departments and statuses for filters
$departments = $conn->query("SELECT id, name FROM Department ORDER BY name");
$statuses = $conn->query("SELECT id, status_name FROM LetterStatus ORDER BY id");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .reports-container {
            padding: 2rem 0;
        }
        .report-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        /* Print Styles */
        @media print {
            @page {
                size: A4;
                margin: 12mm 15mm;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            body {
                background: white !important;
                margin: 0;
                padding: 0;
                font-size: 10pt;
            }
            
            .no-print {
                display: none !important;
            }
            
            .report-card {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
            
            .container, .reports-container {
                width: 100%;
                max-width: 100%;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Print Header - Keep it very compact and stick with content */
            .print-header {
                margin: 0 0 8mm 0;
                padding: 0;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }
            
            .print-header h2 {
                font-size: 13pt;
                margin: 0 0 1mm 0;
                padding: 0;
                font-weight: bold;
                line-height: 1.2;
            }
            
            .print-header h3 {
                font-size: 11pt;
                margin: 1mm 0;
                padding: 0;
                color: #1a1a1a !important;
                line-height: 1.2;
                font-weight: 700;
            }
            
            .print-header p {
                font-size: 8.5pt;
                margin: 0.5mm 0;
                padding: 0;
                color: #4a4a4a !important;
                line-height: 1.2;
                font-weight: 600;
            }
            
            /* Force content to stay with header */
            .print-header ~ .report-card {
                page-break-before: avoid !important;
            }
            
            /* Table Styles */
            .table-responsive {
                overflow: visible !important;
                page-break-inside: auto;
                margin-top: 0 !important;
            }
            
            .table {
                font-size: 9pt;
                width: 100%;
                border-collapse: collapse;
                page-break-inside: auto;
                margin: 0;
            }
            
            .table thead {
                display: table-header-group;
                background: #333 !important;
            }
            
            .table thead th {
                background: #333 !important;
                color: white !important;
                padding: 2.5mm !important;
                font-size: 9pt;
                border: 1px solid #333 !important;
                font-weight: bold;
            }
            
            .table tbody tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            .table td, .table th {
                padding: 2mm !important;
                border: 1px solid #ccc !important;
                font-size: 8.5pt;
            }
            
            .table tfoot {
                display: table-footer-group;
            }
            
            /* Headings */
            h1, h2, h3, h4, h5, h6 {
                color: #000 !important;
                page-break-after: avoid !important;
                margin-top: 0;
            }
            
            /* Report Section - Keep content together */
            .report-section {
                page-break-before: avoid !important;
                page-break-inside: avoid;
                margin: 0;
            }
            
            /* Badge styling for print */
            .badge {
                border: 1px solid #000 !important;
                padding: 1mm 2mm !important;
                font-size: 7.5pt !important;
                background: transparent !important;
                color: #000 !important;
                display: inline-block;
            }
            
            /* Progress bars - hide in print */
            .progress {
                display: none !important;
            }
            
            /* Stat boxes for print */
            .stat-box {
                border: 2px solid #333 !important;
                background: white !important;
                page-break-inside: avoid;
                padding: 4mm !important;
                margin-bottom: 3mm !important;
            }
            
            .stat-box h3 {
                color: #000 !important;
                font-size: 13pt !important;
            }
            
            .stat-box p {
                color: #000 !important;
                font-size: 9pt !important;
            }
            
            /* Orphan and widow control */
            p, li, td {
                orphans: 3;
                widows: 3;
            }
        }
        
        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #333;
            padding-bottom: 1rem;
        }
        
        @media print {
            .print-header {
                display: block;
            }
        }
        
        .report-filters {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 2rem;
            color: white;
        }
        
        .stat-box p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="reports-container">
    <div class="container">
        
        <!-- Screen Header -->
        <div class="no-print d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white"><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
            <div>
                <button onclick="window.print()" class="btn btn-success me-2">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Report Type Selector -->
        <div class="report-card no-print">
            <h4><i class="fas fa-file-alt"></i> Select Report Type</h4>
            <div class="row mt-3">
                <div class="col-md-3">
                    <a href="reports.php?type=letters_summary" class="btn btn-<?php echo $report_type === 'letters_summary' ? 'primary' : 'outline-primary'; ?> w-100 mb-2">
                        <i class="fas fa-list"></i> Letters Summary
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="reports.php?type=status_report" class="btn btn-<?php echo $report_type === 'status_report' ? 'primary' : 'outline-primary'; ?> w-100 mb-2">
                        <i class="fas fa-chart-pie"></i> By Status
                    </a>
                </div>
                <?php if (in_array($role, ['institution_head', 'chief_management_assistant'])): ?>
                <div class="col-md-3">
                    <a href="reports.php?type=department_performance" class="btn btn-<?php echo $report_type === 'department_performance' ? 'primary' : 'outline-primary'; ?> w-100 mb-2">
                        <i class="fas fa-building"></i> Department Performance
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($role === 'institution_head'): ?>
                <div class="col-md-3">
                    <a href="reports.php?type=user_activity" class="btn btn-<?php echo $report_type === 'user_activity' ? 'primary' : 'outline-primary'; ?> w-100 mb-2">
                        <i class="fas fa-users"></i> User Activity
                    </a>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <a href="reports.php?type=my_performance" class="btn btn-<?php echo $report_type === 'my_performance' ? 'primary' : 'outline-primary'; ?> w-100 mb-2">
                        <i class="fas fa-user-check"></i> My Performance
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="report-card no-print report-filters">
            <form method="GET" class="row g-3">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
                
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                
                <?php if ($report_type === 'letters_summary'): ?>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php while ($s = $statuses->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $status_filter == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['status_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <?php if (in_array($role, ['institution_head', 'chief_management_assistant'])): ?>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php while ($d = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $department_filter == $d['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <div class="report-card">
            <!-- Print Header (Inside report card to stay together) -->
            <div class="print-header">
                <h2><?php echo APP_NAME; ?></h2>
                <h3><?php echo $reportTitle; ?></h3>
                <p><?php echo $reportSubtitle; ?></p>
                <p><strong>Generated by:</strong> <?php echo htmlspecialchars($employee_name); ?> | <strong>Date:</strong> <?php echo date('Y-m-d H:i'); ?></p>
            </div>
            
            <!-- Screen Header -->
            <h3 class="no-print"><?php echo $reportTitle; ?></h3>
            <p class="text-muted no-print"><?php echo $reportSubtitle; ?></p>
            <hr class="no-print">

            <?php if (empty($reportData)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No data found for the selected criteria.
                </div>
            <?php else: ?>

                <?php if ($report_type === 'letters_summary'): ?>
                    <!-- Summary Statistics -->
                    <div class="row mb-4 no-print">
                        <div class="col-md-4">
                            <div class="stat-box">
                                <h3><?php echo count($reportData); ?></h3>
                                <p>Total Letters</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box">
                                <h3><?php echo count(array_filter($reportData, fn($r) => $r['status_name'] === 'Completed')); ?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-box">
                                <h3><?php echo count(array_filter($reportData, fn($r) => in_array($r['status_name'], ['Pending', 'In Progress']))); ?></h3>
                                <p>In Progress</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-section">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                    <th>#</th>
                                    <th>Letter Number</th>
                                    <th>Subject</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Date Received</th>
                                    <th>Status</th>
                                    <th>Department</th>
                                    <th>Assigned To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $i => $row): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['letter_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sender']); ?></td>
                                    <td><?php echo htmlspecialchars($row['receiver']); ?></td>
                                    <td><?php echo ($row['date_received'] && $row['date_received'] != '0000-00-00') ? date('Y-m-d', strtotime($row['date_received'])) : 'N/A'; ?></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['status_name']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['assigned_officer'] ?? 'Unassigned'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>

                <?php elseif ($report_type === 'status_report'): ?>
                    <div class="report-section">
                        <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Status</th>
                                    <th>Letter Count</th>
                                    <th>Departments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = array_sum(array_column($reportData, 'letter_count'));
                                foreach ($reportData as $row): 
                                    $percentage = $total > 0 ? round(($row['letter_count'] / $total) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['status_name']); ?></strong></td>
                                    <td>
                                        <?php echo $row['letter_count']; ?> (<?php echo $percentage; ?>%)
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['departments'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-secondary">
                                    <td><strong>TOTAL</strong></td>
                                    <td colspan="2"><strong><?php echo $total; ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                <?php elseif ($report_type === 'department_performance'): ?>
                    <div class="report-section">
                        <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Department</th>
                                    <th>Total Letters</th>
                                    <th>Completed</th>
                                    <th>In Progress</th>
                                    <th>Pending</th>
                                    <th>Officers</th>
                                    <th>Completion Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): 
                                    $completion_rate = $row['total_letters'] > 0 ? round(($row['completed_letters'] / $row['total_letters']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['department_name']); ?></strong></td>
                                    <td><?php echo $row['total_letters']; ?></td>
                                    <td><?php echo $row['completed_letters']; ?></td>
                                    <td><?php echo $row['in_progress_letters']; ?></td>
                                    <td><?php echo $row['pending_letters']; ?></td>
                                    <td><?php echo $row['officers_assigned']; ?></td>
                                    <td>
                                        <?php echo $completion_rate; ?>%
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_rate; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>

                <?php elseif ($report_type === 'user_activity'): ?>
                    <div class="report-section">
                        <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Letters Assigned</th>
                                    <th>Instructions Given</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $row['role'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $row['letters_assigned']; ?></td>
                                    <td><?php echo $row['instructions_given']; ?></td>
                                    <td><?php echo isset($row['last_login']) && $row['last_login'] ? date('Y-m-d H:i', strtotime($row['last_login'])) : 'Never'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>

                <?php elseif ($report_type === 'my_performance'): ?>
                    <div class="row mb-4 no-print">
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h3><?php echo count($reportData); ?></h3>
                                <p>Total Letters</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h3><?php echo count(array_filter($reportData, fn($r) => $r['status_name'] === 'Completed')); ?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h3><?php 
                                    $avg_days = count($reportData) > 0 ? round(array_sum(array_column($reportData, 'processing_days')) / count($reportData), 1) : 0;
                                    echo $avg_days;
                                ?></h3>
                                <p>Avg. Processing Days</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h3><?php echo array_sum(array_column($reportData, 'my_notes_count')); ?></h3>
                                <p>Total Notes Added</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-section">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Letter Number</th>
                                    <th>Subject</th>
                                    <th>Date Received</th>
                                    <th>Date Sent</th>
                                    <th>Processing Days</th>
                                    <th>Status</th>
                                    <th>My Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $i => $row): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['letter_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo ($row['date_received'] && $row['date_received'] != '0000-00-00') ? date('Y-m-d', strtotime($row['date_received'])) : 'N/A'; ?></td>
                                    <td><?php echo ($row['date_sent'] && $row['date_sent'] != '0000-00-00') ? date('Y-m-d', strtotime($row['date_sent'])) : 'Pending'; ?></td>
                                    <td><?php echo $row['processing_days']; ?> days</td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['status_name']); ?></span></td>
                                    <td><?php echo $row['my_notes_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
