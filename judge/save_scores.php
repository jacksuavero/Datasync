<?php
require '../config/dbcon.php';
include('../includes/log_helper.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $judge_id = $_POST['judge_id'];
    $criteria_id = $_POST['criteria_id'];
    $scores = $_POST['scores'] ?? [];

    if (empty($event_id) || empty($judge_id) || empty($criteria_id)) {
        die("Invalid submission.");
    }

    foreach ($scores as $contestant_id => $score) {
        $score = floatval($score); // ensure numeric

        // Check if existing
        $check = $conn->query("
            SELECT id FROM scores 
            WHERE judge_id='$judge_id' 
              AND event_id='$event_id' 
              AND criteria_id='$criteria_id' 
              AND contestant_id='$contestant_id'
        ");

        if ($check && $check->num_rows > 0) {
            // Update
            $conn->query("
                UPDATE scores 
                SET score='$score', is_finalized=1 
                WHERE judge_id='$judge_id' 
                  AND event_id='$event_id' 
                  AND criteria_id='$criteria_id' 
                  AND contestant_id='$contestant_id'
            ");
        } else {
            // Insert new
            $conn->query("
                INSERT INTO scores (judge_id, event_id, contestant_id, criteria_id, score, is_finalized) 
                VALUES ('$judge_id', '$event_id', '$contestant_id', '$criteria_id', '$score', 1)
            ");
        }
    }

    // ✅ Dynamic redirect to same event & criteria
    $redirectUrl = "index.php?event_id={$event_id}&criteria_id={$criteria_id}";

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
Swal.fire({
  icon: 'success',
  title: 'Scores Finalized!',
  text: 'Scores finalized and locked successfully for this criteria.',
  confirmButtonColor: '#28a745',
  timer: 2500,
  showConfirmButton: false
}).then(() => {
  window.location.href = '$redirectUrl';
});
</script>
</body>
</html>
HTML;
}
?>
