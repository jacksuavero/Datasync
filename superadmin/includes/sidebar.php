
<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">


        <div class="sidebar-user mt-2">
            <div class="d-flex justify-content-center align-items-center">
                <div class="flex-shrink-0">
                    <img src="./assets/img/logo-mini.svg" class="avatar img-fluid rounded me-1" alt="Rnhs.png">
                </div>
                <div class="flex-grow-1 ps-2">
                    <h4 class="sidebar-user-title" href="#">
                        DataSync
                    </h4>   


                </div>
            </div>
        </div>



        
            <!-- Navigation -->
        <ul class="sidebar-nav">
            <li class="sidebar-header">Home</li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'active':''; ?>">
                <a href="index.php" class="sidebar-link">
                    <i data-feather="sliders"></i>
                    <span class="sidebar-text">Dashboards</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='account_request.php'?'active':''; ?>">
                <a href="account_request.php" class="sidebar-link">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span class="sidebar-text">Account Request</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='superadmin_events.php'?'active':''; ?>">
                <a href="superadmin_events.php" class="sidebar-link">
                    <i data-feather="bar-chart-2"></i>
                    <span class="sidebar-text">Event Reports</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='manage_users.php'?'active':''; ?>">
                <a href="manage_users.php" class="sidebar-link">
                    <i data-feather="users"></i>
                    <span class="sidebar-text">Manage Users</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='logs.php'?'active':''; ?>">
                <a href="logs.php" class="sidebar-link">
                    <i data-feather="file-text"></i>
                    <span class="sidebar-text">Logs</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a href="../index.php" class="sidebar-link">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span class="sidebar-text text-danger">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>