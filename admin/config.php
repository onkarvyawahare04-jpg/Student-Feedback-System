<?php

// Database configuration
define('DB_HOST', 'sql113.infinityfree.com');
define('DB_USER', 'if0_40846784');
define('DB_PASS', 'S7OPhcznf1GXs');
define('DB_NAME', 'if0_40846784_feedback');

// Create connection with error reporting
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set character set
$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Check if admin is logged in
function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Redirect if admin is not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION['student_id'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: student_login.php");
        exit();
    }
}

// Get current academic year
function getCurrentAcademicYear() {
    $year = date('Y');
    $month = date('n');
    if ($month < 6) { // Before June
        return ($year-1) . '-' . $year;
    }
    return $year . '-' . ($year+1);
}

?>