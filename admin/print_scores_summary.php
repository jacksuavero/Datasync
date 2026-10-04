<?php
require '../config/dbcon.php';
include('../includes/log_helper.php');
session_start();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$admin_name = "Admin"; // fallback in case DB fails

// Fetch admin name from DB
$admin_q = $conn->query("SELECT first_name, last_name FROM users WHERE id='$user_id'");
if ($admin_q && $admin_q->num_rows > 0) {
    $admin_row = $admin_q->fetch_assoc();
    $admin_name = $admin_row['first_name'] . ' ' . $admin_row['last_name'];
}



$event_id = $_GET['event_id'] ?? null;
if (!$event_id) die("Event not specified.");

// Access validation
if ($role === 'admin' || $role === 'superadmin') {
    $event = $conn->query("SELECT * FROM events WHERE id='$event_id'")->fetch_assoc();
} else {
    $event = $conn->query("SELECT * FROM events WHERE id='$event_id' AND created_by='$user_id'")->fetch_assoc();
}
if (!$event) die("<p class='text-danger'>Access Denied.</p>");

// Fetch criteria
$criteria = $conn->query("SELECT * FROM criteria WHERE event_id='$event_id'");
$criteria_arr = [];
while ($c = $criteria->fetch_assoc()) {
    $criteria_arr[] = $c;
}

// Fetch judges
$judges = $conn->query("
    SELECT u.first_name, u.last_name 
    FROM event_judges ej
    JOIN users u ON ej.judge_id = u.id
    WHERE ej.event_id = '$event_id'
");

// Function to calculate contestant scores
function getContestantScores($conn, $event_id, $criteria_arr, $ids = []) {
    $filter = "";
    if (!empty($ids)) {
        $filter = "AND id IN (" . implode(",", array_map('intval', $ids)) . ")";
    }

    $contestants = $conn->query("
        SELECT * FROM contestants 
        WHERE event_id='$event_id' 
        AND is_top_finalist=1 
        AND is_eliminated=0 
        $filter
        ORDER BY number ASC
    ");

    $results = [];
    while ($row = $contestants->fetch_assoc()) {
        $total = 0;
        $scores = [];
        foreach ($criteria_arr as $c) {
            $avg_q = $conn->query("
                SELECT AVG(score) AS avg_score 
                FROM scores 
                WHERE event_id='$event_id' 
                AND criteria_id='{$c['id']}' 
                AND contestant_id='{$row['id']}'
            ");
            $avg = ($avg_q && $avg_q->num_rows) ? $avg_q->fetch_assoc()['avg_score'] : 0;
            $scores[] = number_format($avg, 2);
            $total += $avg * ($c['percentage'] / 100);
        }
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'number' => $row['number'],
            'scores' => $scores,
            'total' => $total
        ];
    }
    usort($results, fn($a, $b) => $b['total'] <=> $a['total']);
    return $results;
}

// Check if event is paired
$pair_check = $conn->query("SELECT COUNT(*) AS c FROM pairs WHERE event_id='$event_id'");
$is_paired = $pair_check->fetch_assoc()['c'] > 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Scores Summary - <?= htmlspecialchars($event['event_name']); ?></title>
    <style>
        @page { size: landscape; margin: 20mm; }
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { height: 70px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; page-break-inside: avoid; }
        th, td { border: 1px solid #333; padding: 6px; text-align: center; }
        th { background: #222; color: #fff; }
        .section-title { text-align: center; font-weight: bold; margin-top: 30px; font-size: 18px; }
        .signature-section { margin-top: 60px; text-align: center; }
        .sig-line { display: inline-block; width: 250px; margin: 20px; text-align: center; }
        .sig-line hr { border: none; border-top: 1px solid #000; margin-bottom: 5px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <img src="./assets/img/logo-mini.svg" alt="Logo"><br>
        <h2><?= htmlspecialchars($event['event_name']); ?></h2>
        <small><em>Official Tabulated Scores (Top Finalists Only)</em></small>
    </div>

<?php
if ($is_paired) {
    // Fetch paired MR and MS IDs
    $pair_q = $conn->query("SELECT mr_id, ms_id FROM pairs WHERE event_id='$event_id'");
    $mr_ids = [];
    $ms_ids = [];
    while ($p = $pair_q->fetch_assoc()) {
        $mr_ids[] = $p['mr_id'];
        $ms_ids[] = $p['ms_id'];
    }

    // Fetch results separately
    $mr_results = getContestantScores($conn, $event_id, $criteria_arr, $mr_ids);
    $ms_results = getContestantScores($conn, $event_id, $criteria_arr, $ms_ids);

    // --- MR Section ---
    if (!empty($mr_results)) {
        echo '<div class="section-title">🤴 Mr. Category</div>';
        echo '<table><thead><tr><th>Rank</th><th>No.</th><th>Name</th>';
        foreach ($criteria_arr as $c) {
            echo '<th>' . htmlspecialchars($c['name']) . '<br><small>' . $c['percentage'] . '%</small></th>';
        }
        echo '<th>Final Score</th></tr></thead><tbody>';
        $rank = 1;
        foreach ($mr_results as $r) {
            echo "<tr><td>{$rank}</td><td>{$r['number']}</td><td>" . htmlspecialchars($r['name']) . "</td>";
            foreach ($r['scores'] as $s) echo "<td>{$s}</td>";
            echo "<td><strong>" . number_format($r['total'], 2) . "</strong></td></tr>";
            $rank++;
        }
        echo '</tbody></table>';
    }

    // --- PAGE BREAK + MS Section ---
    echo '<div class="page-break"></div>';
    if (!empty($ms_results)) {
        echo '<div class="section-title">👑 Ms. Category</div>';
        echo '<table><thead><tr><th>Rank</th><th>No.</th><th>Name</th>';
        foreach ($criteria_arr as $c) {
            echo '<th>' . htmlspecialchars($c['name']) . '<br><small>' . $c['percentage'] . '%</small></th>';
        }
        echo '<th>Final Score</th></tr></thead><tbody>';
        $rank = 1;
        foreach ($ms_results as $r) {
            echo "<tr><td>{$rank}</td><td>{$r['number']}</td><td>" . htmlspecialchars($r['name']) . "</td>";
            foreach ($r['scores'] as $s) echo "<td>{$s}</td>";
            echo "<td><strong>" . number_format($r['total'], 2) . "</strong></td></tr>";
            $rank++;
        }
        echo '</tbody></table>';
    }
} else {
    // Non-paired event
    $results = getContestantScores($conn, $event_id, $criteria_arr);
    echo '<table><thead><tr><th>Rank</th><th>No.</th><th>Name</th>';
    foreach ($criteria_arr as $c) {
        echo '<th>' . htmlspecialchars($c['name']) . '<br><small>' . $c['percentage'] . '%</small></th>';
    }
    echo '<th>Final Score</th></tr></thead><tbody>';
    $rank = 1;
    foreach ($results as $r) {
        echo "<tr><td>{$rank}</td><td>{$r['number']}</td><td>" . htmlspecialchars($r['name']) . "</td>";
        foreach ($r['scores'] as $s) echo "<td>{$s}</td>";
        echo "<td><strong>" . number_format($r['total'], 2) . "</strong></td></tr>";
        $rank++;
    }
    echo '</tbody></table>';
}
?>

    <div class="signature-section">
    <h4>Signatures</h4>
    <?php 
    // Display all judges
    $judges->data_seek(0);
    while ($j = $judges->fetch_assoc()) {
        echo '<div class="sig-line"><hr><strong>'
            . htmlspecialchars($j['first_name'] . ' ' . $j['last_name'])
            . '</strong><br>Judge</div>';
    }

    // Display logged-in admin as a signatory
    echo '<div class="sig-line"><hr><strong>'
        . htmlspecialchars($admin_name)
        . '</strong><br>Admin</div>';
    ?>
</div>


    <script>window.print();</script>
</body>
</html>
