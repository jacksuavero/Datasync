<?php
require '../config/dbcon.php';
include('../includes/log_helper.php');

header('Content-Type: text/plain'); // ensures plain text response

if (!isset($_POST['id'], $_POST['action'])) {
    echo "Missing parameters";
    exit;
}

$id = intval($_POST['id']);
$action = $_POST['action'];

// Toggle Top Finalist
if ($action === 'toggleTop') {
    $row = $conn->query("SELECT is_top_finalist, is_eliminated FROM contestants WHERE id='$id'")->fetch_assoc();
    if (!$row) {
        echo "Contestant not found";
        exit;
    }
    if ($row['is_eliminated']) {
        echo "Cannot mark an eliminated contestant as Top";
        exit;
    }

    $newTop = $row['is_top_finalist'] ? 0 : 1;
    $stmt = $conn->prepare("UPDATE contestants SET is_top_finalist=? WHERE id=?");
    $stmt->bind_param("ii", $newTop, $id);
    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "Failed to update top status";
    }
    $stmt->close();
}

// Eliminate contestant
elseif ($action === 'eliminate') {
    $stmt = $conn->prepare("UPDATE contestants SET is_eliminated=1, is_top_finalist=0 WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "Failed to eliminate contestant";
    }
    $stmt->close();
}

else {
    echo "Invalid action";
}

$conn->close();
?>
