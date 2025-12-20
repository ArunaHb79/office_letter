<?php
/**
 * Update Login Actions with '0' to 'user_login'
 * This script updates activity log records where action='0' to 'user_login' for login-related activities
 */

require_once '../config/db.php';

// Check if this is a POST request to execute the update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_update'])) {
    // Update all action='0' records to 'user_login'
    $update_sql = "UPDATE activitylog SET action = 'user_login' WHERE action = '0'";
    
    if ($conn->query($update_sql)) {
        $affected = $conn->affected_rows;
        $success_message = "Successfully updated {$affected} records from action='0' to 'user_login'.";
    } else {
        $error_message = "Error updating records: " . $conn->error;
    }
}

// Check current count of records with action='0'
$count_result = $conn->query("SELECT COUNT(*) as total FROM activitylog WHERE action = '0'");
$total_zero_actions = 0;
if ($count_result) {
    $row = $count_result->fetch_assoc();
    $total_zero_actions = $row['total'];
}

// Get sample records with action='0'
$sample_sql = "SELECT al.*, u.username, u.email 
               FROM activitylog al 
               LEFT JOIN users u ON al.user_id = u.id
               WHERE al.action = '0' 
               ORDER BY al.timestamp DESC 
               LIMIT 10";
$sample_result = $conn->query($sample_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Login Actions - Office Letter Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f6fa;
        }
        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="info-card">
            <div class="card-header-custom">
                <h2 class="mb-0">
                    <i class="fas fa-sync-alt"></i> Update Login Actions
                </h2>
                <p class="mb-0 mt-2">Convert action '0' to meaningful 'user_login' action name</p>
            </div>
            
            <div class="card-body p-4">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <div class="mt-3">
                            <a href="activity_logs.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> View Activity Logs
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                <?php elseif (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($total_zero_actions > 0): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Status</h5>
                        <p class="mb-0">
                            Found <strong><?php echo $total_zero_actions; ?></strong> activity log records with action='0'.
                            These will be updated to <strong>'user_login'</strong>.
                        </p>
                    </div>

                    <?php if ($sample_result && $sample_result->num_rows > 0): ?>
                        <h5 class="mt-4 mb-3">Sample Records (showing up to 10):</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Current Action</th>
                                        <th>Timestamp</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $sample_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">0</span>
                                                <i class="fas fa-arrow-right mx-2"></i>
                                                <span class="badge bg-success">user_login</span>
                                            </td>
                                            <td><?php echo $row['timestamp']; ?></td>
                                            <td><small><?php echo htmlspecialchars($row['ip_address'] ?? 'N/A'); ?></small></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="card mt-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cog"></i> Execute Update</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                This will update <strong><?php echo $total_zero_actions; ?></strong> records, 
                                changing their action from <span class="badge bg-warning text-dark">0</span> 
                                to <span class="badge bg-success">user_login</span>.
                            </p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to update <?php echo $total_zero_actions; ?> records?');">
                                <button type="submit" name="confirm_update" class="btn btn-success btn-lg">
                                    <i class="fas fa-check"></i> Update All Records
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> All Clean!</h5>
                        <p class="mb-0">
                            No activity log records found with action='0'. 
                            All login actions are properly labeled.
                        </p>
                    </div>
                    <div class="mt-3">
                        <a href="activity_logs.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> View Activity Logs
                        </a>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>
