<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">

        <div class="sidebar-user mt-2">
            <div class="d-flex justify-content-center align-items-center">
                <div class="flex-shrink-0">
                    <img src="./assets/img/logo-mini.svg" class="avatar img-fluid rounded me-1" alt="logo-mini.svg">
                </div>
                <div class="flex-grow-1 ps-2">
                    <h4 class="sidebar-user-title">DataSync</h4>
                </div>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-header">Home</li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'active':''; ?>">
                <a href="index.php" class="sidebar-link">
                    <i class="align-middle" data-feather="sliders"></i>
                    <span class="align-middle">Dashboard</span>
                </a>
            </li>

            <li class="sidebar-header">Event Management</li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='view_events.php'?'active':''; ?>">
                <a class="sidebar-link" href="view_events.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="align-middle">View Event</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='add_event.php'?'active':''; ?>">
                <a class="sidebar-link" href="add_event.php">
                    <i class="fa-solid fa-plus"></i>
                    <span class="align-middle">Add Event</span>
                </a>
            </li>

<li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='view_contestants.php'?'active':''; ?>">
                <a class="sidebar-link" href="view_contestants.php">
                    <i class="fa-solid fa-user-plus"></i>
                    <span class="align-middle">Manage Contestants</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='add_contestant.php'?'active':''; ?>">
                <a class="sidebar-link" href="add_contestant.php">
                    <i class="fa-solid fa-user-plus"></i>
                    <span class="align-middle">Add Contestants</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='view_criteria.php'?'active':''; ?>">
                <a class="sidebar-link" href="view_criteria.php">
                    <i class="fa-solid fa-folder-plus"></i>
                    <span class="align-middle">Manage Criteria</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='add_criteria.php'?'active':''; ?>">
                <a class="sidebar-link" href="add_criteria.php">
                    <i class="fa-solid fa-folder-plus"></i>
                    <span class="align-middle">Create Criteria</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='assign_judge.php'?'active':''; ?>">
                <a class="sidebar-link" href="assign_judge.php">
                    <i class="fa-solid fa-users"></i>
                    <span class="align-middle">Assign Judge</span>
                </a>
            </li>

            <li class="sidebar-header">Report</li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='view_scores.php'?'active':''; ?>">
                <a class="sidebar-link" href="view_scores.php">
                    <i class="fa-solid fa-eye"></i>
                    <span class="align-middle">View Scores</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='event_scores_report.php'?'active':''; ?>">
                <a class="sidebar-link" href="event_scores_report.php">
                    <i class="fa-solid fa-file-download"></i>
                    <span class="align-middle">Report</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a class="sidebar-link" href="../index.php">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span class="align-middle text-danger">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>