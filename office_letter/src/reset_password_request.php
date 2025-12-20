<?php
require_once 'session_config.php';
require_once '../config/db.php';
require_once 'csrf_helper.php';
require_once 'email_helper.php';
require_once 'footer_component.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    
    $email = $_POST['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM Employee WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_id = $row['id'];
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $insert = $conn->prepare("INSERT INTO password_resets (employee_id, token, expires_at) VALUES (?, ?, ?)");
            $insert->bind_param('iss', $user_id, $token, $expires);
            $insert->execute();
            // Generate reset link
            $reset_link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset_password_confirm.php?token=' . $token;
            
            // Send email using the helper function
            $email_result = send_email_with_fallback($email, '', '', $reset_link);
            
            if ($email_result['success']) {
                if (is_development_mode()) {
                    $success = '
                    <div class="mb-3">
                        <strong>Development Mode Active</strong><br>
                        <small class="text-muted">In production, this would be sent via email.</small>
                    </div>
                    <div class="d-grid">
                        <a href="' . htmlspecialchars($reset_link) . '" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-key"></i> Reset Your Password
                        </a>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">⏰ This link will expire in 1 hour</small>
                    </div>';
                } else {
                    $success = 'A password reset link has been sent to your email address.';
                }
            } else {
                $error = 'Failed to send password reset email. Please contact the administrator.';
                // Log error for debugging
                error_log("Failed to send password reset email to: " . $email);
            }
        } else {
            $error = 'Email not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .reset-card {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.5s ease-in-out;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem 2rem 2rem;
            text-align: center;
        }
        .reset-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 0 0.5rem 0;
            letter-spacing: 1px;
        }
        .reset-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .reset-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-reset:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .links-section {
            padding: 1.5rem 2rem 2rem 2rem;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .links-section a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .links-section a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="reset-wrapper">
        <div class="reset-card">
            <div class="reset-header">
                <h1><i class="fas fa-key"></i> <?php echo strtoupper(APP_NAME); ?></h1>
                <p>Reset your password</p>
            </div>
            
            <div class="reset-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
            <?php echo csrf_token_field(); ?>
            
            <div class="mb-4">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" name="email" id="email" class="form-control" 
                       placeholder="Enter your email" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-reset w-100">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
            
            <div class="mt-3 text-center">
                <a href="index.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </form>
    </div>
            
    <div class="links-section">
        <p class="mb-0 text-muted">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> <?php echo APP_NAME; ?> | Created and Developed By Group No-05
        </p>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
