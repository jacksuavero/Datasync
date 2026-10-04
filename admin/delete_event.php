<?php
require_once './config/dbcon.php';
include('../includes/log_helper.php');

session_start();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('🗑️ Event deleted successfully!'); window.location.href='view_events.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to delete event.'); window.history.back();</script>";
    }
}
?>
