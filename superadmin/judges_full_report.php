<?php include('./includes/header.php'); ?>
<?php
require '../config/dbcon.php';

// Get session details
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch event list
$event_query = ($role === 'superadmin')
    ? "SELECT * FROM events ORDER BY event_name ASC"
    : "SELECT * FROM events WHERE created_by='$user_id' ORDER BY event_name ASC";
$events = $conn->query($event_query);

$selected_event_id = $_POST['event_id'] ?? $_GET['event_id'] ?? null;
?>

<div class="wrapper">
    <?php include('./includes/sidebar.php'); ?>
    <div class="main">
        <?php include('./includes/nav.php'); ?>

        <main class="content">
            <div class="container-fluid p-0">
                <h3 class="mb-4">📊 Judges' Full Scores Report</h3>

                <!-- Select Event -->
                <div class="card shadow-sm p-4 mb-4">
                    <form method="post" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Select Event</label>
                            <select class="form-select" name="event_id" required>
                                <option value="">-- Select Event --</option>
                                <?php while ($e = $events->fetch_assoc()): ?>
                                    <option value="<?= $e['id'] ?>" <?= ($selected_event_id == $e['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['event_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3 align-self-end">
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                        </div>
                    </form>
                </div>

                <?php
                if ($selected_event_id):
                    // Restrict admin view to their events
                    if ($role === 'admin') {
                        $chk = $conn->query("SELECT id FROM events WHERE id='$selected_event_id' AND created_by='$user_id'");
                        if ($chk->num_rows === 0) {
                            echo "<div class='alert alert-danger'>You are not authorized to view this event.</div>";
                            include('./includes/footer.php');
                            exit;
                        }
                    }

                    $event = $conn->query("SELECT * FROM events WHERE id='$selected_event_id'")->fetch_assoc();
                    $criteria = $conn->query("SELECT * FROM criteria WHERE event_id='$selected_event_id' ORDER BY id")->fetch_all(MYSQLI_ASSOC);
                    $judges = $conn->query("SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS name FROM event_judges ej JOIN users u ON ej.judge_id=u.id WHERE ej.event_id='$selected_event_id'")->fetch_all(MYSQLI_ASSOC);
                    $is_paired = $conn->query("SELECT COUNT(*) AS c FROM pairs WHERE event_id='$selected_event_id'")->fetch_assoc()['c'] > 0;

                    function fetchContestants($conn, $event_id, $is_paired) {
                        if ($is_paired) {
                            $pairs = $conn->query("
                                SELECT p.*, 
                                       mr.id AS mr_id, mr.name AS mr_name, mr.number AS mr_number, mr.is_eliminated AS mr_elim, mr.is_top_finalist AS mr_top,
                                       ms.id AS ms_id, ms.name AS ms_name, ms.number AS ms_number, ms.is_eliminated AS ms_elim, ms.is_top_finalist AS ms_top
                                FROM pairs p
                                JOIN contestants mr ON p.mr_id = mr.id
                                JOIN contestants ms ON p.ms_id = ms.id
                                WHERE p.event_id = '$event_id'
                                ORDER BY COALESCE(mr.number,0) ASC
                            ");
                            $mr = []; $ms = [];
                            while ($p = $pairs->fetch_assoc()) {
                                $mr[] = ['id'=>$p['mr_id'],'number'=>$p['mr_number'],'name'=>$p['mr_name'],'elim'=>$p['mr_elim'],'top'=>$p['mr_top']];
                                $ms[] = ['id'=>$p['ms_id'],'number'=>$p['ms_number'],'name'=>$p['ms_name'],'elim'=>$p['ms_elim'],'top'=>$p['ms_top']];
                            }
                            return ['mr'=>$mr, 'ms'=>$ms];
                        } else {
                            $solo = $conn->query("SELECT * FROM contestants WHERE event_id='$event_id' ORDER BY CAST(number AS UNSIGNED)");
                            $res = [];
                            while ($c = $solo->fetch_assoc()) {
                                $res[] = ['id'=>$c['id'],'number'=>$c['number'],'name'=>$c['name'],'elim'=>$c['is_eliminated'],'top'=>$c['is_top_finalist']];
                            }
                            return ['solo'=>$res];
                        }
                    }

                    $contestants = fetchContestants($conn, $selected_event_id, $is_paired);

                    function renderScoreTable($conn, $contestants, $criteria, $judges, $title, $tableId) {
                        if (empty($contestants)) return;
                        echo "<div class='d-flex justify-content-between align-items-center mt-4'>
                                <h4 class='fw-bold'>$title</h4>
                                <button class='btn btn-success btn-sm exportBtn' data-table='$tableId'>Export</button>
                              </div>";
                        echo "<div class='table-responsive mt-3'><table class='table table-bordered align-middle text-center exportable' id='$tableId'>";
                        echo '<thead class="table-dark"><tr>';
                        echo '<th rowspan="2">#</th><th rowspan="2">No.</th><th rowspan="2">Contestant</th>';
                        foreach ($criteria as $c) {
                            $c_label = $c['criteria_name'] ?? $c['name'] ?? "Criteria {$c['id']}";
                            echo "<th colspan='".(count($judges)+1)."'>".htmlspecialchars($c_label)." <br><small>({$c['percentage']}%)</small></th>";
                        }
                        echo '<th rowspan="2">Total</th><th rowspan="2">Eliminated</th><th rowspan="2">Top Finalist</th>';
                        echo '</tr><tr>';
                        foreach ($criteria as $c) {
                            foreach ($judges as $j) echo "<th>".htmlspecialchars($j['name'])."</th>";
                            echo "<th>Avg</th>";
                        }
                        echo '</tr></thead><tbody>';

                        $rank = 1;
                        foreach ($contestants as $r) {
                            $class = $r['elim'] ? 'table-danger opacity-75' : ($r['top'] ? 'table-success' : '');
                            echo "<tr class='$class'><td>{$rank}</td><td>{$r['number']}</td><td class='text-start'>".htmlspecialchars($r['name'])."</td>";
                            $total_weighted = 0;
                            foreach ($criteria as $c) {
                                $sum = 0; $cnt = 0;
                                foreach ($judges as $j) {
                                    $score = $conn->query("SELECT score FROM scores WHERE contestant_id='{$r['id']}' AND criteria_id='{$c['id']}' AND judge_id='{$j['id']}'")->fetch_assoc()['score'] ?? '';
                                    echo "<td>".($score === '' ? '-' : number_format($score,2))."</td>";
                                    if ($score !== '') { $sum += floatval($score); $cnt++; }
                                }
                                $avg = $cnt ? $sum / $cnt : '';
                                echo "<td><strong>".($avg === '' ? '-' : number_format($avg,2))."</strong></td>";
                                if ($avg !== '') $total_weighted += ($avg * ($c['percentage'] / 100));
                            }
                            echo "<td><strong>".number_format($total_weighted,2)."</strong></td>";
                            echo "<td>".($r['elim']?'Yes':'No')."</td><td>".($r['top']?'Yes':'No')."</td></tr>";
                            $rank++;
                        }
                        echo '</tbody></table></div>';
                    }

                    echo "<div class='card shadow-sm p-4'>";
                    echo "<h5>".htmlspecialchars($event['event_name'])."</h5>";

                    if ($is_paired) {
                        renderScoreTable($conn, $contestants['ms'], $criteria, $judges, "👑 MISS CANDIDATES", "msTable");
                        renderScoreTable($conn, $contestants['mr'], $criteria, $judges, "🤴 MR CANDIDATES", "mrTable");
                    } else {
                        renderScoreTable($conn, $contestants['solo'], $criteria, $judges, "🎯 CONTESTANTS", "soloTable");
                    }

                    echo "</div>";
                endif;
                ?>
            </div>
        </main>

        <?php include("./includes/copy.php"); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>

<!-- Export JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".exportBtn").forEach(btn => {
        btn.addEventListener("click", async () => {
            const tableId = btn.dataset.table;
            const tableElement = document.getElementById(tableId);
            if (!tableElement) return;

            const clone = tableElement.cloneNode(true);
            clone.style.background = "#fff";
            document.body.appendChild(clone);

            const canvas = await html2canvas(clone, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
            const imgData = canvas.toDataURL("image/png");

            Swal.fire({
                title: "Export Format",
                text: "Choose how to export this report:",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "📄 PDF",
                denyButtonText: "🖼️ Image (PNG)",
                confirmButtonColor: "#3085d6",
                denyButtonColor: "#28a745",
            }).then(result => {
                if (result.isConfirmed) {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF("landscape", "pt", "a4");
                    const imgWidth = pdf.internal.pageSize.getWidth();
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;
                    pdf.addImage(imgData, "PNG", 0, 20, imgWidth, imgHeight);
                    pdf.save(tableId + "_scores_report.pdf");
                } else if (result.isDenied) {
                    const a = document.createElement("a");
                    a.href = imgData;
                    a.download = tableId + "_scores_report.png";
                    a.click();
                }
            });

            document.body.removeChild(clone);
        });
    });
});
</script>
