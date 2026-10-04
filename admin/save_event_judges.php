<?php
require '../config/dbcon.php';
include('../includes/log_helper.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? '';
    $judge_ids = $_POST['judge_ids'] ?? [];

    if (empty($event_id)) {
        echo <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Missing Event',
            text: 'Please select an event before proceeding.',
            confirmButtonColor: '#d33'
        }).then(() => { window.history.back(); });
        </script>
        HTML;
        exit;
    }

    $event_id = intval($event_id);
    $judge_ids = array_map('intval', $judge_ids);

    // GET EVENT NAME FOR LOGS
    $event_name = "";
    $stmtEvent = $conn->prepare("SELECT event_name FROM events WHERE id=?");
    $stmtEvent->bind_param("i", $event_id);
    $stmtEvent->execute();
    $stmtEvent->bind_result($event_name);
    $stmtEvent->fetch();
    $stmtEvent->close();

    // GET OLD JUDGES BEFORE REMOVAL
    $old_judges = [];
    $sql_old = $conn->query("
        SELECT CONCAT(first_name, ' ', last_name) AS judge_name 
        FROM event_judges ej 
        JOIN users u ON ej.judge_id = u.id 
        WHERE ej.event_id = $event_id
    ");

    while ($row = $sql_old->fetch_assoc()) {
        $old_judges[] = $row['judge_name'];
    }

    // Remove all current judges
    $conn->query("DELETE FROM event_judges WHERE event_id = '$event_id'");

    // Insert only selected judges
    $added_judges = [];
    if (!empty($judge_ids)) {
        $stmt = $conn->prepare("INSERT INTO event_judges (event_id, judge_id) VALUES (?, ?)");

        foreach ($judge_ids as $jid) {

            // GET JUDGE NAME
            $judge_name = "";
            $st = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id=?");
            $st->bind_param("i", $jid);
            $st->execute();
            $st->bind_result($judge_name);
            $st->fetch();
            $st->close();

            $added_judges[] = $judge_name;

            // Insert into event_judges
            $stmt->bind_param("ii", $event_id, $jid);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ============================
    //        🔥 ADD LOG HERE
    // ============================
    $admin_id = $_SESSION['user_id'];

    $log_description = "Updated judges for event: $event_name\n";

    // Old judges
    $log_description .= "Old Judges: " . (empty($old_judges) ? "None" : implode(', ', $old_judges)) . "\n";

    // New judges
    $log_description .= "New Judges: " . (empty($added_judges) ? "None" : implode(', ', $added_judges));

    logAction($conn, $admin_id, "Updated Event Judges", $log_description);

    // ============================

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
        title: 'Judges Updated!',
        text: 'Event judges have been successfully updated.',
        confirmButtonColor: '#28a745',
        timer: 2000,
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
