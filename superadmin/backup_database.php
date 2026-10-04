<?php
session_start();
require '../config/dbcon.php';
include('../includes/log_helper.php');
// ✅ Restrict access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../index.php");
    exit();
}

$servername = "localhost";
$username = "u646489592_sophie";
$password = "Datasync_v1";
$dbname = "u646489592_datasync";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Generate filename
$backup_file = 'backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';

// Start backup content
$output = "-- Database Backup: {$dbname}\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Drop + Create structure
    $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_assoc();
    $output .= "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n\n";

    // Insert data
    $rows = $conn->query("SELECT * FROM `$table`");
    if ($rows->num_rows > 0) {
        while ($row = $rows->fetch_assoc()) {
            $vals = array_map(fn($v) => "'" . $conn->real_escape_string($v) . "'", array_values($row));
            $output .= "INSERT INTO `$table` VALUES (" . implode(",", $vals) . ");\n";
        }
    }
    $output .= "\n";
}

$conn->close();

// Write to file
file_put_contents($backup_file, $output);

// Download backup
header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
header('Content-Length: ' . filesize($backup_file));
readfile($backup_file);

// Delete temp file
unlink($backup_file);
exit();
?>
