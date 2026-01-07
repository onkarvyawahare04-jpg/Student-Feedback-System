<?php
session_start();

// If no success message in session, redirect to index
if (!isset($_SESSION['feedback_message'])) {
    header("Location: logoutphp");
    exit();
}

// Get the success message and clear it from session
$message = $_SESSION['feedback_message'];
unset($_SESSION['feedback_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Submitted - MGM College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .success-container {
            max-width: 600px;
            width: 100%;
            padding: 2rem;
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: #198754;
            margin-bottom: 1rem;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <img src="images/logo.png" alt="MGM Logo" class="logo">
        <div class="card">
            <div class="card-body">
                <i class="fas fa-check-circle success-icon"></i>
                <h2 class="mb-4">Thank You!</h2>
                <p class="lead mb-4"><?php echo htmlspecialchars($message); ?></p>
                <div class="d-grid gap-2">
                    <a href="logout.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                    <a href="logout.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Prevent going back to the form
    history.pushState(null, null, document.URL);
    window.addEventListener('popstate', function () {
        history.pushState(null, null, document.URL);
    });
    </script>
</body>
</html> 