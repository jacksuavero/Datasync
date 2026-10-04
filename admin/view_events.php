<?php 
include('./includes/header.php'); 
require '../config/dbcon.php';
include('../includes/log_helper.php');


// ✅ Auto-update event status every page load
$update_sql = "
    UPDATE events 
    SET status = CASE
        WHEN CURDATE() < start_date THEN 'Upcoming'
        WHEN CURDATE() BETWEEN start_date AND end_date THEN 'Ongoing'
        WHEN CURDATE() > end_date THEN 'Ended'
    END
";
$conn->query($update_sql);

if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ✅ DELETE EVENT
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['delete_event']);

    // ✅ Get event name before deleting (for logs)
    $stmt_name = $conn->prepare("SELECT event_name FROM events WHERE id = ?");
    $stmt_name->bind_param("i", $event_id);
    $stmt_name->execute();
    $stmt_name->bind_result($event_name);
    $stmt_name->fetch();
    $stmt_name->close();

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();

    // ✅ Log deletion
    logAction($conn, $_SESSION['user_id'], 'Deleted Event', "Deleted event: $event_name (ID: $event_id)");

    echo "<script>
        Swal.fire('Deleted!', 'Event has been deleted successfully.', 'success')
        .then(() => location.reload());
    </script>";
}

// ✅ UPDATE EVENT
if (isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $event_name = $_POST['event_name'];
    $event_category = $_POST['event_category'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $venue = $_POST['venue'];
    $num_judges = $_POST['num_judges'];
    $contestant_type = $_POST['contestant_type'];
  $max_contestants = $_POST['max_contestants'] ?? $_POST['max_contestants_hidden'];



    $logo = $_POST['old_logo'];
    if (!empty($_FILES['logo']['name'])) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $logo_name = time() . '_' . basename($_FILES['logo']['name']);
        $logo = $target_dir . $logo_name;
        move_uploaded_file($_FILES['logo']['tmp_name'], $logo);
    }

    $stmt = $conn->prepare("
        UPDATE events 
        SET event_name=?, event_category=?, start_date=?, end_date=?, venue=?, 
            num_judges=?, contestant_type=?, max_contestants=?, logo=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssssssissi", 
        $event_name, $event_category, $start_date, $end_date, $venue,
        $num_judges, $contestant_type, $max_contestants, $logo, $event_id
    );
    $stmt->execute();

 logAction($conn, $_SESSION['user_id'], 'Updated Event', "Updated event: $event_name (ID: $event_id)");

    echo "<script>
        Swal.fire({
            title: 'Updated!',
            text: 'Event details were updated successfully.',
            icon: 'success',
            confirmButtonColor: '#198754'
        }).then(() => location.reload());
    </script>";
}

// ✅ START EVENT
if (isset($_POST['start_event'])) {
    $event_id = intval($_POST['start_event']);

    // Get current event info
    $stmt = $conn->prepare("SELECT event_name, status, is_started FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($event_name, $status, $is_started);
    $stmt->fetch();
    $stmt->close();

    // 🟥 If already ended
    if ($status === 'Ended') {
        echo "<script>
            Swal.fire('Error', 'This event has already ended and cannot be started again.', 'error');
        </script>";
    } 
    // 🟡 If already started
    elseif ($is_started == 1) {
        echo "<script>
            Swal.fire('Info', 'This event is already started.', 'info');
        </script>";
    } 
    // 🟢 Otherwise, start the event
    else {
        $update = $conn->prepare("UPDATE events SET is_started = 1, status = 'Ongoing' WHERE id = ?");
        $update->bind_param("i", $event_id);
        $update->execute();
        $update->close();

        logAction($conn, $_SESSION['user_id'], 'Started Event', "Started event: $event_name (ID: $event_id)");

        echo "<script>
            Swal.fire('Started!', 'The event \"$event_name\" has started.', 'success')
            .then(() => location.reload());
        </script>";
    }
}

?>

<div class="wrapper">
    <?php include('./includes/sidebar.php'); ?>
    <div class="main">
        <?php include('./includes/nav.php'); ?>

        <main class="content">
            <div class="container-fluid">
                <h3 class="mb-4"><?= ($role === 'superadmin') ? 'All Events' : 'Your Events'; ?></h3>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Event List</h5>
                    </div>

                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Logo</th>
                                    <th>Event ID</th>
                                    <th>Event Name</th>
                                    <th>Category</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Venue</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = ($role === 'superadmin')
                                ? "SELECT e.*, CONCAT(u.first_name,' ',u.last_name) AS created_by 
                                   FROM events e LEFT JOIN users u ON e.created_by=u.id ORDER BY e.id DESC"
                                : "SELECT e.*, CONCAT(u.first_name,' ',u.last_name) AS created_by 
                                   FROM events e LEFT JOIN users u ON e.created_by=u.id 
                                   WHERE e.created_by=? ORDER BY e.id DESC";

                            $stmt = $conn->prepare($sql);
                            if ($role !== 'superadmin') $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['logo'])): ?>
                                            <img src="<?= htmlspecialchars($row['logo']) ?>" width="50" height="50" class="rounded-circle border">
                                        <?php else: ?>
                                            <span class="text-muted">No logo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['event_name']) ?></td>
                                    <td><?= htmlspecialchars($row['event_category']) ?></td>
                                    <td><?= $row['start_date'] ?></td>
                                    <td><?= $row['end_date'] ?></td>
                                    <td><?= htmlspecialchars($row['venue']) ?></td>
                                    <td>
                                        <span class="badge bg-<?=
                                            ($row['status'] === 'Ongoing') ? 'success' :
                                            (($row['status'] === 'Upcoming') ? 'warning text-dark' : 'secondary')
                                        ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['created_by']) ?></td>
                                    <td>
    <div class="d-flex justify-content-center gap-2">
        <!-- ✏️ Edit -->
        <button class="btn btn-success btn-sm px-3" 
                onclick="confirmEdit(<?= $row['id'] ?>)" 
                title="Edit Event">
            <i class="fas fa-edit"></i>
        </button>

        <!-- 🗑 Delete -->
        <form method="POST" onsubmit="return confirmDelete(this)">
            <input type="hidden" name="delete_event" value="<?= $row['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm px-3" title="Delete Event">
                <i class="fas fa-trash-alt"></i>
            </button>
        </form>

        <!-- ▶ Start Event / ❌ Ended / 🟢 Already Started -->
<?php if ($role !== 'judge'): ?>
    <?php if ($row['status'] === 'Ended'): ?>
        <button class="btn btn-secondary btn-sm px-3" disabled title="Event Ended">
            <i class="fas fa-ban"></i>
        </button>
    <?php elseif ($row['is_started'] == 1): ?>
        <button class="btn btn-info btn-sm px-3" onclick="alreadyStarted()" title="Event Already Started">
            <i class="fas fa-info-circle"></i>
        </button>
    <?php else: ?>
        <form method="POST" onsubmit="return confirmStart(this)">
            <input type="hidden" name="start_event" value="<?= $row['id'] ?>">
            <button type="submit" class="btn btn-warning btn-sm px-3" title="Start Event">
                <i class="fas fa-play-circle"></i>
            </button>
        </form>
    <?php endif; ?>
<?php endif; ?>

    </div>
</td>

                                </tr>

                                <!-- 🧩 Edit Modal -->
                                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Edit Event</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="event_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="old_logo" value="<?= htmlspecialchars($row['logo']) ?>">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Event Name</label>
                                                            <input type="text" name="event_name" class="form-control" value="<?= htmlspecialchars($row['event_name']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Category</label>
                                                            <select name="event_category" class="form-select">
                                                                <option <?= ($row['event_category'] === 'Main Event') ? 'selected' : '' ?>>Main Event</option>
                                                                <option <?= ($row['event_category'] === 'Sub Event') ? 'selected' : '' ?>>Sub Event</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
    <label class="form-label">Start Date</label>
    <input type="date" name="start_date" class="form-control"
           value="<?= $row['start_date'] ?>"
           min="<?= date('Y-m-d') ?>">
</div>
                                                        <div class="col-md-6">
    <label class="form-label">End Date</label>
    <input type="date" name="end_date" class="form-control"
           value="<?= $row['end_date'] ?>"
           min="<?= date('Y-m-d') ?>">
</div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Venue</label>
                                                            <input type="text" name="venue" class="form-control" value="<?= htmlspecialchars($row['venue']) ?>">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Judges</label>
                                                            <input type="number" name="num_judges" class="form-control" value="<?= $row['num_judges'] ?>" min="1" max="50">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Max Contestants</label>
                                                            <input type="number" name="max_contestants" class="form-control maxC<?= $row['id'] ?>" 
                                                            value="<?= $row['max_contestants'] ?>" min="1" max="50">
                                                            <input type="hidden" name="max_contestants_hidden" value="<?= $row['max_contestants'] ?>">

                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Contestant Type</label>
                                                            <select name="contestant_type" class="form-select contestantType<?= $row['id'] ?>">

                                                                <option <?= ($row['contestant_type'] === 'Solo') ? 'selected' : '' ?>>Solo</option>
                                                                <option <?= ($row['contestant_type'] === 'Group') ? 'selected' : '' ?>>Group</option>
                                                                <option <?= ($row['contestant_type'] === 'Mr. and Miss') ? 'selected' : '' ?>>Mr. and Miss</option>
                                                                <option <?= ($row['contestant_type'] === 'Mr.') ? 'selected' : '' ?>>Mr.</option>
                                                                <option <?= ($row['contestant_type'] === 'Ms.') ? 'selected' : '' ?>>Ms.</option>
                                                                <option <?= ($row['contestant_type'] === 'Ms. Gay') ? 'selected' : '' ?>>Ms. Gay</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Event Logo</label>
                                                            <input type="file" name="logo" class="form-control" accept="image/*">
                                                            <?php if (!empty($row['logo'])): ?>
                                                                <img src="<?= htmlspecialchars($row['logo']) ?>" width="60" class="mt-2 rounded border">
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="update_event" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; else: ?>
                                <tr><td colspan="10" class="text-center text-muted">No events found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <?php include('./includes/copy.php'); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>

<!-- ✅ SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(form) {
    event.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: "This event will be permanently deleted.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
    return false;
}

function confirmEdit(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to edit this event?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, edit'
    }).then((result) => {
        if (result.isConfirmed) {
            var modal = new bootstrap.Modal(document.getElementById('editModal' + id));
            modal.show();
        }
    });
}

function confirmStart(form) {
    event.preventDefault();
    Swal.fire({
        title: 'Start Event?',
        text: "Are you sure you want to start this event now?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, start it'
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
    return false;
}

function alreadyStarted() {
    Swal.fire({
        icon: 'info',
        title: 'Already Started',
        text: 'This event has already been started.',
        confirmButtonColor: '#3085d6'
    });
}

document.addEventListener("DOMContentLoaded", function () {
    <?php 
    // Generate dynamic JS for every event modal
    $result->data_seek(0); 
    while ($row2 = $result->fetch_assoc()): 
    ?>
        const type<?= $row2['id'] ?> = document.querySelector(".contestantType<?= $row2['id'] ?>");
        const maxC<?= $row2['id'] ?> = document.querySelector(".maxC<?= $row2['id'] ?>");

        function updateMax<?= $row2['id'] ?>() {
            const val = type<?= $row2['id'] ?>.value;

            if (val === "Solo" || val === "Mr." || val === "Ms." || val === "Ms. Gay") {
                maxC<?= $row2['id'] ?>.value = 1;
                maxC<?= $row2['id'] ?>.disabled = true;
            }
            else if (val === "Mr. and Miss") {
                maxC<?= $row2['id'] ?>.value = 2;
                maxC<?= $row2['id'] ?>.disabled = true;
            }
            else {
                maxC<?= $row2['id'] ?>.disabled = false;
            }
        }

        // Apply when dropdown changes
        type<?= $row2['id'] ?>.addEventListener("change", updateMax<?= $row2['id'] ?>);

        // Apply on modal open
        updateMax<?= $row2['id'] ?>();
    <?php endwhile; ?>
});


</script>
