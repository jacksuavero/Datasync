<?php include('./includes/header.php'); 
include('../includes/log_helper.php');?>

<!-- Include Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="wrapper">
    <?php include('./includes/sidebar.php'); ?>
    <div class="main">
        <?php include('./includes/nav.php'); ?>

        <main class="content">
            <div class="container-fluid p-0">
                <h3>Assign Judges to Event</h3>

                <div class="card shadow-sm p-4 mt-3">
                    <form method="POST" action="save_event_judges.php" id="judgeForm">
                        <!-- Select Event -->
                        <div class="mb-3">
                            <label class="form-label">Select Event</label>
                            <select class="form-select" name="event_id" id="eventSelect" required>
                                <option value="">-- Select Event --</option>
                                <?php
                                session_start();
                                require('../config/dbcon.php');

                                if (!isset($_SESSION['user_id'])) {
                                    header("Location: ../index.php");
                                    exit();
                                }

                                $user_id = $_SESSION['user_id'];
                                $role = $_SESSION['role'];

                               if ($role === 'superadmin') {
    $res = $conn->query("
        SELECT id, event_name, contestant_type 
        FROM events 
        WHERE status IN ('Upcoming', 'Ongoing')
        ORDER BY created_at DESC
    ");
} else {
    $res = $conn->query("
        SELECT id, event_name, contestant_type 
        FROM events 
        WHERE created_by = '$user_id'
        AND status IN ('Upcoming', 'Ongoing')
        ORDER BY created_at DESC
    ");
}


                                while ($r = $res->fetch_assoc()) {
                                    echo "<option value='{$r['id']}'>" . htmlspecialchars($r['event_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Judges List -->
                        <div class="mb-3" id="judgeListWrapper" style="display: none;">
                            <label class="form-label">Select Judges</label>
                            <select class="form-select" name="judge_ids[]" id="judgeSelect" multiple required></select>
                        </div>

                        <button type="submit" class="btn btn-primary" id="assignBtn" style="display: none;">Assign Judges</button>
                    </form>
                </div>
            </div>
        </main>

        <?php include("./includes/copy.php"); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>

<!-- Include jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventSelect = document.getElementById('eventSelect');
    const judgeListWrapper = document.getElementById('judgeListWrapper');
    const judgeSelect = document.getElementById('judgeSelect');
    const assignBtn = document.getElementById('assignBtn');

    $(judgeSelect).select2({
        placeholder: "Select judges...",
        width: '100%'
    });

    eventSelect.addEventListener('change', function() {
        const eventId = this.value;
        if (!eventId) {
            judgeListWrapper.style.display = 'none';
            assignBtn.style.display = 'none';
            $(judgeSelect).empty();
            return;
        }

        fetch('fetch_judges.php?event_id=' + eventId)
            .then(response => response.json())
            .then(data => {
                $(judgeSelect).empty();
                if (data.length > 0) {
                    data.forEach(judge => {
                        const option = new Option(judge.name, judge.id, judge.assigned, judge.assigned);
                        $(judgeSelect).append(option);
                    });
                }
                $(judgeSelect).trigger('change');
                judgeListWrapper.style.display = 'block';
                assignBtn.style.display = 'block';
            });
    });
});
</script>
