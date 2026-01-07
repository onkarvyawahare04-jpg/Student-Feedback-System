<!-- Sidebar -->
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Core</div>
                <a class="nav-link" href="dashboard.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>

                <div class="sb-sidenav-menu-heading">Management</div>
                <a class="nav-link" href="manage_students.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                    Students
                </a>
                <a class="nav-link" href="manage_teachers.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    Teachers
                </a>
                <a class="nav-link" href="manage_courses.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-book"></i></div>
                    Courses
                </a>
                <a class="nav-link" href="manage_classes.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-school"></i></div>
                    Classes
                </a>
                <a class="nav-link" href="manage_questions.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-question-circle"></i></div>
                    Questions
                </a>

                <div class="sb-sidenav-menu-heading">Feedback</div>
                <a class="nav-link" href="view_feedback.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-comments"></i></div>
                    View Feedback
                </a>
                <a class="nav-link" href="appreciation.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-award"></i></div>
                    Appreciation
                </a>
                <a class="nav-link" href="reports.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                    Reports
                </a>

                <div class="sb-sidenav-menu-heading">Settings</div>
                <a class="nav-link" href="settings.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-cog"></i></div>
                    Settings
                </a>
                <a class="nav-link" href="logout.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>
                    Logout
                </a>
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </nav>
</div> 