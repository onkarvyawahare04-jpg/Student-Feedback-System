<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Get filter parameters
$department = isset($_GET['department']) ? $_GET['department'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';
$rating = isset($_GET['rating']) ? $_GET['rating'] : '';

// Check if viewing specific feedback
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    // Get feedback submission details
    $stmt = $conn->prepare("SELECT fs.*, s.email as student_email, s.full_name as student_name, 
                                  c.class_name, c.department, c.semester
                           FROM feedback_submissions fs
                           JOIN students s ON fs.student_email = s.email
                           JOIN classes c ON fs.class_id = c.id
                           WHERE fs.id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $feedback = $stmt->get_result()->fetch_assoc();

    if (!$feedback) {
        header("Location: view_feedback.php");
        exit();
    }

    // Get rating feedback
    $stmt = $conn->prepare("SELECT fr.*, c.course_name, c.course_code, q.question_text 
                           FROM feedback_ratings fr
                           JOIN courses c ON fr.course_id = c.id
                           JOIN questions q ON fr.question_id = q.id
                           WHERE fr.submission_id = ?
                           ORDER BY c.course_name, q.id");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $ratings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get text feedback
    $stmt = $conn->prepare("SELECT ftr.*, q.question_text 
                           FROM feedback_text_responses ftr
                           JOIN questions q ON ftr.question_id = q.id
                           WHERE ftr.submission_id = ?
                           ORDER BY q.id");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $text_responses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Group ratings by course
    $course_ratings = [];
    foreach ($ratings as $rating) {
        $course_id = $rating['course_id'];
        if (!isset($course_ratings[$course_id])) {
            $course_ratings[$course_id] = [
                'course_name' => $rating['course_name'],
                'course_code' => $rating['course_code'],
                'ratings' => []
            ];
        }
        $course_ratings[$course_id]['ratings'][] = $rating;
    }

    $page_title = "View Feedback Details";
} else {
    // Get filter options
    $dept_query = "SELECT DISTINCT department FROM classes WHERE department IS NOT NULL ORDER BY department";
    $dept_result = $conn->query($dept_query);

    $sem_query = "SELECT DISTINCT semester FROM classes WHERE semester IS NOT NULL ORDER BY semester";
    $sem_result = $conn->query($sem_query);

    $year_query = "SELECT DISTINCT academic_year FROM feedback_submissions ORDER BY academic_year DESC";
    $year_result = $conn->query($year_query);

    $course_query = "SELECT DISTINCT c.id, c.course_name FROM courses c 
                     JOIN feedback_ratings fr ON c.id = fr.course_id 
                     ORDER BY c.course_name";
    $course_result = $conn->query($course_query);

    // Base query for feedback list
    $query = "SELECT DISTINCT
                fs.id, fs.submission_date, fs.academic_year,
                s.full_name as student_name, s.email as student_email,
                c.class_name, c.department, c.semester
              FROM feedback_submissions fs
              JOIN students s ON fs.student_email = s.email
              JOIN classes c ON fs.class_id = c.id
              LEFT JOIN feedback_ratings fr ON fs.id = fr.submission_id
              WHERE 1=1";

    // Add filters
    if ($department) {
        $query .= " AND c.department = '" . $conn->real_escape_string($department) . "'";
    }
    if ($semester) {
        $query .= " AND c.semester = '" . $conn->real_escape_string($semester) . "'";
    }
    if ($academic_year) {
        $query .= " AND fs.academic_year = '" . $conn->real_escape_string($academic_year) . "'";
    }
    if ($from_date) {
        $query .= " AND DATE(fs.submission_date) >= '" . $conn->real_escape_string($from_date) . "'";
    }
    if ($to_date) {
        $query .= " AND DATE(fs.submission_date) <= '" . $conn->real_escape_string($to_date) . "'";
    }
    if ($course) {
        $query .= " AND EXISTS (SELECT 1 FROM feedback_ratings fr2 
                               WHERE fr2.submission_id = fs.id 
                               AND fr2.course_id = " . intval($course) . ")";
    }
    if ($rating) {
        $query .= " AND EXISTS (SELECT 1 FROM feedback_ratings fr3 
                               WHERE fr3.submission_id = fs.id 
                               AND fr3.rating = " . intval($rating) . ")";
    }

    $query .= " ORDER BY fs.submission_date DESC";
    $result = $conn->query($query);

    $page_title = "View Feedback";
}

// Set page title for header
$page_title = "View Feedback";

// Include header
include 'header.php';
?>

<?php if (isset($feedback)): ?>
<!-- Feedback Details View -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Feedback Details</h4>
        <a href="view_feedback.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Feedback Details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($feedback['student_name']); ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($feedback['student_email']); ?></p>
                    <p class="mb-1"><strong>Class:</strong> <?php echo htmlspecialchars($feedback['class_name']); ?></p>
                    <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($feedback['department']); ?></p>
                    <p class="mb-0"><strong>Semester:</strong> <?php echo htmlspecialchars($feedback['semester']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Submission Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><strong>Submitted:</strong> <?php echo date('F j, Y, g:i a', strtotime($feedback['submission_date'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Ratings -->
    <?php if (!empty($course_ratings)): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Course Ratings</h5>
        </div>
        <div class="card-body">
            <?php foreach ($course_ratings as $course): ?>
            <div class="course-feedback mb-4">
                <h6 class="mb-3"><?php echo htmlspecialchars($course['course_name']); ?> (<?php echo htmlspecialchars($course['course_code']); ?>)</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th width="100">Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course['ratings'] as $rating): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rating['question_text']); ?></td>
                                <td>
                                    <span class="rating-value rating-<?php echo $rating['rating']; ?>">
                                        <?php echo $rating['rating']; ?>/4
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Text Responses -->
    <?php if (!empty($text_responses)): ?>
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">General Feedback</h5>
        </div>
        <div class="card-body">
            <?php foreach ($text_responses as $response): ?>
            <div class="mb-4">
                <h6 class="text-muted mb-2"><?php echo htmlspecialchars($response['question_text']); ?></h6>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($response['response'])); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Feedback List View -->
<div class="container-fluid">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filter Feedback
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

                <div class="col-md-2">
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

                <div class="col-md-2">
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

                <div class="col-md-3">
                    <label class="form-label">Course</label>
                    <select name="course" class="form-select">
                        <option value="">All Courses</option>
                        <?php while ($c = $course_result->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>"
                                <?php echo $course == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Rating</label>
                    <select name="rating" class="form-select">
                        <option value="">All Ratings</option>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $rating == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?> Star
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Apply Filters
                    </button>
                    <a href="view_feedback.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Feedback List -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-comments me-2"></i>Feedback Submissions
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row['student_name']); ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($row['student_email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td>Semester <?php echo htmlspecialchars($row['semester']); ?></td>
                                <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($row['submission_date'])); ?></td>
                                <td>
                                    <a href="?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No feedback submissions found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Custom styles -->
<style>
.rating-value {
    font-weight: bold;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}
.rating-1 { background-color: #dc3545; color: white; }
.rating-2 { background-color: #ffc107; color: black; }
.rating-3 { background-color: #0dcaf0; color: black; }
.rating-4 { background-color: #198754; color: white; }
</style>

<?php include 'footer.php'; ?>