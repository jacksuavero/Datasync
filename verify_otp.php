<?php
require_once './config/dbcon.php';
include('./includes/log_helper.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $otp_input = $_POST['otp'];

    $sql = "SELECT * FROM otp_verifications WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Function to show SweetAlert2 and redirect
    function swal_redirect($icon, $title, $text, $redirect) {
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
                timer: 2500,
                timerProgressBar: true
            }).then(() => {
                window.location.href = "'.$redirect.'";
            });
        </script>
        </body>
        </html>';
        exit();
    }

    if ($row) {
        $otp = $row['otp'];
        $otp_expiry = $row['otp_expiry'];

        if ($otp_input == $otp && time() < $otp_expiry) {

            // Insert data from otp_verifications to request table
            $insert_sql = "INSERT INTO request (firstname, lastname, middlename, email, role, event_id, status)
               SELECT firstname, lastname, middlename, email, role, event_id, 'Pending'
               FROM otp_verifications
               WHERE email = ?";

            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("s", $email);

            if ($insert_stmt->execute()) {

                // Delete OTP record after successful insert
                $delete_sql = "DELETE FROM otp_verifications WHERE email = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("s", $email);
                $delete_stmt->execute();

                swal_redirect('success', 'OTP Verified', 'Your OTP is correct. Your request has been submitted.', 'index.php');

            } else {
                swal_redirect('error', 'Error', 'Failed to submit your request.', 'request_account.php');
            }

        } else {
            swal_redirect('warning', 'Invalid OTP', 'OTP is incorrect or expired.', 'request_account.php');
        }
    } else {
        swal_redirect('error', 'No OTP Found', 'No OTP found for this email.', 'request_account.php');
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./assets/img/logo-mini.svg">
    <title>Verify OTP - DataSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #e3f2fd;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.4s ease-in;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #007BC9 0%, #008FE8 100%);
            filter: blur(100px);
            opacity: 0.3;
            z-index: 0;
        }

        body.loaded {
            opacity: 1;
        }

        .main-container {
            width: 100%;
            max-width: 380px;
            animation: slideUp 0.6s ease-out;
            position: relative;
            z-index: 1;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 123, 201, 0.2);
            padding: 32px 28px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .header-section {
            text-align: center;
            margin-bottom: 24px;
        }

        

        .header-section h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .header-section p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }
.logo-container .logo-mini {
    max-width: 100%;
    height: auto;
}

        .email-display {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #334155;
        }

        .email-display i {
            color: #007BC9;
            font-size: 14px;
        }

        .email-display strong {
            color: #1e293b;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #334155;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .otp-input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            padding-left: 40px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Inter', monospace;
            letter-spacing: 4px;
            text-align: center;
        }

        .form-control:focus {
            outline: none;
            border-color: #007BC9;
            box-shadow: 0 0 0 4px rgba(0, 123, 201, 0.1);
        }

        .form-control::placeholder {
            color: #94a3b8;
            letter-spacing: normal;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 14px;
        }

        .btn-verify {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #007BC9 0%, #008FE8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 123, 201, 0.4);
        }

        .btn-verify:active {
            transform: translateY(0);
        }

        .btn-verify:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .help-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .help-text {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .resend-link {
            color: #007BC9;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .resend-link:hover {
            color: #006ba8;
            text-decoration: underline;
        }

        .back-link {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: #64748b;
        }

        .back-link a {
            color: #007BC9;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .info-box {
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 12px;
            color: #1e40af;
            line-height: 1.5;
        }

        .info-box i {
            font-size: 14px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* Responsive */
        @media (max-width: 576px) {
            body {
                padding: 16px;
            }

            .card {
                padding: 28px 24px;
            }

            .header-section h1 {
                font-size: 20px;
            }

            .logo-container {
                width: 55px;
                height: 55px;
            }

            .logo-container svg {
                width: 32px;
                height: 32px;
            }
        }

        @media (max-width: 400px) {
            .card {
                padding: 24px 20px;
            }

            .header-section h1 {
                font-size: 18px;
            }

            .form-control {
                font-size: 14px;
                letter-spacing: 3px;
            }
        }
    </style>
</head>
<body onload="document.body.classList.add('loaded')">

<div class="main-container">
    <div class="card">
        <div class="header-section">
            <div class="logo-container">
    <img src="assets/img/logo-mini.svg" alt="Logo" class="logo-mini">
</div>

            <h1>Verify Your Account</h1>
            <p>Enter the 6-digit code sent to your email</p>
        </div>

        <div class="email-display">
            <i class="fas fa-envelope"></i>
            <span>Code sent to <strong><?php echo htmlspecialchars($_GET['email'] ?? ''); ?></strong></span>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <span>The verification code will expire in 5 minutes. Please enter it below to complete your registration.</span>
        </div>

        <form method="post" action="verify_otp.php" id="otpForm">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            
            <div class="form-group">
                <label for="otp" class="form-label">Verification Code</label>
                <div class="otp-input-wrapper">
                    <i class="fas fa-shield-alt input-icon"></i>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="otp" 
                        name="otp" 
                        placeholder="000000"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        required
                        autocomplete="off"
                    >
                </div>
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">
                <i class="fas fa-check-circle"></i>
                <span>Verify & Continue</span>
            </button>
        </form>

        <div class="help-section">
            <p class="help-text">Didn't receive the code?</p>
            <p class="help-text">Make sure you have entered a correct email.</p>

                
        </div>

        <div class="back-link">
            <a href="request_account.php">
                <i class="fas fa-arrow-left"></i> Back to registration
            </a>
        </div>
    </div>
</div>

<script>
// Auto-format OTP input
document.getElementById('otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Form submission handler
document.getElementById('otpForm').addEventListener('submit', function(e) {
    const verifyBtn = document.getElementById('verifyBtn');
    const btnText = verifyBtn.querySelector('span');
    const btnIcon = verifyBtn.querySelector('i');
    
    // Disable button and show loading state
    verifyBtn.disabled = true;
    btnIcon.className = 'loading-spinner';
    btnText.textContent = 'Verifying...';
});

// Resend OTP function
function resendOTP(event) {
    event.preventDefault();
    const email = '<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>';
    
    // Show loading state
    const link = event.currentTarget;
    const originalText = link.innerHTML;
    link.innerHTML = '<i class="loading-spinner"></i> Sending...';
    link.style.pointerEvents = 'none';
    
    // Simulate API call (replace with actual resend logic)
    setTimeout(() => {
        alert('A new verification code has been sent to your email.');
        link.innerHTML = originalText;
        link.style.pointerEvents = 'auto';
    }, 1500);
}

// Auto-focus on OTP input
window.addEventListener('load', function() {
    document.getElementById('otp').focus();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>