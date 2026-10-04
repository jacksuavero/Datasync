<?php 
include('./includes/header.php'); 
require '../config/dbcon.php';
include('../includes/log_helper.php');


echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
/* ============================================================
   ✅ AUTO UPDATE EVENT STATUSES IN DATABASE (every page load)
   ============================================================ */
$update_sql = "
    UPDATE events
    SET status = CASE
        WHEN CURDATE() < start_date THEN 'Upcoming'
        WHEN CURDATE() BETWEEN start_date AND end_date THEN 'Ongoing'
        WHEN CURDATE() > end_date THEN 'Ended'
    END
";
if (!$conn->query($update_sql)) {
    die('❌ Status auto-update failed: ' . $conn->error);
}

/* ============================================================
   ✅ CREATE NEW EVENT
   ============================================================ */
if (isset($_POST['save_event'])) {
    $event_name = trim($_POST['event_name']);
    $event_category = trim($_POST['event_category']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $venue = trim($_POST['venue']);
    $num_judges = intval($_POST['num_judges']);
    $contestant_type = $_POST['contestant_type'];
    $max_contestants = $_POST['hidden_max_contestants'] ?? $_POST['max_contestants'] ?? 1;

    logAction($conn, $_SESSION['user_id'], 'Created Event', "Event: $event_name");

    $created_at = date('Y-m-d H:i:s');
    $created_by = $_SESSION['user_id']; // track which admin created it

    /* ✅ Upload Files */
    $target_dir = './uploads/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $logo = '';
    if (!empty($_FILES['logo']['name'])) {
        $logo_name = time() . '_' . basename($_FILES['logo']['name']);
        $logo_path = $target_dir . $logo_name;
        move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
        $logo = $logo_path;
    }

    $loading_screen = '';
    if (!empty($_FILES['loading_screen']['name'])) {
        $load_name = time() . '_' . basename($_FILES['loading_screen']['name']);
        $load_path = $target_dir . $load_name;
        move_uploaded_file($_FILES['loading_screen']['tmp_name'], $load_path);
        $loading_screen = $load_path;
    }

    /* ✅ Determine status based on date */
    $today = date('Y-m-d');
    if ($today < $start_date) {
        $status = 'Upcoming';
    } elseif ($today >= $start_date && $today <= $end_date) {
        $status = 'Ongoing';
    } else {
        $status = 'Ended';
    }

    /* ✅ Insert into database */
    $sql = "INSERT INTO `events` (
        created_by, event_name, event_category, start_date, end_date, venue,
        num_judges, contestant_type, max_contestants, status,
        logo, loading_screen, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('SQL Prepare Error: ' . $conn->error);
    }

    $stmt->bind_param(
        "issssssssssss",
        $created_by,
        $event_name,
        $event_category,
        $start_date,
        $end_date,
        $venue,
        $num_judges,
        $contestant_type,
        $max_contestants,
        $status,
        $logo,
        $loading_screen,
        $created_at
    );

  if ($stmt->execute()) {
        // ✅ SweetAlert Success
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Event Created!',
                    text: 'Status: $status',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'add_contestant.php';
                });
            };
        </script>";
    } else {
        $error = addslashes($stmt->error);
        // ✅ SweetAlert Error
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Error creating event: $error',
                    confirmButtonText: 'OK'
                });
            };
        </script>";
    }


}
?>


<!-- Your original HTML form and JS remain unchanged below -->

<div class="wrapper">
    <?php include('./includes/sidebar.php') ?>
    <div class="main">
        <?php include('./includes/nav.php') ?>

        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3>Create Event</h3>
                    </div>
                </div>

                <div class="card shadow-sm p-4">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Event Name</label>
                                <input type="text" name="event_name" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Event Category</label>
                                <select name="event_category" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="Main Event">Main Event</option>
                                    <option value="Sub Event">Sub Event</option>
                                </select>
                            </div>

                            <div class="col-md-4">
    <label class="form-label">Start Date</label>
    <input type="date" name="start_date" class="form-control" id="start_date" required>
</div>

<div class="col-md-4">
    <label class="form-label">End Date</label>
    <input type="date" name="end_date" class="form-control" id="end_date" required>
</div>
                            <div class="col-md-4">
                                <label class="form-label">Venue</label>
                                <input type="text" name="venue" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Number of Judges</label>
                                <input type="number" name="num_judges" class="form-control" required min="1" max="50">

                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Contestant Type</label>
                                <select id="contestant_type" name="contestant_type" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="Solo">Solo</option>
                                    <option value="Group">Group</option>
                                    <option value="Mr. and Miss">Mr. and Miss</option>
                                    <option value="Mr.">Mr.</option>
                                    <option value="Ms.">Ms.</option>
                                    <option value="Ms. Gay">Ms. Gay</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Maximum Contestants</label>
                              <input type="number" id="max_contestants" name="max_contestants" class="form-control" required min="1" max="50">

                                <!-- ✅ Hidden field to always send value -->
                                <input type="hidden" id="hidden_max_contestants" name="hidden_max_contestants" value="">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Event Logo</label>
                                <input type="file" name="logo" class="form-control" accept="image/*" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Loading Screen</label>
                                <input type="file" name="loading_screen" class="form-control" accept="image/*,video/*" required>
                            </div>

                            <div class="col-12 text-end mt-3">
                                <button type="submit" name="save_event" class="btn btn-primary px-4">Create Event</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </main>

        <?php include("./includes/copy.php") ?>
    </div>
</div>

<?php include('./includes/footer.php') ?>
<!-- ✅ JavaScript Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contestantType = document.getElementById('contestant_type');
    const maxContestants = document.getElementById('max_contestants');
    const hiddenMaxContestants = document.getElementById('hidden_max_contestants');

    contestantType.addEventListener('change', function() {
        const type = this.value;

        if (type === 'Solo' || type === 'Mr.' || type === 'Ms.' || type === 'Ms. Gay') {
            maxContestants.value = 1;
            hiddenMaxContestants.value = 1;
            maxContestants.disabled = true;
        } else if (type === 'Mr. and Miss') {
            maxContestants.value = 2;
            hiddenMaxContestants.value = 2;
            maxContestants.disabled = true;
        } else {
            maxContestants.disabled = false;
            maxContestants.value = '';
            hiddenMaxContestants.value = '';
        }
    });

    // Keep hidden field updated when manually typing
    maxContestants.addEventListener('input', function() {
        hiddenMaxContestants.value = this.value;
    });
});

 const today = new Date().toISOString().split('T')[0];

    // Set min attribute for start and end date inputs
    document.getElementById('start_date').setAttribute('min', today);
    document.getElementById('end_date').setAttribute('min', today);
</script>