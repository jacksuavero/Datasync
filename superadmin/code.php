<?php
require('../config/dbcon.php');


if (isset($_POST["add_admin_btn"])) {

    $fname = $_POST["fname"];
    $mname = $_POST["mname"];
    $lname = $_POST["lname"];
    $username = $_POST["username"];
    $password = $_POST["password"];


    $save_query = "INSERT INTO admins(firstname, middlename, lastname, username, password) VALUES('$fname', '$mname', '$lname', '$username', '$password')";
    $query_run = mysqli_query($conn, $save_query);

    if ($query_run) {
        echo $result = "Admin Added Successfully!";
    } else {
        echo $result = "failed added!";
    }

}

if (isset($_POST['adminId'])) {
    $adminId = $_POST['adminId'];

    // Fetch admin details from the database
    $fetch_query = "SELECT * FROM admins WHERE id = $adminId";
    $result = mysqli_query($conn, $fetch_query);

    if ($result) {
        // Check if any rows were returned
        if (mysqli_num_rows($result) > 0) {
            $adminDetails = mysqli_fetch_assoc($result);

            // Convert the admin details to JSON format
            $jsonResponse = json_encode($adminDetails);
            echo $jsonResponse;
        } else {
            echo json_encode(['error' => 'Admin not found']);
        }
    } else {
        echo json_encode(['error' => 'Error fetching admin details']);
    }
}

if (isset($_POST['updateAdmin'])) {
    $adminId = $_POST['aID'];
    $fname = $_POST['fname'];
    $mname = $_POST['mname'];
    $lname = $_POST['lname'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $update_query = "UPDATE admins SET firstname='$fname', middlename='$mname', lastname='$lname', username='$username', password='$password' WHERE id = $adminId";
    $query_run = mysqli_query($conn, $update_query);

    if ($query_run) {
        echo $result = "Admin Update Successfully!";
    } else {
        echo $result = "Error updating admin details";
    }
}

if (isset($_POST['deleteAdmin'])) {
    $adminId = $_POST['adminId'];

    // Perform the delete operation in the database
    $delete_query = "DELETE FROM admins WHERE id = $adminId";
    $query_run = mysqli_query($conn, $delete_query);

    if ($query_run) {
        echo "Admin deleted successfully!";
    } else {
        echo "Error deleting admin";
    }
}



?>