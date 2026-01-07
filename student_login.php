<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("=== Student Login Page ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session Data: " . print_r($_SESSION, true));

require_once 'admin/config.php';

// Only clear session if there's no active student session
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SESSION['student_id'])) {
    error_log("Clearing session - no active student session");
    session_unset();
    session_destroy();
    session_start();
}

// Get error message if any
$error_message = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
$success_message = isset($_SESSION['login_message']) ? $_SESSION['login_message'] : '';

// Clear messages after displaying
unset($_SESSION['login_error']);
unset($_SESSION['login_message']);

// Check if already logged in and redirect to appropriate page
if (isset($_SESSION['student_id']) && isset($_SESSION['student_email'])) {
    error_log("Active session found - redirecting to index.php");
    header("Location: index.php");
    exit();
}

$error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    error_log("=== Login Attempt Details ===");
    error_log("Email: " . $email);
    error_log("Password provided: " . (!empty($password) ? 'Yes' : 'No'));
    error_log("POST data: " . print_r($_POST, true));
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
        error_log("Empty fields - Email: " . (empty($email) ? 'missing' : 'provided') . 
                 ", Password: " . (empty($password) ? 'missing' : 'provided'));
    } else {
        try {
            // First check if student exists
            $check_sql = "SELECT * FROM students WHERE email = ? AND is_active = 1";
            $check_stmt = $conn->prepare($check_sql);
            
            if ($check_stmt === false) {
                throw new Exception("Database error (check): " . $conn->error);
            }
            
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $error = "No student found with this email address.";
                error_log("No student found - Email: $email");
            } else {
                $student = $check_result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $student['password'])) {
                    // Check if feedback already submitted
                    $academic_year = getCurrentAcademicYear();
                    $check_feedback_sql = "SELECT id FROM feedback_submissions WHERE student_email = ? AND academic_year = ?";
                    $check_feedback_stmt = $conn->prepare($check_feedback_sql);
                    $check_feedback_stmt->bind_param("ss", $email, $academic_year);
                    $check_feedback_stmt->execute();
                    $feedback_result = $check_feedback_stmt->get_result();
                    
                    if ($feedback_result->num_rows > 0) {
                        echo "<script>alert('You have already submitted feedback for this academic year.'); window.location.href = 'logout.php';</script>";
                        exit();
                    }
                    
                    // Get additional student details
                    $sql = "SELECT s.*, c.class_name, c.department 
                           FROM students s 
                           LEFT JOIN classes c ON s.class_id = c.id 
                           WHERE s.id = ? AND s.is_active = 1";
                           
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt === false) {
                        throw new Exception("Database error (details): " . $conn->error);
                    }
                    
                    $stmt->bind_param("i", $student['id']);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error executing query: " . $stmt->error);
                    }
                    
                    $result = $stmt->get_result();
                    $student = $result->fetch_assoc();
                    
                    // Set session variables
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_email'] = $student['email'];
                    $_SESSION['student_name'] = $student['full_name'];
                    $_SESSION['class_id'] = $student['class_id'];
                    $_SESSION['department'] = $student['department'];
                    
                    error_log("Login successful - Student ID: " . $student['id']);
                    
                    // Redirect to feedback form
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid password.";
                    error_log("Invalid password - Email: $email");
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
            // Add debug information during development
            $debug_info = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - MGM College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 80px;
            margin-bottom: 15px;
        }
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
        }
        .form-label {
            font-weight: 500;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="images/logo.png" alt="MGM Logo">
            <h4>Mahatma Gandhi Mission's</h4>
            <h5>College of Engineering, Nanded</h5>
            <h6>Student Feedback System</h6>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required
                       placeholder="Enter your password">
            </div>
            <button type="submit" name="login" class="btn btn-primary btn-login" value="1">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="admin/login.php" class="text-decoration-none">
                <i class="fas fa-user-shield me-1"></i>Admin Login
            </a>
        </div>
    </div>
    
    <?php if (!empty($debug_info)): ?>
    <div class="debug-info">
        <strong>Debug Info:</strong><br>
        <?php echo htmlspecialchars($debug_info); ?>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 