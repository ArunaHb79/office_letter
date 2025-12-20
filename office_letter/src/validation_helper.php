<?php
// Input Validation and Sanitization Helper

/**
 * Sanitize string input
 */
function sanitize_string($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 */
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Validate integer input
 */
function validate_integer($input, $min = null, $max = null) {
    $int = filter_var($input, FILTER_VALIDATE_INT);
    
    if ($int === false) {
        return false;
    }
    
    if ($min !== null && $int < $min) {
        return false;
    }
    
    if ($max !== null && $int > $max) {
        return false;
    }
    
    return $int;
}

/**
 * Validate date input
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Sanitize filename
 */
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
}

/**
 * Rate limiting for login attempts
 */
function check_rate_limit($identifier, $max_attempts = 5, $window = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/session_config.php';
        session_start();
    }
    
    $key = 'rate_limit_' . md5($identifier);
    $current_time = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Clean old attempts
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($current_time, $window) {
        return ($current_time - $timestamp) < $window;
    });
    
    if (count($_SESSION[$key]) >= $max_attempts) {
        return false; // Rate limit exceeded
    }
    
    $_SESSION[$key][] = $current_time;
    return true; // Allow request
}

/**
 * Validate and sanitize form data
 */
function validate_form_data($data, $rules) {
    $errors = [];
    $clean_data = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? '';
        
        // Required field check
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[$field] = ucfirst($field) . ' is required';
            continue;
        }
        
        // Skip validation if field is empty and not required
        if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
            $clean_data[$field] = '';
            continue;
        }
        
        // Type validation
        switch ($rule['type'] ?? 'string') {
            case 'email':
                if (!validate_email($value)) {
                    $errors[$field] = 'Invalid email format';
                } else {
                    $clean_data[$field] = strtolower(trim($value));
                }
                break;
                
            case 'password':
                $validation = validate_password($value);
                if ($validation !== true) {
                    $errors[$field] = implode(', ', $validation);
                } else {
                    $clean_data[$field] = $value; // Don't sanitize passwords
                }
                break;
                
            case 'integer':
                $int = validate_integer($value, $rule['min'] ?? null, $rule['max'] ?? null);
                if ($int === false) {
                    $errors[$field] = 'Invalid number format';
                } else {
                    $clean_data[$field] = $int;
                }
                break;
                
            case 'date':
                if (!validate_date($value)) {
                    $errors[$field] = 'Invalid date format';
                } else {
                    $clean_data[$field] = $value;
                }
                break;
                
            default: // string
                $clean_data[$field] = sanitize_string($value);
                
                // Length validation
                if (isset($rule['max_length']) && strlen($clean_data[$field]) > $rule['max_length']) {
                    $errors[$field] = ucfirst($field) . ' must be less than ' . $rule['max_length'] . ' characters';
                }
                
                if (isset($rule['min_length']) && strlen($clean_data[$field]) < $rule['min_length']) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $rule['min_length'] . ' characters';
                }
                break;
        }
    }
    
    return ['errors' => $errors, 'data' => $clean_data];
}
?>