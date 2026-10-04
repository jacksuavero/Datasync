<?php
include('../config/dbcon.php');

$event_id = $_GET['event_id'] ?? null;
if (!$event_id) {
    echo json_encode(['error' => 'Missing event_id']);
    exit;
}

$stmt = $conn->prepare("
    SELECT MAX(updated_at) AS latest_time
    FROM scores 
    JOIN contestants ON scores.contestant_id = contestants.id
    WHERE contestants.event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$latest = $row['latest_time'] ?? null;

header('Content-Type: application/json');
echo json_encode(['latest_time' => $latest]);
?>
