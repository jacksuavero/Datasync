<nav class="navbar navbar-expand navbar-light navbar-bg">
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align">
  
            <li class="nav-item d-flex align-items-center mt-2">
                <div class="nav-icon">
                    <div class="position-relative">
                        <h5><?= $user_display['first_name']; ?></h5>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
    <a class="nav-icon pe-md-0 dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
        <?php 
            $firstLetter = strtoupper(substr($user_display['first_name'], 0, 1)); 
        ?>
        <div class="avatar-circle bg-primary text-white fw-bold d-flex justify-content-center align-items-center rounded-circle" 
             style="width:40px; height:40px; font-size:18px;">
            <?= $firstLetter ?>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        <div class="dropdown-divider mt-4"></div>
        <a class="dropdown-item" href="../index.php">Log out</a>
    </div>
</li>
        </ul>
    </div>
</nav>