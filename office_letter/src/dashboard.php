<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'footer_component.php';

// Get user information including name, role, and department
$user_id = intval($_SESSION['user_id']);

// Get user details with department information
$userStmt = $conn->prepare("
    SELECT 
        u.role,
        e.name as employee_name,
        d.name as department_name,
        d.abbreviation as department_abbr
    FROM Users u
    LEFT JOIN Employee e ON u.employee_id = e.id
    LEFT JOIN Department d ON e.department_id = d.id
    WHERE u.id = ? 
    LIMIT 1
");
$userStmt->bind_param('i', $user_id);
$userStmt->execute();
$userRes = $userStmt->get_result();

if ($userRow = $userRes->fetch_assoc()) {
    $role = strtolower(str_replace(' ', '_', $userRow['role']));
    $employee_name = $userRow['employee_name'] ?? 'Unknown User';
    $department_name = $userRow['department_name'] ?? 'No Department';
    $department_abbr = $userRow['department_abbr'] ?? '';
    $role_display = ucwords(str_replace('_', ' ', $userRow['role']));
} else {
    header('Location: index.php');
    exit();
}

// Store in session for use in other pages
$_SESSION['employee_name'] = $employee_name;
$_SESSION['department_name'] = $department_name;
$_SESSION['role_display'] = $role_display;

// Get employee ID for letter statistics
$empIdStmt = $conn->prepare("SELECT employee_id FROM Users WHERE id = ? LIMIT 1");
$empIdStmt->bind_param('i', $user_id);
$empIdStmt->execute();
$empIdRes = $empIdStmt->get_result();
$emp_id = null;
if ($empIdRow = $empIdRes->fetch_assoc()) {
    $emp_id = $empIdRow['employee_id'];
}

// Get department ID if needed
$dept_id = null;
if ($emp_id) {
    $deptStmt = $conn->prepare("SELECT department_id FROM Employee WHERE id = ? LIMIT 1");
    $deptStmt->bind_param('i', $emp_id);
    $deptStmt->execute();
    $deptRes = $deptStmt->get_result();
    if ($deptRow = $deptRes->fetch_assoc()) {
        $dept_id = $deptRow['department_id'];
    }
}

// Build query based on role
$letterStats = [];
$totalLetters = 0;
$statsQuery = "";

if ($role === 'institution_head' || $role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
    // Institution head and postal officer see all letters
    $statsQuery = "
        SELECT 
            ls.status_name,
            ls.id as status_id,
            COUNT(l.id) as count
        FROM LetterStatus ls
        LEFT JOIN Letter l ON l.status_id = ls.id
        GROUP BY ls.id, ls.status_name
        ORDER BY 
            CASE ls.status_name
                WHEN 'Pending' THEN 1
                WHEN 'In Progress' THEN 2
                WHEN 'Under Review' THEN 3
                WHEN 'Approved' THEN 4
                WHEN 'Completed' THEN 5
                WHEN 'On Hold' THEN 6
                WHEN 'Rejected' THEN 7
                WHEN 'Cancelled' THEN 8
                WHEN 'Archived' THEN 9
                ELSE 10
            END
    ";
    $statsResult = $conn->query($statsQuery);
    
} elseif ($role === 'department_head' && $dept_id) {
    // Department head sees all letters in their department
    $statsQuery = "
        SELECT 
            ls.status_name,
            ls.id as status_id,
            COUNT(l.id) as count
        FROM LetterStatus ls
        LEFT JOIN Letter l ON l.status_id = ls.id AND l.department_id = ?
        GROUP BY ls.id, ls.status_name
        ORDER BY 
            CASE ls.status_name
                WHEN 'Pending' THEN 1
                WHEN 'In Progress' THEN 2
                WHEN 'Under Review' THEN 3
                WHEN 'Approved' THEN 4
                WHEN 'Completed' THEN 5
                WHEN 'On Hold' THEN 6
                WHEN 'Rejected' THEN 7
                WHEN 'Cancelled' THEN 8
                WHEN 'Archived' THEN 9
                ELSE 10
            END
    ";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bind_param('i', $dept_id);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    
} elseif ($emp_id) {
    // Subject officer sees only their assigned letters
    $statsQuery = "
        SELECT 
            ls.status_name,
            ls.id as status_id,
            COUNT(l.id) as count
        FROM LetterStatus ls
        LEFT JOIN Letter l ON l.status_id = ls.id AND l.employee_id = ?
        GROUP BY ls.id, ls.status_name
        ORDER BY 
            CASE ls.status_name
                WHEN 'Pending' THEN 1
                WHEN 'In Progress' THEN 2
                WHEN 'Under Review' THEN 3
                WHEN 'Approved' THEN 4
                WHEN 'Completed' THEN 5
                WHEN 'On Hold' THEN 6
                WHEN 'Rejected' THEN 7
                WHEN 'Cancelled' THEN 8
                WHEN 'Archived' THEN 9
                ELSE 10
            END
    ";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bind_param('i', $emp_id);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
}

if (isset($statsResult)) {
    while ($stat = $statsResult->fetch_assoc()) {
        $letterStats[] = $stat;
        $totalLetters += $stat['count'];
    }
}

// Get unassigned letters count for Institution Head
$unassignedCount = 0;
if ($role === 'institution_head') {
    $unassignedQuery = "
        SELECT COUNT(*) as unassigned_count 
        FROM Letter 
        WHERE employee_id IS NULL 
        AND status_id IN (
            SELECT id FROM LetterStatus 
            WHERE status_name IN ('Pending', 'Received')
        )
    ";
    $unassignedResult = $conn->query($unassignedQuery);
    if ($unassignedResult && $unassignedRow = $unassignedResult->fetch_assoc()) {
        $unassignedCount = $unassignedRow['unassigned_count'];
    }
}

// Map statuses to categories
$categories = [
    'Received' => 0,
    'Pending' => 0,
    'Processed' => 0,
    'Completed' => 0
];

// Track individual status counts for breakdown display
$processedBreakdown = [
    'in_progress' => 0,
    'under_review' => 0,
    'processing' => 0
];

foreach ($letterStats as $stat) {
    $status = strtolower($stat['status_name']);
    $count = $stat['count'];
    
    // Map statuses to categories
    if (in_array($status, ['pending', 'new', 'submitted', 'assigned', 'received'])) {
        $categories['Pending'] += $count;
    } elseif (in_array($status, ['in progress', 'under review', 'processing', 'on hold', 'approved'])) {
        $categories['Processed'] += $count;
        
        // Track breakdown for display
        if ($status === 'in progress') {
            $processedBreakdown['in_progress'] += $count;
        } elseif ($status === 'under review') {
            $processedBreakdown['under_review'] += $count;
        } elseif ($status === 'processing') {
            $processedBreakdown['processing'] += $count;
        }
    } elseif (in_array($status, ['completed', 'closed', 'done', 'filed - information only', 'filed', 'rejected', 'cancelled'])) {
        $categories['Completed'] += $count;
    } else {
        $categories['Received'] += $count;
    }
}

// Calculate total letters: Received + Pending + Processed + Completed
$totalLetters = $categories['Received'] + $categories['Pending'] + $categories['Processed'] + $categories['Completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-wrapper {
            padding: 2rem 0;
        }
        .main-card {
            background: rgba(255,255,255,0.98);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2rem;
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
        .user-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .user-info-card.role-department_head {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .user-info-card.role-subject_officer {
            background: linear-gradient(135deg, #fc5c7d 0%, #6a82fb 100%);
        }
        .user-info-card.role-institution_head {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .user-info-card h3 {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .user-info-card .badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .nav-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 100%;
            text-decoration: none;
            display: block;
            border: 2px solid transparent;
        }
        .nav-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
            border-color: #667eea;
        }
        .nav-card .icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .nav-card h5 {
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .nav-card p {
            color: #4a4a4a;
            font-size: 0.9rem;
            margin: 0;
            font-weight: 600;
        }
        .quick-stats {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        .stat-item .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #5569d8;
        }
        .stat-item .stat-label {
            color: #4a4a4a;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .my-letters-section {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .my-letters-section h4 {
            color: #5569d8;
            font-weight: bold;
            margin-bottom: 1.5rem;
            border-bottom: 3px solid #5569d8;
            padding-bottom: 0.5rem;
        }
        .status-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
            height: 100%;
        }
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        a .status-card:hover {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-left-width: 8px;
        }
        .status-card.pending { border-left-color: #ffc107; }
        .status-card.in-progress { border-left-color: #17a2b8; }
        .status-card.under-review { border-left-color: #6f42c1; }
        .status-card.approved { border-left-color: #28a745; }
        .status-card.rejected { border-left-color: #dc3545; }
        .status-card.on-hold { border-left-color: #fd7e14; }
        .status-card.completed { border-left-color: #20c997; }
        .status-card.archived { border-left-color: #6c757d; }
        .status-card.cancelled { border-left-color: #e83e8c; }
        .status-card h5 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }
        .status-card .count {
            font-size: 2rem;
            font-weight: bold;
            color: #5569d8;
        }
        .total-letters {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .total-letters .number {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .total-letters .label {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <div class="container-fluid px-4" style="max-width: 1600px;">
            <div class="main-card">
                <!-- User Information Card -->
                <div class="user-info-card role-<?php echo $role; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($employee_name); ?></h3>
                            <p class="mb-2">
                                <i class="fas fa-briefcase"></i> 
                                <strong><?php echo htmlspecialchars($role_display); ?></strong>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-building"></i> 
                                <?php echo htmlspecialchars($department_name); ?>
                                <?php if ($department_abbr): ?>
                                    <span class="badge ms-2" style="background-color: rgba(255,255,255,0.95); color: #1a1a1a; font-weight: 700; border: 2px solid rgba(0,0,0,0.1);"><?php echo htmlspecialchars($department_abbr); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <p class="mb-1"><i class="fas fa-calendar"></i> <?php echo date('F d, Y'); ?></p>
                            <p class="mb-0"><i class="fas fa-clock"></i> <?php echo date('h:i A'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-th-large"></i> Dashboard</h1>
                    <p>Welcome to <?php echo APP_NAME; ?></p>
                </div>

                <!-- Unassigned Letters Alert for Institution Head -->
                <?php if ($role === 'institution_head' && $unassignedCount > 0): ?>
                <div class="alert alert-warning border-start border-warning border-5 mb-4" style="background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%); box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle text-warning"></i> 
                                Unassigned Letters Awaiting Your Action
                            </h4>
                            <p class="mb-2">
                                <strong style="font-size: 1.5rem; color: #ff6b6b;">
                                    <?php echo $unassignedCount; ?>
                                </strong> 
                                <?php echo $unassignedCount === 1 ? 'letter' : 'letters'; ?> 
                                entered by Chief Management Assistant need to be assigned to subject officers.
                            </p>
                            <small style="color: #4a4a4a; font-weight: 600;">
                                <i class="fas fa-info-circle"></i> These letters are pending and have not been assigned to any officer yet.
                            </small>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <a href="letters.php?filter=unassigned" class="btn btn-warning btn-lg">
                                <i class="fas fa-user-plus"></i> Assign Letters Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php elseif ($role === 'institution_head' && $unassignedCount === 0): ?>
                <div class="alert alert-success border-start border-success border-5 mb-4" style="background: linear-gradient(135deg, #d4edda 0%, #e8f5e9 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle text-success"></i> 
                        All letters have been assigned. Great work!
                    </h5>
                </div>
                <?php endif; ?>

                <!-- My Letters Summary Section -->
                <div class="my-letters-section">
                    <h4><i class="fas fa-envelope-open-text"></i> 
                        <?php 
                        if ($role === 'institution_head') {
                            echo 'All Letters Overview';
                        } elseif ($role === 'department_head') {
                            echo 'Department Letters Overview';
                        } elseif ($role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
                            echo 'All Letters (Postal Overview)';
                        } else {
                            echo 'My Letters Summary';
                        }
                        ?>
                    </h4>
                    
                    <!-- Total Letters -->
                    <div class="total-letters">
                        <div class="number"><?php echo $totalLetters; ?></div>
                        <div class="label">
                            <?php 
                            if ($role === 'institution_head') {
                                echo 'Total Letters in System';
                            } elseif ($role === 'department_head') {
                                echo 'Total Letters in Department';
                            } elseif ($role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
                                echo 'Total Letters in System';
                            } else {
                                echo 'Total Letters Assigned to Me';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Letters by Category (Detailed Status Breakdown) -->
                    <?php
                    // Role-based header and scope description
                    $scopeTitle = '';
                    $scopeDescription = '';
                    $scopeIcon = 'fa-layer-group';
                    $allowedStatuses = []; // Statuses this role can see
                    
                    if ($role === 'institution_head') {
                        $scopeTitle = 'All Letters by Status - Institution Wide';
                        $scopeDescription = 'Complete overview of all letters across all departments';
                        $scopeIcon = 'fa-building';
                        $allowedStatuses = ['pending', 'assigned', 'in progress', 'under review', 'approved', 'completed', 'on hold', 'rejected', 'cancelled', 'archived', 'filed - information only'];
                    } elseif ($role === 'postal_subject_officer') {
                        $scopeTitle = 'All Letters by Status - Postal View';
                        $scopeDescription = 'All incoming and outgoing correspondence';
                        $scopeIcon = 'fa-mail-bulk';
                        $allowedStatuses = ['pending', 'assigned', 'in progress', 'completed', 'approved', 'archived', 'filed - information only'];
                    } elseif ($role === 'chief_management_assistant') {
                        $scopeTitle = 'All Letters by Status - Management View';
                        $scopeDescription = 'System-wide letter tracking and coordination';
                        $scopeIcon = 'fa-clipboard-list';
                        $allowedStatuses = ['pending', 'assigned', 'in progress', 'under review', 'completed', 'approved', 'on hold', 'filed - information only'];
                    } elseif ($role === 'department_head') {
                        $scopeTitle = 'My Department Letters by Status';
                        $scopeDescription = 'Letters assigned to ' . htmlspecialchars($department_name);
                        $scopeIcon = 'fa-users-cog';
                        $allowedStatuses = ['assigned', 'in progress', 'under review', 'completed', 'approved', 'on hold', 'rejected', 'filed - information only'];
                    } else {
                        $scopeTitle = 'My Assigned Letters by Status';
                        $scopeDescription = 'Letters currently assigned to me';
                        $scopeIcon = 'fa-user-check';
                        $allowedStatuses = ['assigned', 'in progress', 'under review', 'completed', 'on hold', 'filed - information only'];
                    }
                    
                    // Filter letterStats to only show allowed statuses for this role
                    $filteredLetterStats = array_filter($letterStats, function($stat) use ($allowedStatuses) {
                        $statusLower = strtolower($stat['status_name']);
                        return in_array($statusLower, $allowedStatuses) && $stat['count'] > 0;
                    });
                    ?>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas <?php echo $scopeIcon; ?>"></i> <?php echo $scopeTitle; ?>
                        </h5>
                        <span class="badge bg-primary" style="font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> <?php echo $scopeDescription; ?>
                        </span>
                    </div>
                    
                    <div class="row mb-4">
                        <?php 
                        // Prioritize statuses based on role
                        $priorityStatuses = [];
                        if ($role === 'institution_head') {
                            $priorityStatuses = ['pending', 'under review', 'in progress', 'assigned', 'completed', 'approved', 'filed - information only'];
                        } elseif ($role === 'department_head') {
                            $priorityStatuses = ['under review', 'in progress', 'assigned', 'completed', 'approved', 'filed - information only'];
                        } elseif ($role === 'subject_officer') {
                            $priorityStatuses = ['in progress', 'assigned', 'under review', 'completed', 'filed - information only'];
                        } else {
                            $priorityStatuses = ['pending', 'assigned', 'in progress', 'under review', 'completed', 'filed - information only'];
                        }
                        
                        // Sort filteredLetterStats by priority
                        usort($filteredLetterStats, function($a, $b) use ($priorityStatuses) {
                            $aLower = strtolower($a['status_name']);
                            $bLower = strtolower($b['status_name']);
                            
                            $aPriority = array_search($aLower, $priorityStatuses);
                            $bPriority = array_search($bLower, $priorityStatuses);
                            
                            if ($aPriority === false) $aPriority = 999;
                            if ($bPriority === false) $bPriority = 999;
                            
                            return $aPriority - $bPriority;
                        });
                        
                        foreach ($filteredLetterStats as $stat): 
                            $statusName = htmlspecialchars($stat['status_name']);
                            $statusLower = strtolower($stat['status_name']);
                            $count = $stat['count'];
                            
                            // For Institution Head: Change "Approved" to "Department Head Approved"
                            if ($role === 'institution_head' && $statusLower === 'approved') {
                                $statusName = 'Department Head Approved';
                            }
                            
                            // Determine color and icon based on status
                            $color = '#6c757d';
                            $icon = 'fa-file';
                            $description = 'Letters in this status';
                            $roleContext = '';
                            
                            // Role-specific descriptions
                            if (in_array($statusLower, ['pending', 'new', 'submitted'])) {
                                $color = '#ffc107';
                                $icon = 'fa-hourglass-half';
                                if ($role === 'institution_head') {
                                    $roleContext = 'Ready for assignment to officers';
                                } elseif ($role === 'department_head') {
                                    $roleContext = 'Awaiting your review or action';
                                } else {
                                    $roleContext = 'Awaiting assignment or action';
                                }
                            } elseif (in_array($statusLower, ['in progress', 'processing'])) {
                                $color = '#17a2b8';
                                $icon = 'fa-spinner';
                                if ($role === 'subject_officer') {
                                    $roleContext = 'Your active work items';
                                } elseif ($role === 'department_head') {
                                    $roleContext = 'Being worked by your team';
                                } else {
                                    $roleContext = 'Currently being worked on';
                                }
                            } elseif (in_array($statusLower, ['under review', 'review'])) {
                                $color = '#6f42c1';
                                $icon = 'fa-search';
                                if ($role === 'department_head') {
                                    $roleContext = 'Requires your review';
                                } elseif ($role === 'institution_head') {
                                    $roleContext = 'Pending final approval';
                                } else {
                                    $roleContext = 'Under management review';
                                }
                            } elseif (in_array($statusLower, ['completed', 'done', 'closed'])) {
                                $color = '#28a745';
                                $icon = 'fa-check-circle';
                                $roleContext = 'Successfully completed';
                            } elseif (in_array($statusLower, ['approved'])) {
                                $color = '#20c997';
                                $icon = 'fa-thumbs-up';
                                if ($role === 'institution_head') {
                                    $roleContext = 'Forwarded by Department Head for your review';
                                } else {
                                    $roleContext = 'Approved and finalized';
                                }
                            } elseif (in_array($statusLower, ['on hold', 'paused'])) {
                                $color = '#fd7e14';
                                $icon = 'fa-pause-circle';
                                $roleContext = 'Temporarily paused';
                            } elseif (in_array($statusLower, ['rejected', 'cancelled'])) {
                                $color = '#dc3545';
                                $icon = 'fa-times-circle';
                                $roleContext = 'Rejected or cancelled';
                            } elseif (in_array($statusLower, ['archived'])) {
                                $color = '#6c757d';
                                $icon = 'fa-archive';
                                $roleContext = 'Archived for records';
                            } elseif (in_array($statusLower, ['filed - information only', 'filed'])) {
                                $color = '#17c671';
                                $icon = 'fa-folder';
                                if ($role === 'subject_officer') {
                                    $roleContext = 'Filed by you - info only';
                                } elseif ($role === 'department_head') {
                                    $roleContext = 'Filed by your team';
                                } else {
                                    $roleContext = 'For information only - filed';
                                }
                            } else {
                                $color = '#17a2b8';
                                $icon = 'fa-inbox';
                                $roleContext = 'Newly received';
                            }
                            
                            $description = $roleContext ?: $description;
                            $percentage = $totalLetters > 0 ? round(($count / $totalLetters) * 100, 1) : 0;
                        ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <a href="letters.php?status=<?php echo urlencode($statusName); ?>" style="text-decoration: none; color: inherit;">
                                    <div class="status-card" style="border-left-color: <?php echo $color; ?>; cursor: pointer; transition: all 0.3s;">
                                        <h6 style="color: #1a1a1a; font-weight: 700;"><i class="fas <?php echo $icon; ?>"></i> <?php echo $statusName; ?></h6>
                                        <div class="count" style="font-size: 2rem; font-weight: bold; color: <?php echo $color; ?>;">
                                            <?php echo $count; ?>
                                        </div>
                                        <small class="d-block mb-1" style="color: #4a4a4a; font-weight: 600;"><?php echo $description; ?></small>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>;" 
                                                 aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small style="color: #4a4a4a; font-weight: 600;"><?php echo $percentage; ?>% of <?php 
                                            if ($role === 'institution_head' || $role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
                                                echo 'all letters';
                                            } elseif ($role === 'department_head') {
                                                echo 'department letters';
                                            } else {
                                                echo 'my letters';
                                            }
                                        ?></small>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($filteredLetterStats)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <?php 
                                    if ($role === 'subject_officer') {
                                        echo 'No letters assigned to you yet. New assignments will appear here.';
                                    } elseif ($role === 'department_head') {
                                        echo 'No letters in your department yet.';
                                    } else {
                                        echo 'No letters found. Start by adding new letters to the system.';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="status-card" style="border-left-color: #007bff; background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);">
                                <h6><i class="fas fa-envelope"></i> 
                                    <?php 
                                    if ($role === 'institution_head' || $role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
                                        echo 'Total Letters';
                                    } elseif ($role === 'department_head') {
                                        echo 'Department Total';
                                    } else {
                                        echo 'My Total Letters';
                                    }
                                    ?>
                                </h6>
                                <div class="count" style="font-size: 2.5rem; font-weight: bold; color: #007bff;">
                                    <?php echo $totalLetters; ?>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    if ($role === 'institution_head' || $role === 'postal_subject_officer' || $role === 'chief_management_assistant') {
                                        echo 'All letters system-wide';
                                    } elseif ($role === 'department_head') {
                                        echo 'Letters in ' . htmlspecialchars($department_abbr ?: 'your department');
                                    } else {
                                        echo 'Letters assigned to you';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="status-card" style="border-left-color: #ffc107;">
                                <h6><i class="fas fa-clock"></i> Action Required</h6>
                                <div class="count" style="font-size: 2.5rem; font-weight: bold; color: #ffc107;">
                                    <?php 
                                    // Action Required shows Pending letters (already includes unassigned)
                                    $actionRequiredCount = $categories['Pending'];
                                    echo $actionRequiredCount;
                                    ?>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    if ($role === 'institution_head') {
                                        echo $unassignedCount > 0 ? "Including {$unassignedCount} unassigned" : 'Need assignment to officers';
                                    } elseif ($role === 'department_head') {
                                        echo 'Awaiting your review';
                                    } elseif ($role === 'subject_officer') {
                                        echo 'Awaiting your action';
                                    } else {
                                        echo 'Pending & awaiting action';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="status-card" style="border-left-color: #17a2b8;">
                                <h6><i class="fas fa-tasks"></i> In Progress</h6>
                                <div class="count" style="font-size: 2.5rem; font-weight: bold; color: #17a2b8;">
                                    <?php echo $categories['Processed']; ?>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    if ($role === 'institution_head') {
                                        $breakdown = [];
                                        if ($processedBreakdown['in_progress'] > 0) {
                                            $breakdown[] = $processedBreakdown['in_progress'] . ' In Progress';
                                        }
                                        if ($processedBreakdown['under_review'] > 0) {
                                            $breakdown[] = $processedBreakdown['under_review'] . ' Under Review';
                                        }
                                        if ($processedBreakdown['processing'] > 0) {
                                            $breakdown[] = $processedBreakdown['processing'] . ' Processing';
                                        }
                                        if (!empty($breakdown)) {
                                            echo implode(', ', $breakdown);
                                        } else {
                                            $categoriesTotal = $categories['Pending'] + $categories['Processed'] + $categories['Completed'] + $categories['Received'];
                                            echo 'Out of ' . $categoriesTotal . ' total';
                                        }
                                    } elseif ($role === 'subject_officer') {
                                        echo 'Your active work items';
                                    } elseif ($role === 'department_head') {
                                        echo 'Team is working on';
                                    } else {
                                        echo 'Being processed or reviewed';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="status-card" style="border-left-color: #28a745;">
                                <h6><i class="fas fa-check-double"></i> Completed</h6>
                                <div class="count" style="font-size: 2.5rem; font-weight: bold; color: #28a745;">
                                    <?php echo $categories['Completed']; ?>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    if ($role === 'subject_officer') {
                                        echo 'Your completed work';
                                    } else {
                                        echo 'Successfully finished';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4 col-sm-6">
                        <a href="letters.php" class="nav-card">
                            <div class="icon"><i class="fas fa-envelope"></i></div>
                            <h5>Letters</h5>
                            <p>View and manage letters</p>
                        </a>
                    </div>
                    
                    <?php if ($role == 'postal_subject_officer' || $role == 'chief_management_assistant') { ?>
                    <div class="col-md-4 col-sm-6">
                        <a href="letter_form.php" class="nav-card">
                            <div class="icon"><i class="fas fa-plus-circle"></i></div>
                            <h5>Add New Letter</h5>
                            <p>Enter received letters</p>
                        </a>
                    </div>
                    <?php } ?>
                    
                    <?php if ($role == 'department_head' || $role == 'institution_head') { ?>
                    <div class="col-md-4 col-sm-6">
                        <a href="summary.php" class="nav-card">
                            <div class="icon"><i class="fas fa-chart-pie"></i></div>
                            <h5>Summary</h5>
                            <p>View statistics and reports</p>
                        </a>
                    </div>
                    <?php } ?>
                    
                    <?php if ($role == 'institution_head') { ?>
                    <div class="col-md-4 col-sm-6">
                        <a href="employees.php" class="nav-card">
                            <div class="icon"><i class="fas fa-user-tie"></i></div>
                            <h5>Employees</h5>
                            <p>Manage employees</p>
                        </a>
                    </div>
                    
                    <div class="col-md-4 col-sm-6">
                        <a href="departments.php" class="nav-card">
                            <div class="icon"><i class="fas fa-sitemap"></i></div>
                            <h5>Departments</h5>
                            <p>Manage departments</p>
                        </a>
                    </div>
                    
                    <div class="col-md-4 col-sm-6">
                        <a href="user_management.php" class="nav-card">
                            <div class="icon"><i class="fas fa-users-cog"></i></div>
                            <h5>User Management</h5>
                            <p>Approve & manage users</p>
                        </a>
                    </div>
                    <?php } ?>
                    
                    <div class="col-md-4 col-sm-6">
                        <a href="reports.php" class="nav-card">
                            <div class="icon"><i class="fas fa-chart-bar"></i></div>
                            <h5>Reports</h5>
                            <p>Generate & print reports</p>
                        </a>
                    </div>
                    
                    <div class="col-md-4 col-sm-6">
                        <a href="logout.php" class="nav-card">
                            <div class="icon"><i class="fas fa-sign-out-alt"></i></div>
                            <h5>Logout</h5>
                            <p>Sign out securely</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Footer -->
    <?php render_dashboard_footer(); ?>
</body>
</html>