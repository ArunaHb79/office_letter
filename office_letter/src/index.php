<?php
require_once 'session_config.php';
require_once 'session_helper.php';
require_once 'validation_helper.php';
start_secure_session();
require_once '../config/db.php';
require_once 'csrf_helper.php';
require_once 'activity_logger.php';
require_once 'footer_component.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    
    $email = sanitize_string($_POST['email']);
    $password = $_POST['password'];
    
    // Rate limiting
    if (!check_rate_limit($email)) {
        $error = 'Too many login attempts. Please try again later.';
    } else {
        $stmt = $conn->prepare("SELECT id, password, approved FROM Users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                // Check if account is approved
                if ($row['approved'] == 0) {
                    $error = 'Your account is pending approval by Institution Head. Please wait for approval.';
                } else {
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $_SESSION['created'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login timestamp
                    $updateLoginStmt = $conn->prepare("UPDATE Users SET last_login = NOW() WHERE id = ?");
                    $updateLoginStmt->bind_param('i', $row['id']);
                    $updateLoginStmt->execute();
                    
                    // Get role from Users table
                    $roleStmt = $conn->prepare("SELECT role FROM Users WHERE id = ? LIMIT 1");
                    $roleStmt->bind_param('i', $row['id']);
                    $roleStmt->execute();
                    $roleRes = $roleStmt->get_result();
                    $_SESSION['role'] = ($roleRes && $roleRes->num_rows > 0) ? strtolower(str_replace(' ', '_', $roleRes->fetch_assoc()['role'])) : '';
                    
                    // Log successful login
                    $logger = new ActivityLogger($conn, $row['id']);
                    $logger->logLogin('standard');
                    
                    header('Location: dashboard.php');
                    exit;
                }

            } else {
                // Log failed login attempt
                ActivityLogger::logFailedLoginAttempt($conn, $email, 'invalid_password');
                
                $error = 'Invalid password.';
            }
        } else {
            // Log failed login attempt for non-existent user
            ActivityLogger::logFailedLoginAttempt($conn, $email, 'user_not_found');
            
            $error = 'User not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
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
        .login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .login-card {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.5s ease-in-out;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem 2rem 2rem;
            text-align: center;
        }
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 0 0.5rem 0;
            letter-spacing: 1px;
        }
        .login-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .login-body {
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
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-login:hover {
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
            color: #5569d8;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .links-section a:hover {
            color: #442e7a;
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
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-file-alt"></i> <?php echo strtoupper(APP_NAME); ?></h1>
                <p>Sign in to your account</p>
            </div>
            
            <div class="login-body">
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i> Your session has expired. Please login again.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'session_invalid'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Invalid session detected. Please login again.
                    </div>
                <?php endif; ?>
                <?php if (isset($error)) { ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php } ?>
                
                <form method="post" onsubmit="return validateLogin();">
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   class="form-control" 
                                   placeholder="Enter your email"
                                   required 
                                   pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$"
                                   autocomplete="email">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control" 
                                   placeholder="Enter your password"
                                   required
                                   autocomplete="current-password">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>
            
            <?php render_system_footer(); ?>
        </div>
    </div>

<script>
function validateLogin() {
    var email = document.getElementById('email').value;
    var emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    if (!emailPattern.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    return true;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>