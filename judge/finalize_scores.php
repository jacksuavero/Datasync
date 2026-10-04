<?php
require '../config/dbcon.php';

$event_id = $_GET['event_id'];
$criteria_id = $_GET['criteria_id'];
$judge_id = $_SESSION['user_id'];

$conn->query("UPDATE scores SET is_finalized=1 
              WHERE event_id='$event_id' 
              AND criteria_id='$criteria_id' 
              AND judge_id='$judge_id'");

echo "<script>alert('Scores finalized and locked!'); window.location.href='index.php';</script>";
?>
