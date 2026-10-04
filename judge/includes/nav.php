<?php


// Handle logout action
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php"); // redirect to login/home page
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Navbar with Logout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <nav class="navbar navbar-expand navbar-light navbar-bg bg-light p-2">


        <div class="sidebar-user mt-2">
            <div class="d-flex justify-content-center align-items-center">
                <div class="flex-shrink-0">
                    <img src="./assets/img/logo-mini.svg" class="avatar img-fluid rounded me-1" alt="logo-mini.svg">
                </div>
                <div class="flex-grow-1 ps-2">
                    <h4 class="sidebar-user-title text-primary" href="#">
                        DataSync
                    </h4>   


                </div>
            </div>
        </div>

        <div class="navbar-collapse collapse show">
            <ul class="navbar-nav ms-auto navbar-align">
                 <li class="nav-item d-flex align-items-center mt-2">
                <div class="nav-icon">
                    <div class="position-relative">
                        <h6><?= $user_display['first_name']; ?></h6>
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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>feather.replace();</script>
</body>
</html>
