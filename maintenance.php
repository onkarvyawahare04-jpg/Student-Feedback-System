<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'admin/config.php';

// Check if feedback system is active
$sql = "SELECT feedback_active, maintenance_mode FROM settings WHERE id = 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();

// If system is active and not in maintenance, redirect to index
if ($settings['feedback_active'] && !$settings['maintenance_mode']) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - MGM College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 90%;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 2rem;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
        .message {
            color: #6c757d;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <img src="images/logo.png" alt="MGM Logo" class="logo">
        <h1>
            <?php if (!$settings['feedback_active']): ?>
                Feedback System Closed
            <?php else: ?>
                System Maintenance
            <?php endif; ?>
        </h1>
        <div class="message">
            <?php if (!$settings['feedback_active']): ?>
                <p>The feedback system is currently closed. Please check back later during the feedback collection period.</p>
            <?php else: ?>
                <p>We are currently performing system maintenance to improve your experience.</p>
                <p>Please check back in a few minutes.</p>
            <?php endif; ?>
        </div>
        <a href="student_login.php" class="btn btn-primary">Return to Login</a>
    </div>
</body>
</html> 