<?php
require '../config/dbcon.php';
include('../includes/log_helper.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $names = $_POST['criteria_names'] ?? [];
    $percents = $_POST['criteria_percents'] ?? [];

    // Basic input validation
    if (empty($event_id) || empty($names) || count($names) !== count($percents)) {
        echo <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Invalid Input',
            text: 'Please fill out all required fields correctly.',
            confirmButtonColor: '#d33'
        }).then(() => {
            window.history.back();
        });
        </script>
        HTML;
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO criteria (event_id, name, percentage, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        echo <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Database Error',
            text: 'Failed to prepare SQL statement. Please try again later.',
            confirmButtonColor: '#d33'
        }).then(() => {
            window.history.back();
        });
        </script>
        HTML;
        exit;
    }

    // Check total percentage
    $totalPercent = 0;
    foreach ($percents as $p) {
        $totalPercent += floatval($p);
    }

    if ($totalPercent > 100) {
        echo <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Invalid Percentage',
            text: 'Total percentage exceeds 100%. Please adjust your values.',
            confirmButtonColor: '#d33'
        }).then(() => {
            window.history.back();
        });
        </script>
        HTML;
        exit;
    }

    // Insert all valid criteria
    for ($i = 0; $i < count($names); $i++) {
        $name = trim($names[$i]);
        $percent = floatval($percents[$i]);
        if ($name !== '' && $percent > 0) {
            $stmt->bind_param("isd", $event_id, $name, $percent);
            $stmt->execute();
        }
    }

    // ✅ SweetAlert success with redirect
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
        title: 'Criteria Saved!',
        text: 'All criteria have been successfully added.',
        confirmButtonColor: '#28a745',
        timer: 2500,
        showConfirmButton: false
    }).then(() => {
        window.location.href = 'assign_judge.php';
    });
    </script>
    </body>
    </html>
    HTML;
}
?>
