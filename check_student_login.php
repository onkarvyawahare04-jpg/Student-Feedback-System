<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug information
error_log("=== Check Student Login ===");
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// If this is a form submission from feedback pages, preserve session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page'])) {
    error_log("Form submission detected - preserving session");
    if (!isset($_SESSION['student_id'])) {
        error_log("No student_id in session during form submission");
        $_SESSION['login_error'] = "Session expired. Please log in again.";
        header("Location: student_login.php");
        exit();
    }
    // Just return here to preserve the session
    return;
}

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    error_log("No student_id in session");
    $_SESSION['login_error'] = "Please log in to continue.";
    header("Location: student_login.php");
    exit();
}

// Get student details from database
require_once 'admin/config.php';

try {
    $stmt = $conn->prepare("SELECT s.*, c.class_name, c.department, c.semester 
                           FROM students s 
                           LEFT JOIN classes c ON s.class_id = c.id 
                           WHERE s.id = ? AND s.is_active = 1");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $_SESSION['student_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        throw new Exception("Student account not found or inactive.");
    }

    // Update session data
    $_SESSION['student'] = $student;
    $_SESSION['student_email'] = $student['email'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['last_activity'] = time();

    // Debug final session state
    error_log("Updated session state: " . print_r($_SESSION, true));

} catch (Exception $e) {
    error_log("Error in check_student_login.php: " . $e->getMessage());
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['login_error'] = $e->getMessage();
    header("Location: student_login.php");
    exit();
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    error_log("Session timeout - last activity: " . $_SESSION['last_activity'] . ", current time: " . time());
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['login_error'] = "Your session has expired. Please log in again.";
    header("Location: student_login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Get current academic year
        $academic_year = '20' . (date('y')-1) . '-' . date('y');

        // Verify login credentials
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
            throw new Exception("Invalid Email Address or Password.");
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

        // Store student data in session
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['full_name'];
        $_SESSION['student_email'] = $student['email'];
        $_SESSION['student'] = $student;
        $_SESSION['last_activity'] = time();
        
        error_log("Login successful - Session data: " . print_r($_SESSION, true));
        
        // Redirect to feedback form
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['login_error'] = $e->getMessage();
        header("Location: student_login.php");
        exit();
    }
}
?> 