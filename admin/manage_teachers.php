<?php
session_start();
require_once 'config.php';
isAdminLoggedIn();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $department = $conn->real_escape_string($_POST['department']);
                $designation = $conn->real_escape_string($_POST['designation']);
                
                $sql = "INSERT INTO teachers (name, email, department, designation) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $name, $email, $department, $designation);
                $stmt->execute();
                $_SESSION['success'] = "Teacher added successfully!";
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $department = $conn->real_escape_string($_POST['department']);
                $designation = $conn->real_escape_string($_POST['designation']);
                
                $sql = "UPDATE teachers SET name = ?, email = ?, department = ?, designation = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $name, $email, $department, $designation, $id);
                $stmt->execute();
                $_SESSION['success'] = "Teacher updated successfully!";
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $sql = "DELETE FROM teachers WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $_SESSION['success'] = "Teacher deleted successfully!";
                break;
        }
        
        header("Location: manage_teachers.php");
        exit();
    }
}

$page_title = "Manage Teachers";
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

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chalkboard-teacher me-1"></i>
                Teachers List
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teacherModal">
                <i class="fas fa-plus me-1"></i> Add New Teacher
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="teachersTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM teachers ORDER BY name";
                        $result = $conn->query($sql);
                        while ($teacher = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['designation']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-primary edit-teacher" 
                                            data-id="<?php echo $teacher['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($teacher['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($teacher['email']); ?>"
                                            data-department="<?php echo htmlspecialchars($teacher['department']); ?>"
                                            data-designation="<?php echo htmlspecialchars($teacher['designation']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-teacher" 
                                            data-id="<?php echo $teacher['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($teacher['name']); ?>">
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
</div>

<!-- Add/Edit Teacher Modal -->
<div class="modal fade" id="teacherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="teacherForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" name="department" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Designation</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                            <input type="text" class="form-control" name="designation" required>
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
                <h5 class="modal-title">Delete Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this teacher?</p>
                <p class="text-danger fw-bold" id="deleteTeacherName"></p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteTeacherId">
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
    $('#teachersTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']], // Sort by name by default
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search teachers..."
        }
    });
    
    // Handle Edit Button Click
    $('.edit-teacher').click(function() {
        const modal = $('#teacherModal');
        modal.find('.modal-title').text('Edit Teacher');
        modal.find('form')[0].reset();
        modal.find('[name=action]').val('edit');
        modal.find('[name=id]').val($(this).data('id'));
        modal.find('[name=name]').val($(this).data('name'));
        modal.find('[name=email]').val($(this).data('email'));
        modal.find('[name=department]').val($(this).data('department'));
        modal.find('[name=designation]').val($(this).data('designation'));
        modal.modal('show');
    });
    
    // Handle Add Button Click
    $('[data-bs-target="#teacherModal"]').click(function() {
        const modal = $('#teacherModal');
        modal.find('.modal-title').text('Add New Teacher');
        modal.find('form')[0].reset();
        modal.find('[name=action]').val('add');
        modal.find('[name=id]').val('');
    });
    
    // Handle Delete Button Click
    $('.delete-teacher').click(function() {
        const modal = $('#deleteModal');
        modal.find('#deleteTeacherId').val($(this).data('id'));
        modal.find('#deleteTeacherName').text($(this).data('name'));
        modal.modal('show');
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>

<?php include 'footer.php'; ?> 