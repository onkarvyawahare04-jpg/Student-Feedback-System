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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    $error = '';

    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        // Validate inputs
        if (empty($full_name) || empty($email)) {
            $error = "Name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if email exists for other admin
            $check_stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $email, $_SESSION['admin_id']);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "Email already exists.";
            } else {
                // Update profile
                $update_stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $full_name, $email, $_SESSION['admin_id']);
                if ($update_stmt->execute()) {
                    $success = true;
                    $admin['full_name'] = $full_name;
                    $admin['email'] = $email;
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif (!password_verify($current_password, $admin['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['admin_id']);
            if ($update_stmt->execute()) {
                $success = true;
            } else {
                $error = "Failed to update password.";
            }
        }
    }
}

// Set page title
$page_title = "Settings";

// Include header
include 'header.php';
?>

<div class="container-fluid">
    <?php if (isset($success) && $success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Settings updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error) && !empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-circle me-2"></i>Profile Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="profileForm">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="full_name" 
                                       value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Password Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-key me-2"></i>Change Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="passwordForm">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" name="new_password" 
                                       minlength="6" required>
                            </div>
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" name="confirm_password" 
                                       minlength="6" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Password form validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        var newPass = this.querySelector('[name="new_password"]').value;
        var confirmPass = this.querySelector('[name="confirm_password"]').value;
        
        if (newPass !== confirmPass) {
            e.preventDefault();
            alert('New passwords do not match!');
        }
    });
});
</script>

<?php include 'footer.php'; ?> 