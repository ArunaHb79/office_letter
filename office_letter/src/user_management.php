<?php
require_once 'session_config.php';
session_start();
require_once 'auth.php';
require_once '../config/db.php';
require_once 'csrf_helper.php';

$user_id = intval($_SESSION['user_id']);

// Check if user is Institution Head
$userStmt = $conn->prepare("SELECT role FROM Users WHERE id = ? LIMIT 1");
$userStmt->bind_param('i', $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();

if ($userData['role'] !== 'institution_head') {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    
    $action = $_POST['action'] ?? '';
    $target_user_id = intval($_POST['user_id'] ?? 0);
    
    if ($action === 'approve' && $target_user_id) {
        $approveStmt = $conn->prepare("
            UPDATE Users 
            SET approved = 1, 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE id = ? AND approved = 0
        ");
        $approveStmt->bind_param('ii', $user_id, $target_user_id);
        if ($approveStmt->execute() && $approveStmt->affected_rows > 0) {
            $success = 'User account approved successfully!';
        } else {
            $error = 'Failed to approve user account.';
        }
    } elseif ($action === 'reject' && $target_user_id) {
        // Delete the pending user - first get employee_id, then delete both records
        $getEmpStmt = $conn->prepare("SELECT employee_id FROM Users WHERE id = ? AND approved = 0");
        $getEmpStmt->bind_param('i', $target_user_id);
        $getEmpStmt->execute();
        $empResult = $getEmpStmt->get_result();
        
        if ($empRow = $empResult->fetch_assoc()) {
            $emp_id = $empRow['employee_id'];
            
            // Delete from Users table first
            $deleteUserStmt = $conn->prepare("DELETE FROM Users WHERE id = ?");
            $deleteUserStmt->bind_param('i', $target_user_id);
            $deleteUserStmt->execute();
            
            // Then delete from Employee table
            $deleteEmpStmt = $conn->prepare("DELETE FROM Employee WHERE id = ?");
            $deleteEmpStmt->bind_param('i', $emp_id);
            
            if ($deleteEmpStmt->execute()) {
                $success = 'User account rejected and removed.';
            } else {
                $error = 'Failed to reject user account: ' . $conn->error;
            }
        } else {
            $error = 'User not found or already approved.';
        }
    } elseif ($action === 'revoke' && $target_user_id) {
        // Revoke approval (set back to pending)
        $revokeStmt = $conn->prepare("
            UPDATE Users 
            SET approved = 0, 
                approved_by = NULL, 
                approved_at = NULL 
            WHERE id = ? AND approved = 1
        ");
        $revokeStmt->bind_param('i', $target_user_id);
        if ($revokeStmt->execute() && $revokeStmt->affected_rows > 0) {
            $success = 'User approval revoked successfully!';
        } else {
            $error = 'Failed to revoke approval.';
        }
    }
}

// Get pending users
$pendingStmt = $conn->query("
    SELECT 
        u.id, 
        u.username, 
        u.email, 
        u.role, 
        u.created_at,
        e.name as employee_name,
        d.name as department_name,
        creator.name as created_by_name
    FROM Users u
    LEFT JOIN Employee e ON u.employee_id = e.id
    LEFT JOIN Department d ON e.department_id = d.id
    LEFT JOIN Users creator_user ON u.created_by = creator_user.id
    LEFT JOIN Employee creator ON creator_user.employee_id = creator.id
    WHERE u.approved = 0
    ORDER BY u.created_at DESC
");

if (!$pendingStmt) {
    $error = 'Database error: ' . $conn->error;
    $pendingStmt = (object)['num_rows' => 0]; // Create empty result
}

// Get approved users
$approvedStmt = $conn->query("
    SELECT 
        u.id, 
        u.username, 
        u.email, 
        u.role, 
        u.created_at,
        u.approved_at,
        e.name as employee_name,
        d.name as department_name,
        approver.name as approved_by_name
    FROM Users u
    LEFT JOIN Employee e ON u.employee_id = e.id
    LEFT JOIN Department d ON e.department_id = d.id
    LEFT JOIN Users approver_user ON u.approved_by = approver_user.id
    LEFT JOIN Employee approver ON approver_user.employee_id = approver.id
    WHERE u.approved = 1
    ORDER BY u.created_at DESC
");

if (!$approvedStmt) {
    $error = 'Database error: ' . $conn->error;
    $approvedStmt = (object)['num_rows' => 0]; // Create empty result
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .user-management-container {
            padding: 2rem 0;
        }
        .management-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .user-row {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s;
        }
        .user-row:hover {
            background-color: #f8f9fa;
        }
        .user-row:last-child {
            border-bottom: none;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #1a1a1a;
            font-weight: 700;
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
        .card-header h5 {
            color: #fff;
            font-weight: 700;
        }
        .management-card h2 {
            color: #1a1a1a;
            font-weight: 700;
        }
        }
        .badge-approved {
            background-color: #28a745;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="user-management-container">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white"><i class="fas fa-users-cog"></i> User Management</h2>
            <div>
                <a href="register.php" class="btn btn-success me-2">
                    <i class="fas fa-user-plus"></i> Create New User
                </a>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Users Section -->
        <div class="management-card">
            <h4><i class="fas fa-clock"></i> Pending Approvals 
                <span class="badge badge-pending"><?php echo is_object($pendingStmt) ? $pendingStmt->num_rows : 0; ?></span>
            </h4>
            <hr>
            <?php if (is_object($pendingStmt) && $pendingStmt->num_rows > 0): ?>
                <?php while ($user = $pendingStmt->fetch_assoc()): ?>
                    <div class="user-row">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['employee_name'] ?? 'Unknown'); ?>
                                </h5>
                                <p class="mb-1">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                                    <strong>Role:</strong> <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $user['role'])); ?></span><br>
                                    <strong>Department:</strong> <?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?><br>
                                    <strong>Requested:</strong> <?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?>
                                    <?php if ($user['created_by_name']): ?>
                                        <br><strong>Created by:</strong> <?php echo htmlspecialchars($user['created_by_name']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Approve this user account?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reject and delete this user account?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted"><i class="fas fa-info-circle"></i> No pending user approvals.</p>
            <?php endif; ?>
        </div>

        <!-- Approved Users Section -->
        <div class="management-card">
            <h4><i class="fas fa-user-check"></i> Approved Users 
                <span class="badge badge-approved"><?php echo is_object($approvedStmt) ? $approvedStmt->num_rows : 0; ?></span>
            </h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Approved</th>
                            <th>Approved By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (is_object($approvedStmt) && $approvedStmt->num_rows > 0): ?>
                            <?php while ($user = $approvedStmt->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['employee_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $user['approved_at'] ? date('Y-m-d H:i', strtotime($user['approved_at'])) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($user['approved_by_name'] ?? 'System'); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Revoke approval for this user?');">
                                            <?php echo csrf_token_field(); ?>
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" title="Revoke Approval">
                                                <i class="fas fa-ban"></i> Revoke
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No approved users yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
