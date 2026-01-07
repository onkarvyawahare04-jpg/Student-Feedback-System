<?php
session_start();
require_once 'config.php';
requireAdminLogin();

// Get filter parameters
$department = isset($_GET['department']) ? $_GET['department'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : '';

// Get filter options
$dept_query = "SELECT DISTINCT department FROM classes WHERE department IS NOT NULL ORDER BY department";
$dept_result = $conn->query($dept_query);

$sem_query = "SELECT DISTINCT semester FROM classes WHERE semester IS NOT NULL ORDER BY semester";
$sem_result = $conn->query($sem_query);

$year_query = "SELECT DISTINCT academic_year FROM feedback_submissions ORDER BY academic_year DESC";
$year_result = $conn->query($year_query);

$course_query = "SELECT id, course_name, course_code FROM courses ORDER BY course_name";
$course_result = $conn->query($course_query);

$teacher_query = "SELECT id, name FROM teachers ORDER BY name";
$teacher_result = $conn->query($teacher_query);

// Base query for feedback data
$query = "SELECT 
            fs.id, fs.submission_date, fs.academic_year,
            c.class_name, c.department, c.semester,
            co.course_name, co.course_code, co.id as course_id,
            t.name as teacher_name, t.id as teacher_id,
            fr.rating,
            q.question_text, q.category,
            AVG(fr.rating) as avg_rating,
            COUNT(DISTINCT fs.id) as total_responses
          FROM feedback_submissions fs
          JOIN classes c ON fs.class_id = c.id
          JOIN feedback_ratings fr ON fs.id = fr.submission_id
          JOIN courses co ON fr.course_id = co.id
          JOIN course_teachers ct ON co.id = ct.course_id
          JOIN teachers t ON ct.teacher_id = t.id
          JOIN questions q ON fr.question_id = q.id
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
if ($course_id) {
    $query .= " AND co.id = " . intval($course_id);
}
if ($teacher_id) {
    $query .= " AND t.id = " . intval($teacher_id);
}

$query .= " GROUP BY co.id, t.id, q.category
           ORDER BY co.course_name, t.name";

$result = $conn->query($query);

// Calculate performance metrics
$performance_data = [];
$course_ratings = [];
$teacher_ratings = [];
$category_ratings = [];
$teacher_course_ratings = [];

while ($row = $result->fetch_assoc()) {
    $course_key = $row['course_code'];
    $teacher_key = $row['teacher_id'];
    $category = $row['category'];
    $teacher_course_key = $row['teacher_id'] . '_' . $row['course_id'];
    
    // Course performance
    if (!isset($course_ratings[$course_key])) {
        $course_ratings[$course_key] = [
            'name' => $row['course_name'],
            'code' => $row['course_code'],
            'total_ratings' => 0,
            'sum_ratings' => 0
        ];
    }
    $course_ratings[$course_key]['total_ratings'] += $row['total_responses'];
    $course_ratings[$course_key]['sum_ratings'] += ($row['avg_rating'] * $row['total_responses']);
    
    // Teacher performance
    if (!isset($teacher_ratings[$teacher_key])) {
        $teacher_ratings[$teacher_key] = [
            'name' => $row['teacher_name'],
            'total_ratings' => 0,
            'sum_ratings' => 0
        ];
    }
    $teacher_ratings[$teacher_key]['total_ratings'] += $row['total_responses'];
    $teacher_ratings[$teacher_key]['sum_ratings'] += ($row['avg_rating'] * $row['total_responses']);
    
    // Teacher-Course performance (aggregate by unique teacher-course pair, regardless of category)
    if (!isset($teacher_course_ratings[$teacher_course_key])) {
        $teacher_course_ratings[$teacher_course_key] = [
            'teacher_name' => $row['teacher_name'],
            'course_name' => $row['course_name'],
            'course_code' => $row['course_code'],
            'total_ratings' => 0,
            'sum_ratings' => 0
        ];
    }
    $teacher_course_ratings[$teacher_course_key]['total_ratings'] += $row['total_responses'];
    $teacher_course_ratings[$teacher_course_key]['sum_ratings'] += ($row['avg_rating'] * $row['total_responses']);
    
    // Category performance
    if (!isset($category_ratings[$category])) {
        $category_ratings[$category] = [
            'total_ratings' => 0,
            'sum_ratings' => 0
        ];
    }
    $category_ratings[$category]['total_ratings'] += $row['total_responses'];
    $category_ratings[$category]['sum_ratings'] += ($row['avg_rating'] * $row['total_responses']);
}

// After aggregation, ensure only one entry per teacher-course pair
// (No further action needed as aggregation above already combines all rows for the same key)

// Calculate averages
foreach ($course_ratings as &$course) {
    $course['avg_rating'] = $course['sum_ratings'] / $course['total_ratings'];
    $course['performance'] = ($course['avg_rating'] / 4) * 100;
}

foreach ($teacher_ratings as &$teacher) {
    $teacher['avg_rating'] = $teacher['sum_ratings'] / $teacher['total_ratings'];
    $teacher['performance'] = ($teacher['avg_rating'] / 4) * 100;
}

foreach ($teacher_course_ratings as &$tc) {
    $tc['avg_rating'] = $tc['sum_ratings'] / $tc['total_ratings'];
    $tc['performance'] = ($tc['avg_rating'] / 4) * 100;
}

foreach ($category_ratings as &$category) {
    $category['avg_rating'] = $category['sum_ratings'] / $category['total_ratings'];
    $category['performance'] = ($category['avg_rating'] / 4) * 100;
}

// Separate query for Teacher-Course Performance (no category grouping)
$teacherCourseQuery = "
    SELECT 
        co.id as course_id,
        co.course_name,
        co.course_code,
        t.id as teacher_id,
        t.name as teacher_name,
        AVG(fr.rating) as avg_rating,
        COUNT(DISTINCT fs.id) as total_responses
    FROM feedback_submissions fs
    JOIN classes c ON fs.class_id = c.id
    JOIN feedback_ratings fr ON fs.id = fr.submission_id
    JOIN courses co ON fr.course_id = co.id
    JOIN course_teachers ct ON co.id = ct.course_id
    JOIN teachers t ON ct.teacher_id = t.id
    WHERE 1=1";
// Add same filters as above
if ($department) {
    $teacherCourseQuery .= " AND c.department = '" . $conn->real_escape_string($department) . "'";
}
if ($semester) {
    $teacherCourseQuery .= " AND c.semester = '" . $conn->real_escape_string($semester) . "'";
}
if ($academic_year) {
    $teacherCourseQuery .= " AND fs.academic_year = '" . $conn->real_escape_string($academic_year) . "'";
}
if ($from_date) {
    $teacherCourseQuery .= " AND DATE(fs.submission_date) >= '" . $conn->real_escape_string($from_date) . "'";
}
if ($to_date) {
    $teacherCourseQuery .= " AND DATE(fs.submission_date) <= '" . $conn->real_escape_string($to_date) . "'";
}
if ($course_id) {
    $teacherCourseQuery .= " AND co.id = " . intval($course_id);
}
if ($teacher_id) {
    $teacherCourseQuery .= " AND t.id = " . intval($teacher_id);
}
$teacherCourseQuery .= " GROUP BY co.id, t.id ORDER BY co.course_name, t.name";

$teacherCourseResult = $conn->query($teacherCourseQuery);
$teacher_course_ratings = [];
while ($row = $teacherCourseResult->fetch_assoc()) {
    $teacher_course_ratings[] = [
        'teacher_name' => $row['teacher_name'],
        'course_name' => $row['course_name'],
        'course_code' => $row['course_code'],
        'avg_rating' => $row['avg_rating'],
        'performance' => ($row['avg_rating'] / 4) * 100,
        'total_ratings' => $row['total_responses']
    ];
}

$page_title = "Feedback Reports & Analytics";
include 'header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Include html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="container-fluid" id="report-content">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filter Reports
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
                    <select name="course_id" class="form-select">
                        <option value="">All Courses</option>
                        <?php while ($course = $course_result->fetch_assoc()): ?>
                        <option value="<?php echo $course['id']; ?>"
                                <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?> (<?php echo htmlspecialchars($course['course_code']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Teacher</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">All Teachers</option>
                        <?php while ($teacher = $teacher_result->fetch_assoc()): ?>
                        <option value="<?php echo $teacher['id']; ?>"
                                <?php echo $teacher_id == $teacher['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($teacher['name']); ?>
                        </option>
                        <?php endwhile; ?>
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
                    <a href="reports.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-1"></i>Reset
                    </a>
                    <button type="button" class="btn btn-success" onclick="generatePDF()">
                        <i class="fas fa-file-pdf me-1"></i>Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="row">
        <!-- Course Performance -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Course Performance</h5>
                </div>
                <div class="card-body">
                    <canvas id="courseChart" height="400"></canvas>
                    <div class="mt-2">
                        <ul class="list-unstyled small">
                            <?php
                            $i = 1;
                            foreach ($course_ratings as $course):
                            ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teacher Performance -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Teacher Performance</h5>
                </div>
                <div class="card-body">
                    <canvas id="teacherChart" height="400"></canvas>
                </div>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Category Performance</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher-Course Performance -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Teacher-Course Performance</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Course</th>
                            <th>Code</th>
                            <th>Average Rating</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teacher_course_ratings as $tc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tc['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($tc['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($tc['course_code']); ?></td>
                            <td><?php echo number_format($tc['avg_rating'], 2); ?>/4.00</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar <?php echo getProgressBarClass($tc['performance']); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $tc['performance']; ?>%">
                                        <?php echo number_format($tc['performance'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
          
        </div>
    </div>

    
</div>

<script>
// Helper function to get random colors
function getRandomColors(count) {
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push('#' + Math.floor(Math.random()*16777215).toString(16));
    }
    return colors;
}

// Course Performance Chart
const courseCtx = document.getElementById('courseChart').getContext('2d');
new Chart(courseCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($course_ratings, 'name')); ?>,
        datasets: [{
            label: 'Course Performance (%)',
            data: <?php echo json_encode(array_column($course_ratings, 'performance')); ?>,
            backgroundColor: getRandomColors(<?php echo count($course_ratings); ?>),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});

// Teacher Performance Chart
const teacherCtx = document.getElementById('teacherChart').getContext('2d');
new Chart(teacherCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($teacher_ratings, 'name')); ?>,
        datasets: [{
            label: 'Teacher Performance (%)',
            data: <?php echo json_encode(array_column($teacher_ratings, 'performance')); ?>,
            backgroundColor: getRandomColors(<?php echo count($teacher_ratings); ?>),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});

// Category Performance Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_keys($category_ratings)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($category_ratings, 'performance')); ?>,
            backgroundColor: getRandomColors(<?php echo count($category_ratings); ?>)
        }]
    },
    options: {
        responsive: true
    }
});

// Teacher-Course Performance Chart
const teacherCourseCtx = document.getElementById('teacherCourseChart').getContext('2d');
new Chart(teacherCourseCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($tc) { 
            return $tc['teacher_name'] . ' - ' . $tc['course_code']; 
        }, $teacher_course_ratings)); ?>,
        datasets: [{
            label: 'Teacher-Course Performance (%)',
            data: <?php echo json_encode(array_column($teacher_course_ratings, 'performance')); ?>,
            backgroundColor: getRandomColors(<?php echo count($teacher_course_ratings); ?>),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});

// PDF Generation
function generatePDF() {
    const element = document.getElementById('report-content');
    const opt = {
        margin: 0,
        filename: 'feedback_report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
    };

    // Generate PDF
    html2pdf().set(opt).from(element).save();

}
</script>

<?php
// Helper function to determine progress bar class based on performance
function getProgressBarClass($performance) {
    if ($performance >= 90) return 'bg-success';
    if ($performance >= 70) return 'bg-info';
    if ($performance >= 50) return 'bg-warning';
    return 'bg-danger';
}
?>

<style>
.progress {
    height: 25px;
}
.progress-bar {
    line-height: 25px;
    font-size: 14px;
}
</style>

<?php include 'footer.php'; ?>
<?php include 'footer.php'; ?>