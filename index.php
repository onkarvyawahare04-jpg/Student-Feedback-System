<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("=== Starting Feedback Form ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));
error_log("Session Data: " . print_r($_SESSION, true));

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    error_log("No student_id in session at index.php");
    $_SESSION['login_error'] = "Please log in to continue.";
    header("Location: student_login.php");
    exit();
}

require_once 'check_student_login.php';
require_once 'admin/config.php';

// Define the steps and their order
$steps = ['theory', 'practical', 'general'];

// Get current page from POST, GET, or default to first step
$current_page = isset($_POST['page']) ? $_POST['page'] : 
               (isset($_GET['page']) ? $_GET['page'] : $steps[0]);

// Validate the page
if (!in_array($current_page, $steps)) {
    error_log("Invalid page requested: " . $current_page);
    $current_page = $steps[0];
}

$current_step = array_search($current_page, $steps) + 1;
$total_steps = count($steps);
error_log("Current Page: " . $current_page . ", Current Step: " . $current_step . ", Total Steps: " . $total_steps);

// Handle form navigation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request in index.php");
    error_log("POST data: " . print_r($_POST, true));

    // Initialize feedback data array in session if not exists
    if (!isset($_SESSION['feedback_data'])) {
        $_SESSION['feedback_data'] = array();
    }

    // Store current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'rating_') === 0 || strpos($key, 'text_') === 0) {
            $_SESSION['feedback_data'][$key] = $value;
        }
    }

    error_log("Updated session feedback data: " . print_r($_SESSION['feedback_data'], true));

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'next':
                // Move to next page
                $next_step = min($current_step, count($steps) - 1);
                $current_page = $steps[$next_step];
                error_log("Moving to next page: " . $current_page);
                header("Location: index.php?page=" . $current_page);
                exit();
                break;

            case 'previous':
                // Move to previous page
                $prev_step = max(0, $current_step - 2);
                $current_page = $steps[$prev_step];
                error_log("Moving to previous page: " . $current_page);
                header("Location: index.php?page=" . $current_page);
                exit();
                break;

            case 'submit':
                error_log("Submitting final feedback");
                // Log rating data being sent
                error_log("Rating data being sent: " . print_r($_SESSION['feedback_data'], true));
                // Redirect to submission handler
                header("Location: submit_feedback.php");
                exit();
                break;
        }
    }
}

// Get student details from database
$stmt = $conn->prepare("SELECT s.*, c.class_name, c.department, c.semester 
                       FROM students s 
                       LEFT JOIN classes c ON s.class_id = c.id 
                       WHERE s.id = ? AND s.is_active = 1");

if (!$stmt) {
    error_log("Failed to prepare student query: " . $conn->error);
    $_SESSION['login_error'] = "Database error. Please try again.";
    header("Location: student_login.php");
    exit();
}

$stmt->bind_param("i", $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    error_log("Failed to fetch student data for ID: " . $_SESSION['student_id']);
    $_SESSION['login_error'] = "Student data not found. Please login again.";
    header("Location: student_login.php");
    exit();
}

// Get questions based on current page
if ($current_page === 'general') {
    $sql = "SELECT * FROM questions WHERE category = 'general' AND question_type = 'text' AND is_active = 1 ORDER BY id";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM questions WHERE category = ? AND question_type = 'rating' AND is_active = 1 ORDER BY id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_page);
}

if (!$stmt || !$stmt->execute()) {
    error_log("Error fetching questions: " . $conn->error);
    $_SESSION['login_error'] = "Error loading questions. Please try again.";
    echo "<script>alert('Error: " . addslashes($conn->error) . "\\n\\nPlease check the error logs for more details.'); window.location.href = 'student_login.php';</script>";
    exit();
}

$questions_result = $stmt->get_result();
$rating_questions = [];
$text_questions = [];

while ($question = $questions_result->fetch_assoc()) {
    if ($question['question_type'] === 'rating') {
        $rating_questions[] = $question;
    } else {
        $text_questions[] = $question;
    }
}

// Get courses for non-general pages
$courses_array = [];
if ($current_page !== 'general') {
    $sql = "SELECT c.*, t.name as teacher_name, t.id as teacher_id 
            FROM courses c 
            LEFT JOIN course_teachers ct ON c.id = ct.course_id 
            LEFT JOIN teachers t ON ct.teacher_id = t.id 
            WHERE c.department = ? AND c.course_type = ?
            ORDER BY c.course_name";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt || !$stmt->bind_param("ss", $student['department'], $current_page) || !$stmt->execute()) {
        error_log("Error fetching courses: " . $conn->error);
        $_SESSION['login_error'] = "Error loading courses. Please try again.";
        echo "<script>alert('Error: " . addslashes($conn->error) . "\\n\\nPlease check the error logs for more details.'); window.location.href = 'student_login.php';</script>";
        exit();
    }
    
    $result = $stmt->get_result();
    while ($course = $result->fetch_assoc()) {
        $courses_array[] = $course;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedback - MGM College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .feedback-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .feedback-header img {
            max-width: 80px;
            margin-bottom: 15px;
        }
        .session-info {
            margin-bottom: 20px;
            font-size: 14px;
        }
        .instructions {
            margin-bottom: 30px;
            font-size: 14px;
            text-align: justify;
        }
        .rating-scale {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .rating-scale .row {
            margin-bottom: 5px;
        }
        .feedback-table {
            margin-top: 20px;
            font-size: 14px;
        }
        .feedback-table th {
            text-align: center;
            vertical-align: middle;
            background-color: #f8f9fa;
        }
        .feedback-table td {
            vertical-align: middle;
        }
        .rating-cell {
            width: 60px;
            text-align: center;
            padding: 5px !important;
        }
        .question-cell {
            width: 40%;
            font-size: 13px;
            padding: 8px !important;
        }
        .teacher-header {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            background-color: #f8f9fa !important;
        }
        .question-number {
            width: 40px;
            text-align: center;
            font-weight: bold;
        }
        .text-question {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .progress-indicator {
            margin: 20px auto;
            max-width: 600px;
        }
        .progress-indicator .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            margin: 0 auto;
        }
        .progress-indicator .step.active {
            background-color: #0d6efd;
        }
        .progress-indicator .step.completed {
            background-color: #198754;
        }
        .progress-indicator .line {
            flex-grow: 1;
            height: 2px;
            background-color: #dee2e6;
        }
        .progress-indicator .line.completed {
            background-color: #198754;
        }
        .step-label {
            text-align: center;
            font-size: 12px;
            margin-top: 5px;
            color: #6c757d;
        }
        .step-label.active {
            color: #0d6efd;
            font-weight: bold;
        }
        @media print {
            body {
                background: none;
            }
            .page {
                margin: 0;
                padding: 20mm;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
        .submit-text {
            margin-right: 0.5rem;
        }
        .feedback-table th, .feedback-table td { text-align: center; vertical-align: middle; }
        .feedback-table th { background: #f8f9fa; }
        .feedback-table { width: 100%; border-collapse: collapse; }
        .feedback-table th, .feedback-table td { border: 1px solid #ccc; padding: 6px; }
        .legend-table th, .legend-table td { border: 1px solid #ccc; padding: 4px; text-align: center; }
        .legend-table { margin-bottom: 10px; }
        .mapping-table th, .mapping-table td { border: 1px solid #ccc; padding: 4px; text-align: center; }
        .mapping-table { margin-bottom: 10px; }
        .custom-rating-cell {
            position: relative;
            width: 60px;
            text-align: center;
        }
        .custom-rating-value {
            display: inline-block;
            width: 32px;
            height: 32px;
            line-height: 32px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            user-select: none;
        }
        .custom-rating-select {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 1;
            z-index: 10;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Progress Indicator -->
    <div class="container mt-3 no-print">
        <div class="progress-indicator d-flex align-items-center">
            <?php
            $step_labels = ['Theory Subjects', 'Practical Subjects', 'General Feedback'];
            foreach ($steps as $index => $step) {
                $is_active = $current_page === $step;
                $is_completed = array_search($step, $steps) < array_search($current_page, $steps);
                
                if ($index > 0) {
                    echo '<div class="line' . ($is_completed ? ' completed' : '') . '"></div>';
                }
                
                echo '<div class="d-flex flex-column align-items-center">';
                echo '<div class="step' . ($is_active ? ' active' : '') . ($is_completed ? ' completed' : '') . '">';
                echo $is_completed ? '<i class="fas fa-check"></i>' : ($index + 1);
                echo '</div>';
                echo '<div class="step-label' . ($is_active ? ' active' : '') . '">' . $step_labels[$index] . '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="feedback-header">
            <img src="images/logo.png" alt="MGM Logo">
            <h4>Mahatma Gandhi Mission's</h4>
            <h3>College of Engineering, Nanded - 431605</h3>
            <h4>FEEDBACK FORM</h4>
            <h5 class="mt-2">
                <?php 
                echo $current_page === 'theory' ? 'Theory Subjects Feedback' : 
                    ($current_page === 'practical' ? 'Practical Subjects Feedback' : 'General Feedback');
                ?>
            </h5>
        </div>

        <!-- Session Info -->
        <div class="session-info">
            <div class="row">
                <div class="col-6">
                    <strong>Session:</strong> 20<?php echo date('y')-1; ?> - <?php echo date('y'); ?>
                </div>
                <div class="col-6">
                    <strong>Date:</strong> <?php echo date('d-m-Y'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <strong>Class:</strong> <?php echo isset($student['class_name']) ? htmlspecialchars($student['class_name']) : 'N/A'; ?>
                </div>
                <div class="col-6">
                    <strong>Semester:</strong> <?php echo isset($student['semester']) ? htmlspecialchars($student['semester']) : 'N/A'; ?>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <p><strong>Dear Student,</strong></p>
            <p>For the improvement of general academic environment of the college your sincere feedback is expected. Please indicate one of the following numbers in the spaces provided in columns for each subject as your comparative assessment of the quality of teaching.</p>
        </div>

        <!-- Rating Scale -->
        <?php if ($current_page !== 'general'): ?>
        <div class="rating-scale">
            <div class="row">
                <div class="col-3 text-center"><strong>4</strong> - Excellent</div>
                <div class="col-3 text-center"><strong>3</strong> - Good/Adequate</div>
                <div class="col-3 text-center"><strong>2</strong> - Average/Satisfactory</div>
                <div class="col-3 text-center"><strong>1</strong> - Poor/Unsatisfactory</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mapping Table (above feedback questions) -->
        <?php if ($current_page !== 'general' && !empty($courses_array)): ?>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle text-center">
                <thead>
                    <tr>
                        <th>Sr.No.</th>
                        <th>Name of the Theory Course</th>
                        <th>Name of the Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($courses_array as $i => $course): ?>
                    <tr>
                        <td><?php echo 'CR' . ($i+1); ?></td>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Feedback Form -->
        <form method="POST" <?php echo ($current_step === count($steps)) ? 'action="submit_feedback.php"' : ''; ?> id="feedbackForm" autocomplete="off">
            <!-- Debug info -->
            <input type="hidden" name="debug_step" value="<?php echo $current_step; ?>">
            <input type="hidden" name="debug_total" value="<?php echo count($steps); ?>">
            <!-- Essential data -->
            <input type="hidden" name="page" value="<?php echo $current_page; ?>">
            <input type="hidden" name="student_id" value="<?php echo $_SESSION['student_id']; ?>">
            <?php if ($current_step === count($steps) && isset($_SESSION['feedback_data'])): ?>
                <?php foreach ($_SESSION['feedback_data'] as $key => $value): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Rating Questions Table -->
            <?php if ($current_page !== 'general' && !empty($courses_array)): ?>
            <div class="table-responsive">
                <table class="table table-bordered feedback-table">
                    <thead>
                        <tr>
                            <th class="question-number">No.</th>
                            <th>Questions</th>
                            <?php for($i=0; $i<count($courses_array); $i++): ?>
                                <th><?php echo 'CR' . ($i+1); ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rating_questions as $index => $question) {
                            echo '<tr>';
                            echo '<td class="question-number">' . ($index + 1) . '</td>';
                            echo '<td class="question-cell">' . htmlspecialchars($question['question_text']) . '</td>';
                            foreach($courses_array as $i => $course) {
                                $field_name = 'rating_' . $course['id'] . '_' . $question['id'];
                                $selected_value = isset($_SESSION['feedback_data'][$field_name]) ? $_SESSION['feedback_data'][$field_name] : '';
                                $display_value = $selected_value !== '' ? $selected_value : '-';
                                echo '<td class="custom-rating-cell">';
                                echo '<span class="custom-rating-value" tabindex="0">' . $display_value . '</span>';
                                echo '<select class="form-select form-select-sm custom-rating-select" name="' . $field_name . '" required>';
                                echo '<option value="">-</option>';
                                for($r = 4; $r >= 1; $r--) {
                                    $selected = ($selected_value == $r) ? ' selected' : '';
                                    echo '<option value="' . $r . '"' . $selected . '>' . $r . '</option>';
                                }
                                echo '</select>';
                                echo '</td>';
                            }
                            echo '</tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <!-- Text Questions -->
            <?php if (!empty($text_questions)): ?>
            <div class="text-questions mt-4">
                <h5 class="mb-3">Additional Feedback</h5>
                <?php foreach($text_questions as $index => $question): ?>
                <?php 
                    $field_name = 'text_' . $question['id'];
                    $saved_value = isset($_SESSION['feedback_data'][$field_name]) ? $_SESSION['feedback_data'][$field_name] : '';
                ?>
                <div class="text-question">
                    <label class="form-label">
                        <strong><?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?></strong>
                    </label>
                    <textarea class="form-control" name="<?php echo $field_name; ?>" rows="3" required><?php echo htmlspecialchars($saved_value); ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between mt-4 no-print">
                <?php if ($current_step > 1): ?>
                <button type="submit" name="action" value="previous" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
                <?php if ($current_step < count($steps)): ?>
                <button type="submit" name="action" value="next" class="btn btn-primary">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
                <?php else: ?>
                <button type="submit" name="action" value="submit" class="btn btn-success" id="submitBtn">
                    <span class="submit-text">Submit Feedback</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('feedbackForm');
        // Custom dropdown for rating fields
        form.querySelectorAll('.custom-rating-cell').forEach(function(cell) {
            const span = cell.querySelector('.custom-rating-value');
            const select = cell.querySelector('.custom-rating-select');
            // Show select on single click
            span.addEventListener('mousedown', function(e) {
                e.preventDefault(); // Prevent text selection
                span.style.display = 'none';
                select.style.display = 'inline-block';
                select.focus();
            });
            // Hide select and update span on change or blur
            function hideSelect() {
                const val = select.value || '-';
                span.textContent = val;
                select.style.display = 'none';
                span.style.display = 'inline-block';
            }
            select.addEventListener('change', hideSelect);
            select.addEventListener('blur', hideSelect);
        });
        form.addEventListener('submit', function(event) {
            const requiredFields = form.querySelectorAll('select[required], textarea[required]');
            let isValid = true;
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            if (!isValid) {
                event.preventDefault();
                alert('Please fill in all required fields before proceeding.');
                return false;
            }
            // Show spinner and disable button on submit
            if (event.submitter && event.submitter.value === 'submit') {
                const submitBtn = document.getElementById('submitBtn');
                const submitText = submitBtn.querySelector('.submit-text');
                const spinner = submitBtn.querySelector('.spinner-border');
                submitText.textContent = 'Submitting...';
                spinner.classList.remove('d-none');
                submitBtn.disabled = true;
            }
            return true;
        });
    });
    </script>
</body>
</html>
