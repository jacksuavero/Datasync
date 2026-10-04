<?php
require_once '../config/dbcon.php';
require '../config/PHPMailer/src/PHPMailer.php';
require '../config/PHPMailer/src/SMTP.php';
require '../config/PHPMailer/src/Exception.php';
include('../includes/log_helper.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Invalid request ID.");
}

$request_id = intval($_GET['id']);

// ✅ Fetch all necessary details including event_id
$sql = "SELECT email, firstname, lastname, middlename, role, event_id 
        FROM request 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $email = $row['email'];
    $firstname = $row['firstname'];
    $lastname = $row['lastname'];
    $middlename = $row['middlename'];
    $role = $row['role'];
    $event_id = $row['event_id'];

    // Generate a temporary password
    $password = substr(md5(uniqid(rand(), true)), 0, 8);

    // ✅ Update request status and password
    $sql_update = "UPDATE request SET status = 'approve', password = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $password, $request_id);

    if ($stmt_update->execute()) {
        // ✅ Insert into users including event_id
        $sql_user = "INSERT INTO users (first_name, last_name, middle_name, email, role, password, event_id, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ssssssi", $firstname, $lastname, $middlename, $email, $role, $password, $event_id);

        if ($stmt_user->execute()) {
            // ✅ Send approval email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'datasyncteam@datasyncv1.online';
                $mail->Password = 'Datasync_v1'; // your webmail password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
                $mail->Port = 465; // or 587 for TLS

                $mail->setFrom('datasyncteam@datasyncv1.online', 'Datasync');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Account Request Approved";
                $mail->Body = "
                    Hello <b>$firstname $lastname</b>,<br><br>
                    Your <b>$role</b> account request has been approved.<br>
                    <b>Email:</b> $email<br>
                    <b>Password:</b> $password<br><br>
                    You can now log in to the system.<br><br>
                    Best regards,<br>
                    <b>DataSync Team</b>
                ";

                $mail->send();

                // ✅ SweetAlert2 — will appear properly even after PHP
                echo "
                <!DOCTYPE html>
                <html>
                <head>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Request Approved!',
                            text: 'An email has been sent to the user.',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'account_request.php';
                        });
                    </script>
                </body>
                </html>";
                exit;

            } catch (Exception $e) {
                echo "❌ Error sending email: {$mail->ErrorInfo}";
            }
        } else {
            echo "❌ Error inserting into users table: " . $conn->error;
        }
    } else {
        echo "❌ Error updating request status: " . $conn->error;
    }
} else {
    echo "❌ Request not found.";
}

$conn->close();
?>
