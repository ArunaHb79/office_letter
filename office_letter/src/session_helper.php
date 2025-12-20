<?php
// Secure Session Management Helper

/**
 * Start a secure session with proper settings
 */
function start_secure_session() {
    // Include session configuration
    require_once __DIR__ . '/session_config.php';
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Session timeout (30 minutes)
    $timeout = 30 * 60; // 30 minutes
    
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_unset();
            session_destroy();
            header('Location: index.php?timeout=1');
            exit;
        }
    }
    
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Destroy session securely
 */
function destroy_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

/**
 * Validate session integrity
 */
function validate_session() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
    
    // Additional user agent validation
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    } else if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        destroy_session();
        header('Location: index.php?error=session_invalid');
        exit;
    }
}
?>