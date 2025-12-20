<?php
// Application name central constant
define('APP_NAME', 'Documents Management System');

// Set timezone for Sri Lanka (adjust to your timezone if needed)
date_default_timezone_set('Asia/Colombo');

// Database configuration
$host = 'localhost';
$db   = 'office_letter';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Set character encoding to UTF-8 for proper Unicode support (including Sinhala)
$conn->set_charset("utf8mb4");

// Set MySQL timezone to match PHP timezone
$conn->query("SET time_zone = '+05:30'");
?>