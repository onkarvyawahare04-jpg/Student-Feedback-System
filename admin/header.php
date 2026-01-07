<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>MGM College Feedback System</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
        }

        /* Layout */
        body {
            min-height: 100vh;
            display: flex;
            background-color: #f8f9fa;
            overflow-x: hidden;
            padding: 0;
            margin: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            padding: 1rem;
            transition: all 0.3s ease;
            top: 0;
            left: 0;
        }

        .sidebar-header {
            text-align: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .sidebar-header img {
            max-width: 80px;
            margin-bottom: 0.5rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            color: white;
            background-color: var(--secondary-color);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            color: white;
            background-color: #0d6efd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        /* Main Content Wrapper */
        .content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation */
        .top-nav {
            position: sticky;
            top: 0;
            z-index: 900;
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            flex: 1;
        }

        /* Card Styles */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
            border: none;
            border-radius: 0.5rem;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        /* Button Styles */
        .btn {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-group > .btn {
            margin: 0 0.1rem;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 0.5rem;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        /* Form Styles */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        /* Alert Styles */
        .alert {
            margin-bottom: 1.5rem;
            border: none;
            border-radius: 0.25rem;
        }

        /* Badge Styles */
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }

        /* DataTables Customization */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
            padding: 0.375rem 0.75rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .content-wrapper {
                margin-left: 0;
            }

            .top-nav {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="MGM Logo" class="img-fluid">
            <h5 class="mb-0">Admin Panel</h5>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Manage Students' ? 'active' : ''; ?>" href="manage_students.php">
                    <i class="fas fa-users"></i> Manage Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Manage Teachers' ? 'active' : ''; ?>" href="manage_teachers.php">
                    <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Manage Courses' ? 'active' : ''; ?>" href="manage_courses.php">
                    <i class="fas fa-book"></i> Manage Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Manage Classes' ? 'active' : ''; ?>" href="manage_classes.php">
                    <i class="fas fa-school"></i> Manage Classes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Manage Questions' ? 'active' : ''; ?>" href="manage_questions.php">
                    <i class="fas fa-question-circle"></i> Manage Questions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'View Feedback' ? 'active' : ''; ?>" href="view_feedback.php">
                    <i class="fas fa-comments"></i> View Feedback
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Reports' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li>
            <a class="nav-link" href="appreciation.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-award"></i></div>
                    Appreciation
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title == 'Settings' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
    <div class="content-wrapper">
        <div class="top-nav">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-dark d-md-none me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0"><?php echo $page_title; ?></h4>
            </div>
            <div class="d-flex align-items-center">
                
            </div>
        </div>
        <div class="main-content"> 