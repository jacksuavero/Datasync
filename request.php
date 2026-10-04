<?php
require_once './config/dbcon.php';
require './config/PHPMailer/src/PHPMailer.php';
require './config/PHPMailer/src/SMTP.php';
require './config/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $middlename = $_POST['middlename'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $event_id = isset($_POST['event_id']) && !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;

    // ================================
    // DUPLICATE CHECKS (users, request, otp_verifications)
    // ================================
    function swal_redirect($icon, $title, $text) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
        <script>
            Swal.fire({
                icon: "'.$icon.'",
                title: "'.$title.'",
                text: "'.$text.'",
                allowOutsideClick: false,
            }).then(() => {
                window.location.href = document.referrer;
            });
        </script>
        </body>
        </html>';
        exit();
    }

    // 1. Check USERS table
    $stmt_users = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_users->bind_param("s", $email);
    $stmt_users->execute();
    $res_users = $stmt_users->get_result();
    if ($res_users->num_rows > 0) {
        swal_redirect('error', 'Already Registered', 'You already have an account.');
    }

    // 2. Check REQUEST table
    $stmt_request = $conn->prepare("SELECT id FROM request WHERE email = ?");
    $stmt_request->bind_param("s", $email);
    $stmt_request->execute();
    $res_request = $stmt_request->get_result();
    if ($res_request->num_rows > 0) {
        swal_redirect('warning', 'Pending Request Found', 'You have already requested an account.');
    }

    // 3. Check OTP_VERIFICATIONS table (multiple requests)
    $stmt_otp = $conn->prepare("SELECT id FROM otp_verifications WHERE email = ?");
    $stmt_otp->bind_param("s", $email);
    $stmt_otp->execute();
    $res_otp = $stmt_otp->get_result();
    if ($res_otp->num_rows > 0) {
        swal_redirect('error', 'Multiple Requests Detected', 'You already requested an OTP. Contact ADMIN for assistance.');
    }

    // ================================
    // VALIDATE ROLE
    // ================================
    if (!in_array($role, ['admin', 'judge'])) {
        echo "<script>alert('Invalid role selected.'); window.history.back();</script>";
        exit();
    }

    // ================================
    // VALIDATE EVENT FOR JUDGES
    // ================================
    if ($role === 'judge') {
        if (!$event_id) {
            echo "<script>alert('Please provide an Event ID for judge role.'); window.history.back();</script>";
            exit();
        }
        $check_event = $conn->prepare("SELECT id FROM events WHERE id = ?");
        $check_event->bind_param("i", $event_id);
        $check_event->execute();
        $result_event = $check_event->get_result();
        if ($result_event->num_rows === 0) {
            echo "<script>alert('Invalid Event ID.'); window.history.back();</script>";
            exit();
        }
        $check_event->close();
    }

    // ================================
    // GENERATE OTP
    // ================================
    $otp = rand(100000, 999999);
    $otp_expiry = time() + 300; // 5 min

    // Save OTP + data
    $otp_sql = "INSERT INTO otp_verifications (firstname, lastname, middlename, email, role, event_id, otp, otp_expiry)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $otp_stmt = $conn->prepare($otp_sql);
    $otp_stmt->bind_param("sssssiii", $firstname, $lastname, $middlename, $email, $role, $event_id, $otp, $otp_expiry);

    if ($otp_stmt->execute()) {
        // ================================
        // SEND EMAIL VIA PHPMailer
        // ================================
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'datasyncteam@datasyncv1.online';
            $mail->Password = 'Datasync_v1';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('datasyncteam@datasyncv1.online', 'Datasync');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code for Account Request';
            $mail->Body = "<p>Hello <b>$firstname $lastname</b>,</p>
                           <p>Your OTP code for DataSync is: <b>$otp</b></p>
                           <p>This code expires in 5 minutes.</p>";
            $mail->AltBody = "Hello $firstname $lastname, Your OTP code: $otp. Expires in 5 minutes.";

            $mail->send();

            // Redirect to OTP verification page
            header("Location: verify_otp.php?email=" . urlencode($email));
            exit();
        } catch (Exception $e) {
            echo "Failed to send OTP: " . $mail->ErrorInfo;
        }
    } else {
        echo "Error saving OTP data: " . $conn->error;
    }

    $otp_stmt->close();
    $conn->close();
}
?>
