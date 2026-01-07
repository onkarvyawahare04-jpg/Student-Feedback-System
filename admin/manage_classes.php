<?php
session_start();
require_once 'config.php';
isAdminLoggedIn();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $class_name = $conn->real_escape_string($_POST['class_name']);
                $department = $conn->real_escape_string($_POST['department']);
                $semester = $conn->real_escape_string($_POST['semester']);
                $academic_year = $conn->real_escape_string($_POST['academic_year']);
                
                $sql = "INSERT INTO classes (class_name, department, semester, academic_year) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $class_name, $department, $semester, $academic_year);
                $stmt->execute();
                $_SESSION['success'] = "Class added successfully!";
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $class_name = $conn->real_escape_string($_POST['class_name']);
                $department = $conn->real_escape_string($_POST['department']);
                $semester = $conn->real_escape_string($_POST['semester']);
                $academic_year = $conn->real_escape_string($_POST['academic_year']);
                
                $sql = "UPDATE classes SET class_name = ?, department = ?, semester = ?, 
                        academic_year = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $class_name, $department, $semester, 
                                $academic_year, $id);
                $stmt->execute();
                $_SESSION['success'] = "Class updated successfully!";
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $sql = "DELETE FROM classes WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $_SESSION['success'] = "Class deleted successfully!";
                break;
        }
        
        header("Location: manage_classes.php");
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

$page_title = "Manage Classes";
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

<div class="d-flex justify-content-between align-items-center mb-4"><div>
                <i class="fas fa-chalkboard-teacher me-1"></i>
                New Class
            </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
        <i class="fas fa-plus me-1"></i> Add New Class
    </button>
</div>

<!-- Classes Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="classesTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM classes ORDER BY class_name";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><?php echo htmlspecialchars($row['semester']); ?></td>
                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary edit-class" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['class_name']); ?>"
                                        data-department="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-semester="<?php echo htmlspecialchars($row['semester']); ?>"
                                        data-academic-year="<?php echo htmlspecialchars($row['academic_year']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-class" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['class_name']); ?>">
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

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_classes.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-school"></i></span>
                            <input type="text" class="form-control" name="class_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <select class="form-select" name="department" required>
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
                        <label class="form-label">Semester</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                            <input type="text" class="form-control" name="semester" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Year</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="text" class="form-control" name="academic_year" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Add Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_classes.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editClassId">
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-school"></i></span>
                            <input type="text" class="form-control" name="class_name" id="editClassName" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <select class="form-select" name="department" id="editClassDepartment" required>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                            <input type="text" class="form-control" name="semester" id="editClassSemester" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Year</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="text" class="form-control" name="academic_year" id="editClassAcademicYear" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Class Modal -->
<div class="modal fade" id="deleteClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this class?</p>
                <p class="text-danger fw-bold" id="deleteClassName"></p>
            </div>
            <div class="modal-footer">
                <form action="manage_classes.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteClassId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    $('.edit-class').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const department = $(this).data('department');
        const semester = $(this).data('semester');
        const academicYear = $(this).data('academic-year');
        
        $('#editClassId').val(id);
        $('#editClassName').val(name);
        $('#editClassDepartment').val(department);
        $('#editClassSemester').val(semester);
        $('#editClassAcademicYear').val(academicYear);
        
        $('#editClassModal').modal('show');
    });
    
    // Handle delete button clicks
    $('.delete-class').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        $('#deleteClassId').val(id);
        $('#deleteClassName').text(name);
        
        $('#deleteClassModal').modal('show');
    });
});
</script>

<?php include 'footer.php'; ?> 