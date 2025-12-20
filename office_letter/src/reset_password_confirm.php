<?php
require_once '../config/db.php';
require_once 'footer_component.php';

$error = '';
$success = '';
$show_form = false;
$token = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Validate token
    $stmt = $conn->prepare("SELECT employee_id, expires_at FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $employee_id = $row['employee_id'];
        $expires_at = $row['expires_at'];
        
        // Check if token has expired
        if (strtotime($expires_at) < time()) {
            $error = 'Reset token has expired.';
            // Delete expired token
            $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $del->bind_param('s', $token);
            $del->execute();
        } else {
            $show_form = true;
        }
    } else {
        $error = 'Invalid reset token.';
    }
} else {
    $error = 'No reset token provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    // Include CSRF helper for validation
    require_once 'csrf_helper.php';
    check_csrf_token();
    
    $new_password = $_POST['new_password'];
    $token = $_POST['token'];
    
    // Validate password
    if (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $show_form = true;
    } else {
        // Verify token again for security
        $stmt = $conn->prepare("SELECT employee_id, expires_at FROM password_resets WHERE token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $employee_id = $row['employee_id'];
            $expires_at = $row['expires_at'];
            
            if (strtotime($expires_at) >= time()) {
                // Update password in Employee table
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE Employee SET password = ? WHERE id = ?");
                $update->bind_param('si', $hashed, $employee_id);
                
                if ($update->execute()) {
                    // Also update Users table if exists
                    $userUpdate = $conn->prepare("UPDATE Users SET password = ? WHERE employee_id = ?");
                    $userUpdate->bind_param('si', $hashed, $employee_id);
                    $userUpdate->execute();
                    
                    $success = 'Password reset successful! You can now login.';
                    
                    // Delete used token
                    $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $del->bind_param('s', $token);
                    $del->execute();
                } else {
                    $error = 'Failed to reset password.';
                    $show_form = true;
                }
            } else {
                $error = 'Reset token has expired.';
                // Delete expired token
                $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $del->bind_param('s', $token);
                $del->execute();
            }
        } else {
            $error = 'Invalid reset token.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Set New Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .resetconf-container {
            background: rgba(255,255,255,0.92);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 2.5rem 2rem 2rem 2rem;
            margin-top: 60px;
        }
        .resetconf-heading {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: bold;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 1.5rem;
            letter-spacing: 2px;
            text-shadow: 1px 1px 8px #fff;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="resetconf-container w-100" style="max-width: 400px;">
    <div class="resetconf-heading"><?php echo strtoupper(APP_NAME); ?></div>
        <h4 class="mb-4 text-center">Set New Password</h4>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($show_form): ?>
        <form method="post" onsubmit="return validateForm();">
            <?php 
            require_once 'csrf_helper.php';
            echo csrf_token_field(); 
            ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                <div class="form-text">Password must be at least 8 characters.</div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Set Password</button>
        </form>
        <script>
        function validateForm() {
            var password = document.getElementById('new_password').value;
            if (password.length < 8) {
                alert('Password must be at least 8 characters.');
                return false;
            }
            return true;
        }
        </script>
        <?php endif; ?>
        <div class="mt-3 text-center">
            <a href="index.php">Back to Login</a>
        </div>
    </div>
</div>

<!-- Footer -->
<?php render_dashboard_footer(); ?>
</body>
</html>
