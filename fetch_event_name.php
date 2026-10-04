<?php
require_once './config/dbcon.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = $conn->prepare("SELECT event_name FROM events WHERE id = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();

    if ($row = $result->fetch_assoc()) {
        echo $row['event_name'];
    } else {
        echo '';
    }
}
?>
