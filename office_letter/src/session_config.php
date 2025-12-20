<?php
// Session Configuration - Include this file FIRST in any script that uses sessions
// This file sets session configuration before any session is started

if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration before starting session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.entropy_length', 32);
    ini_set('session.hash_function', 'sha256');
    ini_set('session.name', 'OFFICE_LETTER_SESS');
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
}
?>