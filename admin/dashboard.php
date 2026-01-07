<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get quick statistics
$stats = array();

// Total Students
$result = $conn->query("SELECT COUNT(*) as count FROM students");
$stats['total_students'] = $result->fetch_assoc()['count'];

// Total Teachers
$result = $conn->query("SELECT COUNT(*) as count FROM teachers");
$stats['total_teachers'] = $result->fetch_assoc()['count'];

// Total Courses
$result = $conn->query("SELECT COUNT(*) as count FROM courses");
$stats['total_courses'] = $result->fetch_assoc()['count'];

// Recent Feedback Count (last 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback_submissions 
                       WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$stats['recent_feedback'] = $stmt->get_result()->fetch_assoc()['count'];

// Get recent feedback submissions
$stmt = $conn->prepare("SELECT fs.*, s.email as student_email, c.class_name 
                       FROM feedback_submissions fs
                       JOIN students s ON fs.student_email = s.email
                       JOIN classes c ON fs.class_id = c.id
                       ORDER BY fs.submission_date DESC LIMIT 5");
$stmt->execute();
$recent_submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set page title for header
$page_title = "Dashboard";

// Include header
include 'header.php';
?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Total Students</h6>
                        <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Total Teachers</h6>
                        <h3 class="mb-0"><?php echo $stats['total_teachers']; ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Total Courses</h6>
                        <h3 class="mb-0"><?php echo $stats['total_courses']; ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Recent Feedback</h6>
                        <h3 class="mb-0"><?php echo $stats['recent_feedback']; ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Submissions -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Recent Feedback Submissions</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_submissions as $submission): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($submission['student_email']); ?></td>
                        <td><?php echo htmlspecialchars($submission['class_name']); ?></td>
                        <td><?php echo date('M d, Y ', strtotime($submission['submission_date'])); ?></td>
                        <td>
                            <a href="view_feedback.php?id=<?php echo $submission['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Close the main-content and content-wrapper divs from header.php
?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 