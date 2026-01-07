<?php
session_start();
require_once 'config.php';
require_once '../vendor/autoload.php';
requireAdminLogin();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set page title
$page_title = "Teacher Appreciation";

// Get filter parameters
$department = isset($_GET['department']) ? $_GET['department'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';

// Handle AJAX request for getting teacher email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_teacher_email') {
    try {
        $teacher_id = $_POST['teacher_id'];
        // $conn is already available from config.php
        // Get teacher's email
        $stmt = $conn->prepare("SELECT email FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'email' => $row['email']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Teacher not found'
            ]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle AJAX request for sending email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    header('Content-Type: application/json');
    try {
        // Validate input
        if (empty($_POST['teacher_id'])) {
            throw new Exception("Teacher ID is required");
        }
        if (empty($_POST['content'])) {
            throw new Exception("Letter content is required");
        }
        $teacher_id = $_POST['teacher_id'];
        $subject = $_POST['subject'] ?? 'Appreciation Letter';
        $content = $_POST['content'];
        // $conn is already available from config.php
        // Get teacher's email
        $stmt = $conn->prepare("SELECT email, name FROM teachers WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $teacher_id);
        if (!$stmt->execute()) {
            throw new Exception("Database execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Teacher not found with ID: " . $teacher_id);
        }
        $row = $result->fetch_assoc();
        error_log("Teacher data retrieved: " . print_r($row, true));
        if (empty($row['email'])) {
            throw new Exception("Teacher email is not set in the database");
        }
require_once 'config/mail_config.php';

        // Configure PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->SMTPDebug = 0; // Disable verbose debug output for production
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            error_log("Attempting to send email to: " . $row['email']);
            $mail->addAddress($row['email'], $row['name']);
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = nl2br($content);
            $mail->AltBody = strip_tags($content);
            // Send email
            if (!$mail->send()) {
                throw new Exception("Email could not be sent. Mailer Error: " . $mail->ErrorInfo);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Appreciation letter sent successfully to ' . $row['email']
            ]);
        } catch (Exception $e) {
            throw new Exception("PHPMailer Error: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Error in send_email: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Get filter options
$dept_query = "SELECT DISTINCT department FROM classes WHERE department IS NOT NULL ORDER BY department";
$dept_result = $conn->query($dept_query);

$sem_query = "SELECT DISTINCT semester FROM classes WHERE semester IS NOT NULL ORDER BY semester";
$sem_result = $conn->query($sem_query);

$year_query = "SELECT DISTINCT academic_year FROM feedback_submissions ORDER BY academic_year DESC";
$year_result = $conn->query($year_query);

// Base query for teacher performance
$query = "SELECT 
            t.id,
            t.name as teacher_name,
            t.department,
            COUNT(DISTINCT fs.id) as total_feedback,
            COALESCE(AVG(fr.rating), 0) as avg_rating,
            CASE 
                WHEN AVG(fr.rating) IS NOT NULL 
                THEN COALESCE(AVG(fr.rating), 0) * 25
                ELSE 0
            END AS appreciation_percentage

          FROM teachers t
          LEFT JOIN course_teachers ct ON t.id = ct.teacher_id
          LEFT JOIN courses c ON ct.course_id = c.id
          LEFT JOIN feedback_ratings fr ON c.id = fr.course_id
          LEFT JOIN feedback_submissions fs ON fr.submission_id = fs.id
          WHERE 1=1";

$params = array();
$types = "";

if ($department) {
    $query .= " AND t.department = ?";
    $params[] = $department;
    $types .= "s";
}

if ($semester) {
    $query .= " AND c.semester = ?";
    $params[] = $semester;
    $types .= "s";
}

if ($academic_year) {
    $query .= " AND fs.academic_year = ?";
    $params[] = $academic_year;
    $types .= "s";
}

$query .= " GROUP BY t.id, t.name, t.department
            ORDER BY appreciation_percentage DESC, avg_rating DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include 'header.php';
?>

<div class="container-fluid">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filter Teachers
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php while ($dept = $dept_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($dept['department']); ?>"
                                <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['department']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">All Semesters</option>
                        <?php while ($sem = $sem_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($sem['semester']); ?>"
                                <?php echo $semester === $sem['semester'] ? 'selected' : ''; ?>>
                            Semester <?php echo htmlspecialchars($sem['semester']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Academic Year</label>
                    <select name="academic_year" class="form-select">
                        <option value="">All Years</option>
                        <?php while ($year = $year_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                <?php echo $academic_year === $year['academic_year'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year['academic_year']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="appreciation.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Teachers Performance Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-award me-2"></i>Teacher Performance
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="teachersTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Teacher Name</th>
                            <th>Department</th>
                            <th>Total Feedback</th>
                            <th>Average Rating</th>
                            <th>Appreciation %</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                            <td><?php echo $teacher['total_feedback']; ?></td>
                            <td><?php echo number_format($teacher['avg_rating'], 2); ?></td>
                            <td><?php echo number_format($teacher['appreciation_percentage'], 1); ?>%</td>
                            <td>
                                <?php
                                $percentage = $teacher['appreciation_percentage'];
                                if ($percentage >= 80) {
                                    echo '<span class="badge bg-success">Excellent</span>';
                                } elseif ($percentage >= 60) {
                                    echo '<span class="badge bg-info">Good</span>';
                                } elseif ($percentage >= 40) {
                                    echo '<span class="badge bg-warning">Average</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Needs Improvement</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-success btn-sm send-letter" 
                                        data-teacher-id="<?php echo $teacher['id']; ?>"
                                        data-teacher-name="<?php echo htmlspecialchars($teacher['teacher_name']); ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#appreciationLetterModal">
                                    <i class="fas fa-envelope me-1"></i> Send Letter
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Appreciation Letter Modal -->
<div class="modal fade" id="appreciationLetterModal" tabindex="-1" aria-labelledby="appreciationLetterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="appreciationLetterModalLabel">Send Appreciation Letter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="appreciationLetterForm">
                    <input type="hidden" id="teacherId" name="teacher_id">
                    <div class="mb-3">
                        <label for="teacherName" class="form-label">Teacher Name</label>
                        <input type="text" class="form-control" id="teacherName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="letterSubject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="letterSubject" name="subject" 
                               value="Appreciation Letter for Outstanding Performance" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="letterContent" class="form-label">Letter Content</label>
                        <textarea class="form-control" id="letterContent" name="content" rows="10" readonly style="white-space: pre-wrap;"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sendLetterBtn">
                    <i class="fas fa-paper-plane me-1"></i> Send Letter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable with proper cleanup
    if ($.fn.DataTable.isDataTable('#teachersTable')) {
        $('#teachersTable').DataTable().destroy();
    }
    
    $('#teachersTable').DataTable({
        order: [[4, 'desc']], // Sort by appreciation percentage by default
        pageLength: 25,
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search teachers...",
            lengthMenu: "_MENU_ records per page",
        }
    });

    // Letter template
    const letterTemplate = `Dear [Teacher Name],

I am writing to express our sincere appreciation for your outstanding contributions to our institution. Your dedication and commitment to excellence in teaching have not gone unnoticed.

Your performance metrics show remarkable achievement:
- Appreciation Percentage: [Percentage]
- Average Rating: [Rating]

Your dedication to teaching, academic advancement, punctuality, and comprehensive coverage of the syllabus has been remarkable. Your commitment to impactful research endeavors and your interaction with the industry have been instrumental in shaping a holistic learning environment.

Your effectiveness as a teacher is truly commendable. You have a remarkable ability to inspire and motivate students, fostering an environment where both students and teachers grow together.

Your efforts in making the learning process enjoyable and effective are highly valued. Your contributions in mentoring students and fostering a desire for excellence in their academic pursuits have not gone unnoticed.

Thank you for your continued hard work and dedication. Your contributions are invaluable and deeply appreciated.

Best regards,
MGM College Administration`;

    // Handle send letter button click
    $('.send-letter').click(function() {
        const teacherId = $(this).data('teacher-id');
        const teacherName = $(this).data('teacher-name');
        const percentage = $(this).closest('tr').find('td:eq(4)').text().trim();
        const rating = $(this).closest('tr').find('td:eq(3)').text().trim();

        // Set teacher information
        $('#teacherId').val(teacherId);
        $('#teacherName').val(teacherName);

        // Replace placeholders in the letter template
        let content = letterTemplate;
        content = content.replace(/\[Teacher Name\]/g, teacherName);
        content = content.replace(/\[Percentage\]/g, percentage);
        content = content.replace(/\[Rating\]/g, rating);

        // Set the letter content
        $('#letterContent').val(content);
    });

    // Handle send letter form submission
    $('#sendLetterBtn').click(function() {
        const teacherId = $('#teacherId').val();
        const teacherName = $('#teacherName').val();
        const subject = $('#letterSubject').val();
        const content = $('#letterContent').val();

        // Show loading state
        $('#sendLetterBtn').prop('disabled', true).text('Sending...');

        // Send email
        $.ajax({
            url: 'appreciation.php',
            method: 'POST',
            data: {
                action: 'send_email',
                teacher_id: teacherId,
                subject: subject,
                content: content
            },
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    alert(response.message);
                    $('#appreciationLetterModal').modal('hide');
                } else {
                    alert('Error: ' + (response.message || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                let errorMessage = 'Error sending email. ';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage += response.message || error;
                } catch (e) {
                    errorMessage += error;
                }
                
                alert(errorMessage);
            },
            complete: function() {
                // Reset button state
                $('#sendLetterBtn').prop('disabled', false).text('Send');
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?> 