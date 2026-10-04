<?php 
include('./includes/header.php'); 

$judge_id = $_SESSION['user_id'];
$event_id = $_GET['event_id'] ?? 0;

// Fetch event info
$event = $conn->query("SELECT event_name FROM events WHERE id = '$event_id'")->fetch_assoc();
?>



    <div class="main">
        <?php include('./includes/nav.php'); ?>

        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-2">
                    <div class="col-auto">
                        <h3>Scoring - <?php echo htmlspecialchars($event['event_name']); ?></h3>
                    </div>
                </div>

                <form method="POST" action="save_scores.php">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    <input type="hidden" name="judge_id" value="<?php echo $judge_id; ?>">

                    <div class="card shadow-sm p-4">
                        <h5>Contestants & Criteria</h5>
                        <hr>

                        <?php
                        $contestants = $conn->query("SELECT * FROM contestants WHERE event_id = '$event_id' ORDER BY CAST (number AS UNSIGNED) ASC");
                        $criteria = $conn->query("SELECT * FROM criteria WHERE event_id = '$event_id'");

                        if ($contestants->num_rows > 0 && $criteria->num_rows > 0) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-bordered align-middle text-center">';
                            echo '<thead><tr><th>Contestant</th>';

                            while ($c = $criteria->fetch_assoc()) {
                                echo '<th>' . htmlspecialchars($c['criteria_name']) . '<br><small>(' . $c['percentage'] . '%)</small></th>';
                                $criteriaList[] = $c;
                            }
                            echo '</tr></thead><tbody>';

                            // Reset criteria query
                            $criteria = $conn->query("SELECT * FROM criteria WHERE event_id = '$event_id'");

                            while ($con = $contestants->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($con['name']) . '</td>';

                                $criteria->data_seek(0);
                                while ($c = $criteria->fetch_assoc()) {
                                    echo '<td><input type="number" step="0.01" min="0" max="100" 
                                                name="scores['.$con['id'].']['.$c['id'].']" 
                                                class="form-control text-center" required></td>';
                                }

                                echo '</tr>';
                            }

                            echo '</tbody></table></div>';
                        } else {
                            echo '<p class="text-muted">No contestants or criteria found for this event.</p>';
                        }
                        ?>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-success px-4">Save Scores</button>
                    </div>
                </form>

            </div>
        </main>

        <?php include("./includes/copy.php"); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>
