<?php
session_start();
require_once 'config.php';
requireAdminLogin();

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $roll_number = $_POST['roll_number'];
            $email = $_POST['email'];
            $full_name = $_POST['full_name'];
            $class_id = $_POST['class_id'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($_POST['action'] === 'add') {
                // Check if email already exists
                $check_stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $_SESSION['error'] = "Email address already exists.";
                    header("Location: manage_students.php");
                    exit();
                }
                
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO students (roll_number, email, password, full_name, class_id, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssii", $roll_number, $email, $password, $full_name, $class_id, $is_active);
            } else {
                $id = $_POST['id'];
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE students SET roll_number=?, email=?, password=?, full_name=?, class_id=?, is_active=? WHERE id=?");
                    $stmt->bind_param("ssssiis", $roll_number, $email, $password, $full_name, $class_id, $is_active, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE students SET roll_number=?, email=?, full_name=?, class_id=?, is_active=? WHERE id=?");
                    $stmt->bind_param("sssiis", $roll_number, $email, $full_name, $class_id, $is_active, $id);
                }
            }
            $stmt->execute();
            $_SESSION['success'] = $_POST['action'] === 'add' ? 'Student added successfully!' : 'Student updated successfully!';
            
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $_SESSION['success'] = 'Student deleted successfully!';
        }
        
        header("Location: manage_students.php");
        exit();
    }
}

// Get all students
$result = $conn->query("SELECT s.*, c.class_name, c.department, c.semester 
                       FROM students s 
                       LEFT JOIN classes c ON s.class_id = c.id 
                       ORDER BY s.email");
$students = $result->fetch_all(MYSQLI_ASSOC);

// Get all classes for the form
$classes = $conn->query("SELECT * FROM classes ORDER BY department, semester");

$page_title = "Manage Students";
include 'header.php';
?>

<div class="container-fluid">
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
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users me-1"></i>
                Students List
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal">
                <i class="fas fa-plus me-1"></i> Add New Student
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="studentsTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Roll Number</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['department']); ?></td>
                            <td><?php echo htmlspecialchars($student['semester']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $student['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-primary edit-student" 
                                            data-id="<?php echo $student['id']; ?>"
                                            data-roll="<?php echo htmlspecialchars($student['roll_number']); ?>"
                                            data-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                            data-class="<?php echo $student['class_id']; ?>"
                                            data-active="<?php echo $student['is_active']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-student" 
                                            data-id="<?php echo $student['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($student['full_name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<!-- Add/Edit Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="studentForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <small class="form-text text-muted">This will be used for login</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Roll Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" name="roll_number" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <small class="form-text text-muted">Leave blank to keep existing password when editing</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select class="form-select" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['department'] . ' (' . $class['semester'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                            <label class="form-check-label">Active Account</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this student?</p>
                <p class="text-danger fw-bold" id="deleteStudentName"></p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteStudentId">
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
    // Initialize DataTable with enhanced features
    $('#studentsTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']], // Sort by email by default
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search students..."
        }
    });
    
    // Handle Edit Button Click
    $('.edit-student').click(function() {
        const modal = $('#studentModal');
        modal.find('.modal-title').text('Edit Student');
        modal.find('form')[0].reset();
        modal.find('[name=action]').val('edit');
        modal.find('[name=id]').val($(this).data('id'));
        modal.find('[name=roll_number]').val($(this).data('roll'));
        modal.find('[name=full_name]').val($(this).data('name'));
        modal.find('[name=email]').val($(this).data('email'));
        modal.find('[name=class_id]').val($(this).data('class'));
        modal.find('[name=is_active]').prop('checked', $(this).data('active') == 1);
        modal.find('[name=password]').removeAttr('required');
        modal.modal('show');
    });
    
    // Handle Add Button Click
    $('[data-bs-target="#studentModal"]').click(function() {
        const modal = $('#studentModal');
        modal.find('.modal-title').text('Add New Student');
        modal.find('form')[0].reset();
        modal.find('[name=action]').val('add');
        modal.find('[name=id]').val('');
        modal.find('[name=password]').attr('required', 'required');
        modal.find('[name=is_active]').prop('checked', true);
    });
    
    // Handle Delete Button Click
    $('.delete-student').click(function() {
        const modal = $('#deleteModal');
        modal.find('#deleteStudentId').val($(this).data('id'));
        modal.find('#deleteStudentName').text($(this).data('name'));
        modal.modal('show');
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>

<?php include 'footer.php'; ?> 