<?php
session_start();
require_once 'dbcon.php';

function login($email, $password)
{
    global $conn;

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("SQL prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $_SESSION['login_error'] = "No account found with that email.";
        return false;
    }

    $user = $result->fetch_assoc();

    // ✅ 1. Check account status
    if (strtolower($user['status']) !== 'active') {
        $_SESSION['login_error'] = "Your account is currently inactive. Please contact the administrator.";
        return false;
    }

    // ✅ 2. Verify password (handles both encrypted + non-encrypted)
    $is_valid = false;

    // Check if password looks like a bcrypt hash (starts with $2y$ or $2b$)
    if (preg_match('/^\$2[aby]\$/', $user['password'])) {
        // Encrypted password → verify with password_verify()
        if (password_verify($password, $user['password'])) {
            $is_valid = true;
        }
    } else {
        // Plain text → direct comparison
        if ($password === $user['password']) {
            $is_valid = true;
        }
    }

    if (!$is_valid) {
        $_SESSION['login_error'] = "Invalid email or password.";
        return false;
    }

    // ✅ 3. Login success — store session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['login_success'] = true;

    return true;
}
