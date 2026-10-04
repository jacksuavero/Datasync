<?php
require_once './config/dbcon.php';
require_once './config/auth.php';
include('./includes/log_helper.php');

if (isset($_POST['btn_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (login($email, $password, false)) {
        $role = $_SESSION['role'] ?? null;

        $_SESSION['login_success'] = true;

        if ($role === 'admin') {
            $_SESSION['redirect_to'] = './admin/index.php';
        } elseif ($role === 'judge') {
            $_SESSION['redirect_to'] = './judge/index.php';
        } elseif ($role === 'superadmin') {
            $_SESSION['redirect_to'] = './superadmin/index.php';
        }
    } else {
        $_SESSION['login_error'] = "Invalid email or password.";
    }

    // Always go back to the main login page
    header("Location: index.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
