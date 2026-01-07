<?php
session_start();
require_once 'config.php';
requireAdminLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $course_code = $conn->real_escape_string($_POST['course_code']);
                $course_name = $conn->real_escape_string($_POST['course_name']);
                $course_type = $conn->real_escape_string($_POST['course_type']);
                $semester = $conn->real_escape_string($_POST['semester']);
                $department = $conn->real_escape_string($_POST['department']);
                $teacher_id = (int)$_POST['teacher_id'];
                
                $conn->begin_transaction();
                try {
                    // Insert course
                    $sql = "INSERT INTO courses (course_code, course_name, course_type, semester, department) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssss", $course_code, $course_name, $course_type, 
                                    $semester, $department);
                    $stmt->execute();
                    $course_id = $conn->insert_id;

                    // Insert course-teacher relationship
                    $sql = "INSERT INTO course_teachers (course_id, teacher_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $course_id, $teacher_id);
                    $stmt->execute();

                    $conn->commit();
                    $_SESSION['success'] = "Course added successfully!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "Error adding course: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $course_code = $conn->real_escape_string($_POST['course_code']);
                $course_name = $conn->real_escape_string($_POST['course_name']);
                $course_type = $conn->real_escape_string($_POST['course_type']);
                $semester = $conn->real_escape_string($_POST['semester']);
                $department = $conn->real_escape_string($_POST['department']);
                $teacher_id = (int)$_POST['teacher_id'];
                
                $conn->begin_transaction();
                try {
                    // Update course
                    $sql = "UPDATE courses SET course_code = ?, course_name = ?, course_type = ?, 
                            semester = ?, department = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssi", $course_code, $course_name, $course_type, 
                                    $semester, $department, $id);
                    $stmt->execute();

                    // Update course-teacher relationship
                    $sql = "INSERT INTO course_teachers (course_id, teacher_id) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $id, $teacher_id);
                    $stmt->execute();

                    $conn->commit();
                    $_SESSION['success'] = "Course updated successfully!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = "Error updating course: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $sql = "DELETE FROM courses WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $_SESSION['success'] = "Course deleted successfully!";
                break;
        }
        
        header("Location: manage_courses.php");
        exit();
    }
}

// Get list of departments
$departments = [];
$sql = "SELECT DISTINCT department FROM teachers ORDER BY department";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    if (!empty($row['department'])) {
        $departments[] = $row['department'];
    }
}

// Get list of teachers
$teachers = [];
$sql = "SELECT id, name, department FROM teachers ORDER BY name";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

$page_title = "Manage Courses";
include 'header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <i class="fas fa-book me-1"></i>
        Manage Courses
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
        <i class="fas fa-plus me-1"></i> Add New Course
    </button>
</div>

<!-- Courses Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="coursesTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Type</th>
                        <th>Semester</th>
                        <th>Department</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT c.*, t.name as teacher_name, t.id as teacher_id 
                            FROM courses c 
                            LEFT JOIN course_teachers ct ON c.id = ct.course_id 
                            LEFT JOIN teachers t ON ct.teacher_id = t.id 
                            ORDER BY c.course_code";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $row['course_type'] == 'theory' ? 'primary' : 'success'; ?>">
                                <?php echo ucfirst(htmlspecialchars($row['course_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['semester']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><?php echo htmlspecialchars($row['teacher_name'] ?? 'Not Assigned'); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary edit-course" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-code="<?php echo htmlspecialchars($row['course_code']); ?>"
                                        data-name="<?php echo htmlspecialchars($row['course_name']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['course_type']); ?>"
                                        data-semester="<?php echo htmlspecialchars($row['semester']); ?>"
                                        data-department="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-teacher="<?php echo htmlspecialchars($row['teacher_id'] ?? ''); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-course" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['course_name']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_courses.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Course Code</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                            <input type="text" class="form-control" name="course_code" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-book"></i></span>
                            <input type="text" class="form-control" name="course_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Type</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                            <select class="form-select" name="course_type" required>
                                <option value="">Select Type</option>
                                <option value="theory">Theory</option>
                                <option value="practical">Practical</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                            <input type="text" class="form-control" name="semester" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <select class="form-select" name="department" id="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-chalkboard-teacher"></i></span>
                            <select class="form-select" name="teacher_id" id="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" data-department="<?php echo htmlspecialchars($teacher['department']); ?>">
                                        <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['department']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_courses.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editCourseId">
                    <div class="mb-3">
                        <label class="form-label">Course Code</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                            <input type="text" class="form-control" name="course_code" id="editCourseCode" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-book"></i></span>
                            <input type="text" class="form-control" name="course_name" id="editCourseName" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Type</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                            <select class="form-select" name="course_type" id="editCourseType" required>
                                <option value="theory">Theory</option>
                                <option value="practical">Practical</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                            <input type="text" class="form-control" name="semester" id="editCourseSemester" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <select class="form-select" name="department" id="editCourseDepartment" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-chalkboard-teacher"></i></span>
                            <select class="form-select" name="teacher_id" id="editCourseTeacher" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" data-department="<?php echo htmlspecialchars($teacher['department']); ?>">
                                        <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['department']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_courses.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCourseId">
                    <p>Are you sure you want to delete the course "<span id="deleteCourseNameSpan"></span>"?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#coursesTable').DataTable({
        order: [[0, 'asc']]
    });

    // Filter teachers based on selected department
    function filterTeachers(departmentSelect, teacherSelect) {
        const selectedDepartment = departmentSelect.value;
        const teacherOptions = teacherSelect.querySelectorAll('option');
        
        teacherOptions.forEach(option => {
            if (option.value === '') return; // Skip the placeholder option
            const teacherDepartment = option.getAttribute('data-department');
            option.style.display = !selectedDepartment || teacherDepartment === selectedDepartment ? '' : 'none';
        });

        // Reset teacher selection if current selection is not in the filtered department
        const selectedTeacher = teacherSelect.querySelector('option:checked');
        if (selectedTeacher && selectedTeacher.style.display === 'none') {
            teacherSelect.value = '';
        }
    }

    // Set up department change handlers for both add and edit forms
    const addDepartmentSelect = document.getElementById('department');
    const addTeacherSelect = document.getElementById('teacher_id');
    const editDepartmentSelect = document.getElementById('editCourseDepartment');
    const editTeacherSelect = document.getElementById('editCourseTeacher');

    if (addDepartmentSelect && addTeacherSelect) {
        addDepartmentSelect.addEventListener('change', () => filterTeachers(addDepartmentSelect, addTeacherSelect));
    }

    if (editDepartmentSelect && editTeacherSelect) {
        editDepartmentSelect.addEventListener('change', () => filterTeachers(editDepartmentSelect, editTeacherSelect));
    }

    // Handle edit course button clicks
    document.querySelectorAll('.edit-course').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('editCourseModal');
            document.getElementById('editCourseId').value = this.dataset.id;
            document.getElementById('editCourseCode').value = this.dataset.code;
            document.getElementById('editCourseName').value = this.dataset.name;
            document.getElementById('editCourseType').value = this.dataset.type;
            document.getElementById('editCourseSemester').value = this.dataset.semester;
            document.getElementById('editCourseDepartment').value = this.dataset.department;
            document.getElementById('editCourseTeacher').value = this.dataset.teacher;
            
            filterTeachers(editDepartmentSelect, editTeacherSelect);
            
            new bootstrap.Modal(modal).show();
        });
    });

    // Handle delete course button clicks
    document.querySelectorAll('.delete-course').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('deleteCourseModal');
            document.getElementById('deleteCourseId').value = this.dataset.id;
            document.getElementById('deleteCourseNameSpan').textContent = this.dataset.name;
            new bootstrap.Modal(modal).show();
        });
    });
});
</script>

<?php include 'footer.php'; ?> 