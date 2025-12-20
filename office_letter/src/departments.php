<?php
require_once 'auth.php';
require_once '../config/db.php';
require_once 'footer_component.php';

// Get role from Users table
$user_id = intval($_SESSION['user_id']);
$roleRes = $conn->query("SELECT role FROM Users WHERE id = $user_id");
$normalized_role = ($roleRes && $roleRes->num_rows > 0) ? strtolower(str_replace(' ', '_', $roleRes->fetch_assoc()['role'])) : '';
if (!in_array($normalized_role, ['head_of_institution', 'institution_head', 'department_head', 'subject_officer'])) {
    header('Location: dashboard.php');
    exit;
}
$role = $normalized_role;

// Role-based summary
$role = $_SESSION['role'];
if (in_array($role, ['head_of_institution', 'institution_head'])) {
    // Institution head: show letter summary for all departments
    $summary = [
        'total' => 0,
        'received' => 0,
        'in_progress' => 0,
        'completed' => 0
    ];
    $res = $conn->query("SELECT status_id, COUNT(*) as cnt FROM Letter GROUP BY status_id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $statusRes = $conn->query("SELECT status_name FROM LetterStatus WHERE id = " . intval($row['status_id']));
            $statusName = $statusRes ? strtolower($statusRes->fetch_assoc()['status_name']) : '';
            if ($statusName == 'received') $summary['received'] += $row['cnt'];
            if ($statusName == 'in progress') $summary['in_progress'] += $row['cnt'];
            if ($statusName == 'completed') $summary['completed'] += $row['cnt'];
        }
        // Total = Received + In Progress + Completed
        $summary['total'] = $summary['received'] + $summary['in_progress'] + $summary['completed'];
    }
} elseif ($role == 'department_head') {
    // Department head: show department summary
    $deptRes = $conn->query("SELECT department_id FROM Employee WHERE id = (SELECT employee_id FROM Users WHERE id = " . intval($_SESSION['user_id']) . ")");
    $dept_id = ($deptRes && $deptRes->num_rows > 0) ? $deptRes->fetch_assoc()['department_id'] : 0;
    $summary = [
        'total' => 0,
        'received' => 0,
        'in_progress' => 0,
        'completed' => 0
    ];
    $res = $conn->query("SELECT status_id, COUNT(*) as cnt FROM Letter WHERE department_id = $dept_id GROUP BY status_id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $statusRes = $conn->query("SELECT status_name FROM LetterStatus WHERE id = " . intval($row['status_id']));
            $statusName = $statusRes ? strtolower($statusRes->fetch_assoc()['status_name']) : '';
            if ($statusName == 'received') $summary['received'] += $row['cnt'];
            if ($statusName == 'in_progress') $summary['in_progress'] += $row['cnt'];
            if ($statusName == 'completed') $summary['completed'] += $row['cnt'];
        }
        // Total = Received + In Progress + Completed
        $summary['total'] = $summary['received'] + $summary['in_progress'] + $summary['completed'];
    }
} elseif ($role == 'subject_officer') {
    // Subject officer: show employee summary
    $empRes = $conn->query("SELECT employee_id FROM Users WHERE id = " . intval($_SESSION['user_id']));
    $emp_id = ($empRes && $empRes->num_rows > 0) ? $empRes->fetch_assoc()['employee_id'] : 0;
    $summary = [
        'total' => 0,
        'received' => 0,
        'in_progress' => 0,
        'completed' => 0
    ];
    $res = $conn->query("SELECT status_id, COUNT(*) as cnt FROM Letter WHERE employee_id = $emp_id GROUP BY status_id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $statusRes = $conn->query("SELECT status_name FROM LetterStatus WHERE id = " . intval($row['status_id']));
            $statusName = $statusRes ? strtolower($statusRes->fetch_assoc()['status_name']) : '';
            if ($statusName == 'received') $summary['received'] += $row['cnt'];
            if ($statusName == 'in_progress') $summary['in_progress'] += $row['cnt'];
            if ($statusName == 'completed') $summary['completed'] += $row['cnt'];
        }
        // Total = Received + In Progress + Completed
        $summary['total'] = $summary['received'] + $summary['in_progress'] + $summary['completed'];
    }
}

$sql = "SELECT * FROM Department";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Departments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .departments-container {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 2.5rem;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        .departments-heading {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: bold;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 1.5rem;
            letter-spacing: 2px;
            text-shadow: 1px 1px 8px #fff;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <div class="container-fluid px-4" style="max-width: 1600px;">
        <div class="departments-container">
            <div class="departments-heading"><?php echo strtoupper(APP_NAME); ?></div>
            <h4 class="mb-4 text-center"><i class="fas fa-building"></i> Departments</h4>
            <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Letters</h5>
                        <p class="card-text display-6"><?php echo $summary['total']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Received</h5>
                        <p class="card-text display-6"><?php echo $summary['received']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">In Progress</h5>
                        <p class="card-text display-6"><?php echo $summary['in_progress']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <p class="card-text display-6"><?php echo $summary['completed']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <table id="departmentsTable" class="table table-striped">
            <thead>
                <tr><th>Name</th><th>Abbreviation</th></tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['abbreviation']); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        </div>
        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#departmentsTable').DataTable();
        });
    </script>
    
    <!-- Footer -->
    <?php render_dashboard_footer(); ?>
</body>
</html>