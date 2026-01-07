<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("=== Processing Login ===");
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

require_once 'admin/config.php';

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['login_error'] = "Invalid request method.";
    header("Location: student_login.php");
    exit();
}

// Validate required fields
if (!isset($_POST['email']) || !isset($_POST['password'])) {
    $_SESSION['login_error'] = "Please provide both email and password.";
    header("Location: student_login.php");
    exit();
}

$email = trim($_POST['email']);
$password = $_POST['password'];

try {
    // Get current academic year
    $academic_year = '20' . (date('y')-1) . '-' . date('y');

    // Get student details with class information
    $sql = "SELECT s.*, c.class_name, c.department, c.semester 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.id 
            WHERE s.email = ? AND s.is_active = 1";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student || !password_verify($password, $student['password'])) {
        throw new Exception("Invalid email or password.");
    }

    // Check if feedback already submitted
    $check_sql = "SELECT id FROM feedback_submissions 
                  WHERE student_email = ? AND academic_year = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $email, $academic_year);
    $check_stmt->execute();
    $submission_result = $check_stmt->get_result();

    if ($submission_result->num_rows > 0) {
        throw new Exception("You have already submitted feedback for this academic year.");
    }

    // Start a fresh session
    session_unset();
    session_destroy();
    session_start();

    // Store student data in session
    $_SESSION['student_id'] = $student['id'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['student_email'] = $student['email'];
    $_SESSION['student'] = $student;
    $_SESSION['last_activity'] = time();
    
    error_log("Login successful - New session data: " . print_r($_SESSION, true));
    
    // Redirect to feedback form
    header("Location: index.php");
    exit();

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['login_error'] = $e->getMessage();
    header("Location: student_login.php");
    exit();
}
?> 