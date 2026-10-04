<?php
require_once '../config/dbcon.php';
require '../config/PHPMailer/src/PHPMailer.php';
require '../config/PHPMailer/src/SMTP.php';
require '../config/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$request_id = $_GET['id'];

$sql = "SELECT email FROM request WHERE id = $request_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$email = $row['email'];

if ($email) {
    $sql = "UPDATE request SET status = 'rejected' WHERE id = $request_id";

    if ($conn->query($sql) === TRUE) {
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
            $mail->Subject = "Account Request Rejected";
            $mail->Body = "We regret to inform you that your account request has been rejected.";

            $mail->send();

            // ✅ Proper SweetAlert2
            echo "
            <!DOCTYPE html>
            <html>
            <head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Rejected!',
                        text: 'The email has been sent to the user.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'account_request.php';
                    });
                </script>
            </body>
            </html>";
            exit;

        } catch (Exception $e) {
            echo "Error sending email: {$mail->ErrorInfo}";
        }
    } else {
        echo "Error updating record: " . $conn->error;
    }
} else {
    echo "Request not found.";
}

$conn->close();
?>
