<?php
session_start();
require_once 'config.php';
isAdminLoggedIn();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $question_text = $conn->real_escape_string($_POST['question_text']);
                $category = $conn->real_escape_string($_POST['category']);
                $question_type = $conn->real_escape_string($_POST['question_type']);
                $is_required = isset($_POST['is_required']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "INSERT INTO questions (question_text, category, question_type, is_required, is_active) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $question_text, $category, $question_type, $is_required, $is_active);
                $stmt->execute();
                $_SESSION['success'] = "Question added successfully!";
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $question_text = $conn->real_escape_string($_POST['question_text']);
                $category = $conn->real_escape_string($_POST['category']);
                $question_type = $conn->real_escape_string($_POST['question_type']);
                $is_required = isset($_POST['is_required']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE questions SET question_text = ?, category = ?, question_type = ?, 
                        is_required = ?, is_active = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssiii", $question_text, $category, $question_type, 
                                $is_required, $is_active, $id);
                $stmt->execute();
                $_SESSION['success'] = "Question updated successfully!";
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                // First, delete related feedback_text_responses
                $stmt = $conn->prepare("DELETE FROM feedback_text_responses WHERE question_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                // Now, delete the question
                $sql = "DELETE FROM questions WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $_SESSION['success'] = "Question deleted successfully!";
                break;
        }
        
        header("Location: manage_questions.php");
        exit();
    }
}

$page_title = "Manage Questions";
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
                <i class="fas fa-question-circle me-1"></i>
                Questions List
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal">
                <i class="fas fa-plus me-1"></i> Add New Question
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="questionsTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM questions ORDER BY category, id";
                        $result = $conn->query($sql);
                        while ($question = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo ucfirst(htmlspecialchars($question['category'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo ucfirst(htmlspecialchars($question['question_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $question['is_required'] ? 'warning' : 'light'; ?>">
                                    <?php echo $question['is_required'] ? 'Required' : 'Optional'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $question['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $question['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-primary edit-question" 
                                            data-id="<?php echo $question['id']; ?>"
                                            data-text="<?php echo htmlspecialchars($question['question_text']); ?>"
                                            data-category="<?php echo htmlspecialchars($question['category']); ?>"
                                            data-type="<?php echo htmlspecialchars($question['question_type']); ?>"
                                            data-required="<?php echo $question['is_required']; ?>"
                                            data-active="<?php echo $question['is_active']; ?>">
                                        <i class="fas fa-edit"></i>
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

<!-- Add/Edit Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="questionForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Question Text</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-question"></i></span>
                            <textarea class="form-control" name="question_text" rows="3" required></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <option value="theory">Theory</option>
                                <option value="practical">Practical</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Question Type</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                            <select class="form-select" name="question_type" required>
                                <option value="">Select Type</option>
                                <option value="rating">Rating (1-5)</option>
                                <option value="text">Text Response</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_required" value="1" checked>
                            <label class="form-check-label">Required Question</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                            <label class="form-check-label">Active Question</label>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable with enhanced features
    $('#questionsTable').DataTable({
        pageLength: 10,
        order: [[1, 'asc']], // Sort by category by default
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search questions..."
        }
    });
    
    // Handle Edit Button Click
    $('.edit-question').click(function() {
        const modal = $('#questionModal');
        modal.find('.modal-title').text('Edit Question');
        modal.find('form')[0].reset();
        modal.find('[name=action]').val('edit');
        modal.find('[name=id]').val($(this).data('id'));
        modal.find('[name=question_text]').val($(this).data('text'));
        modal.find('[name=category]').val($(this).data('category'));
        modal.find('[name=question_type]').val($(this).data('type'));
        modal.find('[name=is_required]').prop('checked', $(this).data('required') == 1);
        modal.find('[name=is_active]').prop('checked', $(this).data('active') == 1);
        modal.modal('show');
    });
    
    // Handle Add Button Click
    $('[data-bs-target="#questionModal"]').click(function() {
        const modal = $('#questionModal');
        modal.find('.modal-title').text('Add New Question');
        modal.find('form')[0].reset();
        modal.find('[name=action]').val('add');
        modal.find('[name=id]').val('');
        modal.find('[name=is_required]').prop('checked', true);
        modal.find('[name=is_active]').prop('checked', true);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>

<?php include 'footer.php'; ?> 