<?php
// --- Secure Session and Database Connection ---
require('../config/dbcon.php');
session_start();

// --- Security: Block if not logged in or role mismatch ---
$required_role = basename(dirname(__DIR__)); // auto-detect folder name: "admin" or "superadmin"

// Check login status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit;
}

// Check correct role for this directory
if ($_SESSION['role'] !== $required_role) {
    session_destroy(); // destroy any hijacked session
    header("Location: ../index.php");
    exit;
}

// Sanitize user_id
$user_id = intval($_SESSION['user_id']);

// Validate user still exists and has correct role
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = ?");
$stmt->bind_param("is", $user_id, $required_role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // user was deleted or role changed — kick out
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$user_display = $result->fetch_assoc();

// Optional: Prevent session fixation (extra layer)
session_regenerate_id(true);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="AdminKit">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="./assets/img/logo-mini.svg">

    <link rel="canonical" href="dashboard-ecommerce.html">
    <link rel="canonical" href="tables-datatables-responsive.html">

    <title>DataSync</title>

    <link href="css2.css?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Choose your prefered color scheme -->
    <!-- <link href="css/light.css" rel="stylesheet"> -->
    <!-- <link href="css/dark.css" rel="stylesheet"> -->

    <!-- BEGIN SETTINGS -->
    <!-- Remove this after purchasing -->
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">

    <!-- fontawesome cdn -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- <script src="js/settings.js"></script> -->

    <style>
        body {
            opacity: 0;
        }
    </style>
    <!-- END SETTINGS -->
    <script async="" src="gtag/js.js?id=UA-120946860-10"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'UA-120946860-10', { 'anonymize_ip': true });
    </script>
</head>
<!--
  HOW TO USE: 
  data-theme: default (default), dark, light, colored
  data-layout: fluid (default), boxed
  data-sidebar-position: left (default), right
  data-sidebar-layout: default (default), compact
-->

<body data-theme="dark" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <form action="create_user.php" method="POST">
    <div class="mb-3">
        <label for="first_name" class="form-label">First Name</label>
        <input type="text" class="form-control" name="first_name" required pattern="[A-Za-z\s]+" title="First name should contain only letters and spaces">
    </div>
    <div class="mb-3">
        <label for="last_name" class="form-label">Last Name</label>
        <input type="text" class="form-control" name="last_name" required pattern="[A-Za-z\s]+" title="Last name should contain only letters and spaces">
    </div>
    <div class="mb-3">
        <label for="middle_name" class="form-label">Middle Name</label>
        <input type="text" class="form-control" name="middle_name" pattern="[A-Za-z\s]+" title="Middle name should contain only letters and spaces">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" name="email" required>
    </div>
    <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select class="form-select" name="role" required>
            <option value="teacher">Teacher</option>
            <option value="parent">Parent</option>
        </select>
    </div>

                        <button type="submit" class="btn btn-primary">Create User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>