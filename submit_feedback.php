<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("=== Processing Feedback Submission ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));
error_log("Session Data: " . print_r($_SESSION, true));

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    error_log("No student_id in session at submit_feedback.php");
    $_SESSION['login_error'] = "Please log in to continue.";
    header("Location: student_login.php");
    exit();
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header("Location: index.php");
    exit();
}

// Debug step information
error_log("Debug Step: " . (isset($_POST['debug_step']) ? $_POST['debug_step'] : 'not set'));
error_log("Debug Total: " . (isset($_POST['debug_total']) ? $_POST['debug_total'] : 'not set'));

require_once 'admin/config.php';

try {
    // Get student details
    $stmt = $conn->prepare("SELECT email, class_id FROM students WHERE id = ?");
    if (!$stmt->bind_param("i", $_SESSION['student_id']) || !$stmt->execute()) {
        throw new Exception("Failed to get student details: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    if (!$student) {
        throw new Exception("Student not found");
    }

    // Get the current academic year
    $academic_year = (date('Y') - 1) . '-' . date('Y');
    // Get the current date
    $current_date = date('Y-m-d');
    // Get student email from session
    $student_email = $student['email'];
    // Get class ID from session
    $class_id = $student['class_id'];

    // Start transaction
    $conn->begin_transaction();

    // Create feedback submission entry
    $sql = "INSERT INTO feedback_submissions (student_email, class_id, academic_year, submission_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt || !$stmt->bind_param("siss", $student_email, $class_id, $academic_year, $current_date)) {
        throw new Exception("Failed to prepare submission insert: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        // Check if it's a duplicate submission
        if ($stmt->errno == 1062) { // Duplicate entry error
            throw new Exception("You have already submitted feedback for this academic year.");
        }
        throw new Exception("Failed to create submission: " . $stmt->error);
    }
    
    $submission_id = $stmt->insert_id;
    error_log("Created feedback submission with ID: " . $submission_id);

    // Process ratings
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'rating_') === 0 && !empty($value)) {
            $parts = explode('_', $key);
            if (count($parts) === 3) {
                $course_id = intval($parts[1]);
                $question_id = intval($parts[2]);
                $rating = intval($value);

                if ($rating < 1 || $rating > 4) {
                    throw new Exception("Invalid rating value: $rating");
                }

                $sql = "INSERT INTO feedback_ratings (submission_id, course_id, question_id, rating) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt || !$stmt->bind_param("iiii", $submission_id, $course_id, $question_id, $rating)) {
                    throw new Exception("Failed to prepare rating insert: " . $conn->error);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert rating: " . $stmt->error);
                }
                error_log("Inserted rating for course $course_id, question $question_id");
            }
        }
    }

    // Process text responses
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'text_') === 0 && !empty($value)) {
            $question_id = intval(substr($key, 5));
            
            $sql = "INSERT INTO feedback_text_responses (submission_id, question_id, response) 
                    VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt || !$stmt->bind_param("iis", $submission_id, $question_id, $value)) {
                throw new Exception("Failed to prepare text response insert: " . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert text response: " . $stmt->error);
            }
            error_log("Inserted text response for question $question_id");
        }
    }

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully");
    
    // Clear feedback data from session
    unset($_SESSION['feedback_data']);
    
    // Set success message
    $_SESSION['success_message'] = "Thank you! Your feedback has been submitted successfully.";
    
    // Redirect to success page
    error_log("Redirecting to success page...");
    header("Location: feedback_success.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
        error_log("Transaction rolled back");
    }
    
    error_log("Feedback submission error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['login_error'] = "An error occurred while submitting your feedback: " . $e->getMessage();
    header("Location: index.php");
    exit();
} 