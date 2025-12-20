<?php
// Email Configuration and Helper Functions

/**
 * Email Configuration
 * For production deployment, configure these settings according to your email provider
 */
class EmailConfig {
    // SMTP Settings for production
    const SMTP_HOST = 'smtp.gmail.com'; // Change to your SMTP server
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'your-email@gmail.com'; // Change to your email
    const SMTP_PASSWORD = 'your-app-password'; // Use app password for Gmail
    const SMTP_ENCRYPTION = 'tls'; // or 'ssl'
    
    // Email settings
    const FROM_EMAIL = 'no-reply@your-domain.com';
    const FROM_NAME = APP_NAME;
    const REPLY_TO = 'support@your-domain.com';
}

/**
 * Send email using PHP's mail function (for simple hosting)
 */
function send_simple_email($to, $subject, $message, $from_email = null) {
    $from_email = $from_email ?: EmailConfig::FROM_EMAIL;
    
    $headers = "From: " . EmailConfig::FROM_NAME . " <$from_email>\r\n";
    $headers .= "Reply-To: " . EmailConfig::REPLY_TO . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send password reset email
 */
function send_password_reset_email($email, $reset_link) {
    $subject = 'Password Reset Request - ' . APP_NAME;
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Password Reset Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; font-weight: 500; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            .button { display: inline-block; padding: 12px 24px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; font-weight: 700; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #4a4a4a; font-weight: 600; }
            p { color: #1a1a1a; font-weight: 500; }
            h2 { color: #1a1a1a; font-weight: 700; }
        </style>
    </head>
    <body>
        <div class="container">
                <div class="header">
                <h1>' . APP_NAME . '</h1>
            </div>
            <div class="content">
                <h2>Password Reset Request</h2>
                <p>Dear User,</p>
                <p>You have requested a password reset for your ' . APP_NAME . ' account.</p>
                <p>Click the button below to reset your password:</p>
                <p style="text-align: center;">
                    <a href="' . htmlspecialchars($reset_link) . '" class="button">Reset Password</a>
                </p>
                <p><strong>Important:</strong> This link will expire in 1 hour for security reasons.</p>
                <p>If the button doesn\'t work, you can copy and paste the following link into your browser:</p>
                <p style="word-break: break-all; background: #e9ecef; padding: 10px; font-family: monospace;">
                    ' . htmlspecialchars($reset_link) . '
                </p>
                <p>If you did not request this password reset, please ignore this email. Your password will remain unchanged.</p>
            </div>
            <div class="footer">
                <p>This is an automated email from ' . APP_NAME . '.</p>
                <p>Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return send_simple_email($email, $subject, $message);
}

/**
 * Check if we're in development mode
 */
function is_development_mode() {
    $dev_indicators = ['localhost', '127.0.0.1', '::1', '.local', '.dev', '.test'];
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    
    foreach ($dev_indicators as $indicator) {
        if (strpos($host, $indicator) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Send email with development/production handling
 */
function send_email_with_fallback($to, $subject, $message, $reset_link = null) {
    if (is_development_mode()) {
        // Development mode - return link for display
        return [
            'success' => true, 
            'message' => 'Development mode: Email would be sent to ' . htmlspecialchars($to),
            'reset_link' => $reset_link
        ];
    } else {
        // Production mode - actually send email
        if ($reset_link) {
            $success = send_password_reset_email($to, $reset_link);
        } else {
            $success = send_simple_email($to, $subject, $message);
        }
        
        return [
            'success' => $success,
            'message' => $success ? 'Email sent successfully' : 'Failed to send email'
        ];
    }
}

/**
 * Instructions for SMTP configuration
 */
function get_smtp_setup_instructions() {
    return '
    <!-- SMTP Setup Instructions for Production -->
    <!--
    For production deployment, you have several options:
    
    1. GMAIL SMTP (Recommended for small applications):
       - Enable 2-factor authentication in Gmail
       - Generate an app password
       - Use smtp.gmail.com:587 with TLS
    
    2. SMTP2GO, SendGrid, Mailgun (Recommended for production):
       - Sign up for a transactional email service
       - Get SMTP credentials
       - Configure the settings in EmailConfig class
    
    3. Server SMTP (If your hosting provides it):
       - Use your hosting provider\'s SMTP settings
       - Usually localhost:587 or mail.yourdomain.com
    
    4. For XAMPP Local Development:
       - Install a tool like MailHog or Papercut SMTP
       - Or use the development mode (shows links directly)
    
    Update the EmailConfig class constants with your actual SMTP settings.
    -->';
}
?>