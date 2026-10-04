<?php
require_once '../config/dbcon.php';
require '../config/PHPMailer/src/PHPMailer.php';
require '../config/PHPMailer/src/SMTP.php';
require '../config/PHPMailer/src/Exception.php';
include('../includes/log_helper.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Validate GET parameters
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Invalid request ID.");
}

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) exit("Invalid User ID");

// Include SweetAlert JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Action</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php

// Initialize message variables
$msg = '';
$msg_type = 'success';

// Switch based on action
switch ($action) {
    case 'activate':
        $conn->query("UPDATE users SET status='active' WHERE id=$id");
        $msg = "User account activated successfully.";
        break;

    case 'deactivate':
        $conn->query("UPDATE users SET status='inactive' WHERE id=$id");
        $msg = "User account deactivated successfully.";
        break;

    case 'reset':
        $query = $conn->prepare("SELECT first_name, last_name, email, role FROM users WHERE id=?");
        $query->bind_param("i", $id);
        $query->execute();
        $result = $query->get_result();
        if ($result->num_rows === 0) {
            $msg = "User not found.";
            $msg_type = 'error';
        } else {
            $user = $result->fetch_assoc();
            $new_password_plain = substr(md5(uniqid()), 0, 8);
            $new_password_hashed = password_hash($new_password_plain, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update->bind_param("si", $new_password_hashed, $id);
            $update->execute();

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'datasyncteam@datasyncv1.online';
                $mail->Password   = 'Datasync_v1';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('datasyncteam@datasyncv1.online', 'DataSync');
                $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

                $mail->isHTML(true);
                $mail->Subject = "Your Account Password Has Been Reset";
                $mail->Body    = "
                    Hello <b>{$user['first_name']} {$user['last_name']}</b>,<br><br>
                    Your <b>{$user['role']}</b> account password has been successfully reset.<br><br>
                    <b>Email:</b> {$user['email']}<br>
                    <b>New Password:</b> {$new_password_plain}<br><br>
                    Please log in to the system using the new password and change it immediately.<br><br>
                    Best regards,<br>
                    <b>DataSync Team</b>
                ";
                $mail->AltBody = "Hello {$user['first_name']} {$user['last_name']}, Your new password: {$new_password_plain}";

                $mail->send();
                $msg = "Password reset successfully and email sent!";
            } catch (Exception $e) {
                $msg = "Password reset but email failed: {$mail->ErrorInfo}";
                $msg_type = 'error';
            }
        }
        break;

    case 'change_role':
        $role = $_GET['role'] ?? '';
        if (in_array($role, ['superadmin', 'admin', 'judge'])) {
            $conn->query("UPDATE users SET role='$role' WHERE id=$id");
            $msg = "User role updated successfully.";
        } else {
            $msg = "Invalid role selected.";
            $msg_type = 'error';
        }
        break;

   case 'delete':

    // 1️⃣ Check if the user is SUPERADMIN
    $check = $conn->prepare("SELECT email, role FROM users WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $userData = $result->fetch_assoc();
    $check->close();

    if (!$userData) {
        $msg = "User not found.";
        $msg_type = 'error';
        break;
    }

    // ❌ Prevent deleting SUPERADMIN
    if ($userData['role'] === 'superadmin') {
        $msg = "You cannot delete a Super Admin account.";
        $msg_type = 'error';
        break;
    }

    $email = $userData['email'];

    // 2️⃣ Delete request record (if exists)
    if (!empty($email)) {
        $delReq = $conn->prepare("DELETE FROM request WHERE email = ?");
        $delReq->bind_param("s", $email);
        $delReq->execute();
        $delReq->close();
    }

    // 3️⃣ Delete the user
    $delUser = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delUser->bind_param("i", $id);
    $delUser->execute();
    $delUser->close();

    $msg = "User and related request deleted successfully.";
    break;




    default:
        $msg = "Invalid action.";
        $msg_type = 'error';
        break;
}

// Close connection
$conn->close();

// Show SweetAlert message and redirect
?>
<script>
Swal.fire({
    icon: '<?= $msg_type ?>',
    title: '<?= $msg_type == "success" ? "Success!" : "Oops!" ?>',
    text: '<?= $msg ?>',
    confirmButtonText: 'OK'
}).then(() => {
    window.location.href = 'manage_users.php';
});
</script>
</body>
</html>
