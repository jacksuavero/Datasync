<?php
// ======================= LOG HELPER =======================
// This file handles activity logging for Admin, Superadmin, Judges, etc.
// ===========================================================

if (!function_exists('logAction')) {
    function logAction($conn, $user_id, $action, $description = null) {
        if (empty($user_id) || empty($action)) return;

        $stmt = $conn->prepare("INSERT INTO logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $user_id, $action, $description);
        $stmt->execute();
        $stmt->close();
    }
}
?>
