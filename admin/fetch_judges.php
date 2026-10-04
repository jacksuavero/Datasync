<?php
require('../config/dbcon.php');
header('Content-Type: application/json');

if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    echo json_encode([]);
    exit;
}

$event_id = intval($_GET['event_id']);

// ✅ Fetch judges who were approved for this event
$query = "
    SELECT 
        u.id, 
        CONCAT(u.first_name, ' ', u.last_name) AS name
    FROM users u
    WHERE u.role = 'judge'
      AND u.status = 'active'
      AND u.event_id = ?
    ORDER BY name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

$judges = [];

while ($row = $result->fetch_assoc()) {
    // Check if already assigned
    $checkAssigned = $conn->prepare("SELECT 1 FROM event_judges WHERE event_id = ? AND judge_id = ?");
    $checkAssigned->bind_param('ii', $event_id, $row['id']);
    $checkAssigned->execute();
    $assignedResult = $checkAssigned->get_result();
    $row['assigned'] = $assignedResult->num_rows > 0;
    $judges[] = $row;
}

echo json_encode($judges);
