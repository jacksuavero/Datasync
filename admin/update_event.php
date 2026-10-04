<?php
require_once './config/dbcon.php';
include('../includes/log_helper.php');
session_start();

if (isset($_POST['update_event'])) {
    $id = $_POST['event_id'];
    $name = $_POST['event_name'];
    $category = $_POST['event_category'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $venue = $_POST['venue'];

    $status = (date('Y-m-d') < $start) ? 'Upcoming' : ((date('Y-m-d') > $end) ? 'Ended' : 'Ongoing');

    $sql = "UPDATE events SET event_name=?, event_category=?, start_date=?, end_date=?, venue=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $name, $category, $start, $end, $venue, $status, $id);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Event updated successfully!'); window.location.href='view_events.php';</script>";
    } else {
        echo "<script>alert('❌ Update failed: " . $conn->error . "'); window.history.back();</script>";
    }
}
?>
