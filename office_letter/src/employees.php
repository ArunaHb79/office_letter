<?php
require_once 'auth.php';
require_once '../config/db.php';
require_once 'footer_component.php';

// Get user role from session (set by auth.php)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'institution_head') {
    header('Location: dashboard.php');
    exit;
}

// Join Users table to get role for each employee
$sql = "SELECT Employee.*, Department.name AS dept_name, Users.role AS user_role FROM Employee JOIN Department ON Employee.department_id = Department.id LEFT JOIN Users ON Users.employee_id = Employee.id";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employees</title>
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
        .employees-container {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 2.5rem;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        .employees-heading {
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
    <div class="container-fluid px-4" style="max-width: 1400px;">
        <div class="employees-container">
            <div class="employees-heading"><?php echo strtoupper(APP_NAME); ?></div>
            <h4 class="mb-4 text-center"><i class="fas fa-users"></i> Employees</h4>
            <div class="table-responsive">
            <table id="employeesTable" class="table table-striped table-hover">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Department</th><th>Role</th></tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['user_role']))); ?></td>
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
            $('#employeesTable').DataTable();
        });
    </script>
    
    <!-- Footer -->
    <?php render_dashboard_footer(); ?>
</body>
</html>