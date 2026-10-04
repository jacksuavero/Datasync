<?php
// view_contestants.php
include('./includes/header.php');

?>
<!-- Load SweetAlert immediately so any PHP-echoed <script> that calls Swal works -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require '../config/dbcon.php';
include('../includes/log_helper.php');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'admin';

/* -------------------------
   Handle Update (Edit)
   ------------------------- */
if (isset($_POST['update_contestant'])) {
    $cid = intval($_POST['cid']);
    $name = trim($_POST['name']);
    $number = isset($_POST['number']) && $_POST['number'] !== '' ? intval($_POST['number']) : null;
    $old_image = $_POST['old_image'] ?? '';

    // validate name: letters and spaces only
    if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
        echo "<script>
            Swal.fire('Invalid Name','Name must contain letters and spaces only.','error').then(()=>history.back());
        </script>";
        exit;
    }

    // Ownership check
    $chk = $conn->prepare("SELECT c.event_id, e.created_by FROM contestants c JOIN events e ON e.id = c.event_id WHERE c.id = ? LIMIT 1");
    $chk->bind_param("i", $cid);
    $chk->execute();
    $info = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$info || $info['created_by'] != $user_id) {
        echo "<script>Swal.fire('Unauthorized','You cannot edit this contestant.','error').then(()=>history.back());</script>";
        exit;
    }

    // Handle image upload replacement
    $image_path = $old_image;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../uploads/contestants/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $safe = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '', basename($_FILES['image']['name']));
        $image_path = $target_dir . $safe;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            // upload failed
            echo "<script>Swal.fire('Error','Failed to upload image.','error').then(()=>history.back());</script>";
            exit;
        }
    }

    // Update
    $up = $conn->prepare("UPDATE contestants SET name = ?, number = ?, image = ? WHERE id = ?");
    $up->bind_param("sisi", $name, $number, $image_path, $cid);
    $ok = $up->execute();
    $up->close();

    if ($ok) {
        logAction($conn, $user_id, "Updated Contestant", "Updated contestant: $name (ID: $cid)");
        // Success alert then redirect to same event
        $event_id_redirect = intval($info['event_id']);
        echo "<script>
            Swal.fire('Updated','Contestant updated successfully.','success')
            .then(()=>{ window.location.href = 'view_contestants.php?event_id={$event_id_redirect}'; });
        </script>";
        exit;
    } else {
        echo "<script>Swal.fire('Error','Failed to update contestant.','error').then(()=>history.back());</script>";
        exit;
    }
}

/* -------------------------
   Handle Delete
   ------------------------- */
if (isset($_POST['delete_contestant'])) {
    $cid = intval($_POST['delete_contestant']);

    // verify ownership
    $chk = $conn->prepare("SELECT c.event_id, c.name, e.created_by FROM contestants c JOIN events e ON e.id = c.event_id WHERE c.id = ? LIMIT 1");
    $chk->bind_param("i", $cid);
    $chk->execute();
    $info = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$info || $info['created_by'] != $user_id) {
        echo "<script>Swal.fire('Unauthorized','You cannot delete this contestant.','error').then(()=>history.back());</script>";
        exit;
    }

    $del = $conn->prepare("DELETE FROM contestants WHERE id = ?");
    $del->bind_param("i", $cid);
    $ok = $del->execute();
    $del->close();

    if ($ok) {
        logAction($conn, $user_id, "Deleted Contestant", "Deleted contestant: {$info['name']} (ID: $cid)");
        $event_id_redirect = intval($info['event_id']);
        echo "<script>
            Swal.fire('Deleted','Contestant deleted.','success')
            .then(()=>{ window.location.href = 'view_contestants.php?event_id={$event_id_redirect}'; });
        </script>";
        exit;
    } else {
        echo "<script>Swal.fire('Error','Failed to delete contestant.','error').then(()=>history.back());</script>";
        exit;
    }
}

/* -------------------------
   Fetch events for dropdown (Upcoming & Ongoing)
   ------------------------- */
$ev_sql = ($role === 'superadmin')
    ? "SELECT id,event_name,contestant_type,status FROM events WHERE status IN ('Upcoming','Ongoing') ORDER BY event_name ASC"
    : "SELECT id,event_name,contestant_type,status FROM events WHERE created_by = ? AND status IN ('Upcoming','Ongoing') ORDER BY event_name ASC";

if ($role === 'superadmin') {
    $ev_stmt = $conn->prepare($ev_sql);
} else {
    $ev_stmt = $conn->prepare($ev_sql);
    $ev_stmt->bind_param("i", $user_id);
}
$ev_stmt->execute();
$events = $ev_stmt->get_result();
$ev_stmt->close();
?>

<style>
/* Sky-blue header, white rows */
.table-blue thead { background: #87CEFA !important; color: #003366 !important; }
.table-blue tbody tr { background: #ffffff !important; }
.table-blue tbody tr:hover { background: #dff2ff !important; }
.table-blue th, .table-blue td { vertical-align: middle; }
.img-thumb { cursor: pointer; }
</style>

<div class="wrapper">
  <?php include('./includes/sidebar.php'); ?>
  <div class="main">
    <?php include('./includes/nav.php'); ?>

    <main class="content">
      <div class="container-fluid p-4">
        <h3 class="mb-4"><?= ($role === 'superadmin') ? 'All Contestants' : 'Your Contestants'; ?></h3>

        <div class="card shadow-sm mb-3">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Select Event</h5>
          </div>
          <div class="card-body">
            <form method="GET">
              <div class="row g-2 align-items-center">
                <div class="col-md-10">
                  <select name="event_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Event --</option>
                    <?php while ($ev = $events->fetch_assoc()): ?>
                      <option value="<?= $ev['id'] ?>"
                        <?= (isset($_GET['event_id']) && intval($_GET['event_id']) === intval($ev['id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ev['event_name']) ?> (<?= htmlspecialchars($ev['status']) ?>)
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col-md-2 text-end">
                  <a href="view_events.php" class="btn btn-outline-secondary btn-sm">Back to Events</a>
                </div>
              </div>
            </form>
          </div>
        </div>

<?php
if (isset($_GET['event_id']) && intval($_GET['event_id']) > 0):
    $event_id = intval($_GET['event_id']);

    // Verify event ownership (or superadmin)
    $chk_sql = ($role === 'superadmin')
        ? "SELECT * FROM events WHERE id = ? AND status IN ('Upcoming','Ongoing') LIMIT 1"
        : "SELECT * FROM events WHERE id = ? AND created_by = ? AND status IN ('Upcoming','Ongoing') LIMIT 1";

    $chk = $conn->prepare($chk_sql);
    if ($role === 'superadmin') {
        $chk->bind_param("i", $event_id);
    } else {
        $chk->bind_param("ii", $event_id, $user_id);
    }
    $chk->execute();
    $selected_event = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$selected_event) {
        echo '<div class="alert alert-warning">Selected event not found or you do not have permission to view it.</div>';
    } else {

        // Determine paired by checking pairs table
        $pq = $conn->prepare("SELECT COUNT(*) AS c FROM pairs WHERE event_id = ?");
        $pq->bind_param("i", $event_id);
        $pq->execute();
        $is_paired = ($pq->get_result()->fetch_assoc()['c'] > 0);
        $pq->close();

        // Fetch criteria (kept in case you want to display)
        $criteria_q = $conn->prepare("SELECT * FROM criteria WHERE event_id = ? ORDER BY id ASC");
        $criteria_q->bind_param("i", $event_id);
        $criteria_q->execute();
        $criteria_arr = $criteria_q->get_result()->fetch_all(MYSQLI_ASSOC);
        $criteria_q->close();
        ?>

        <div class="card shadow-sm">
          <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($selected_event['event_name']) ?> — Contestants</h5>
            <div class="small text-white-50"><?= htmlspecialchars($selected_event['contestant_type'] ?? '') ?></div>
          </div>

          <div class="card-body table-responsive">

            <?php if ($is_paired):
                // PAIRED: build separate MR & MS lists from pairs table
                $pairs = $conn->prepare("
                    SELECT 
                      p.id AS pair_id,
                      mr.id AS mr_id, mr.name AS mr_name, mr.number AS mr_number, mr.image AS mr_image,
                      ms.id AS ms_id, ms.name AS ms_name, ms.number AS ms_number, ms.image AS ms_image
                    FROM pairs p
                    JOIN contestants mr ON mr.id = p.mr_id
                    JOIN contestants ms ON ms.id = p.ms_id
                    WHERE p.event_id = ?
                    ORDER BY mr.number ASC, ms.number ASC
                ");
                $pairs->bind_param("i", $event_id);
                $pairs->execute();
                $pairs_res = $pairs->get_result();
                $pairs->close();

                $mr_list = []; $ms_list = [];
                while ($r = $pairs_res->fetch_assoc()) {
                    $mr_list[] = $r;
                    $ms_list[] = $r;
                }
            ?>

            <!-- MISS TABLE -->
            <h5 class="text-danger text-center mb-3">👑 Miss Candidates</h5>
            <table class="table table-bordered table-hover align-middle text-center table-blue">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>ID</th>
                  <th>No.</th>
                  <th>Name</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($ms_list) === 0): ?>
                  <tr><td colspan="5" class="text-muted">No Miss candidates found.</td></tr>
                <?php else: foreach ($ms_list as $row): ?>
                  <tr>
                    <td>
                      <?php if (!empty($row['ms_image'])): ?>
                        <img src="<?= htmlspecialchars($row['ms_image']) ?>" width="50" height="50" class="rounded-circle border img-thumb"
                             onclick="openImageModal('<?= addslashes(htmlspecialchars($row['ms_image'])) ?>')" alt="thumb">
                      <?php else: ?>
                        <span class="text-muted">No Image</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $row['ms_id'] ?></td>
                    <td><?= htmlspecialchars($row['ms_number']) ?></td>
                    <td><?= htmlspecialchars($row['ms_name']) ?></td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-success btn-sm"
                          onclick="openEditModal(<?= $row['ms_id'] ?>, '<?= addslashes(htmlspecialchars($row['ms_name'])) ?>', '<?= $row['ms_number'] ?>', '<?= addslashes(htmlspecialchars($row['ms_image'])) ?>')"
                          title="Edit"><i class="fas fa-edit"></i></button>

                        <form method="POST" onsubmit="return confirmDelete(this)" style="display:inline-block;">
                          <input type="hidden" name="delete_contestant" value="<?= $row['ms_id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>

            <!-- MR TABLE -->
            <h5 class="text-primary text-center mb-3 mt-4">🤵 Mr Candidates</h5>
            <table class="table table-bordered table-hover align-middle text-center table-blue">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>ID</th>
                  <th>No.</th>
                  <th>Name</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($mr_list) === 0): ?>
                  <tr><td colspan="5" class="text-muted">No Mr candidates found.</td></tr>
                <?php else: foreach ($mr_list as $row): ?>
                  <tr>
                    <td>
                      <?php if (!empty($row['mr_image'])): ?>
                        <img src="<?= htmlspecialchars($row['mr_image']) ?>" width="50" height="50" class="rounded-circle border img-thumb"
                             onclick="openImageModal('<?= addslashes(htmlspecialchars($row['mr_image'])) ?>')" alt="thumb">
                      <?php else: ?>
                        <span class="text-muted">No Image</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $row['mr_id'] ?></td>
                    <td><?= htmlspecialchars($row['mr_number']) ?></td>
                    <td><?= htmlspecialchars($row['mr_name']) ?></td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-success btn-sm"
                          onclick="openEditModal(<?= $row['mr_id'] ?>, '<?= addslashes(htmlspecialchars($row['mr_name'])) ?>', '<?= $row['mr_number'] ?>', '<?= addslashes(htmlspecialchars($row['mr_image'])) ?>')"
                          title="Edit"><i class="fas fa-edit"></i></button>

                        <form method="POST" onsubmit="return confirmDelete(this)" style="display:inline-block;">
                          <input type="hidden" name="delete_contestant" value="<?= $row['mr_id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>

            <?php else:
                // NON-PAIRED: list all contestants for the event
                $cq = $conn->prepare("SELECT * FROM contestants WHERE event_id = ? ORDER BY number ASC, id ASC");
                $cq->bind_param("i", $event_id);
                $cq->execute();
                $contestants = $cq->get_result();
                $cq->close();
            ?>

            <table class="table table-bordered table-hover align-middle text-center table-blue">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>ID</th>
                  <th>No.</th>
                  <th>Name</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($contestants->num_rows === 0): ?>
                  <tr><td colspan="5" class="text-muted">No contestants found for this event.</td></tr>
                <?php else: while ($c = $contestants->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <?php if (!empty($c['image'])): ?>
                        <img src="<?= htmlspecialchars($c['image']) ?>" width="50" height="50" class="rounded-circle border img-thumb"
                             onclick="openImageModal('<?= addslashes(htmlspecialchars($c['image'])) ?>')" alt="thumb">
                      <?php else: ?>
                        <span class="text-muted">No Image</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['number']) ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-success btn-sm"
                          onclick="openEditModal(<?= $c['id'] ?>, '<?= addslashes(htmlspecialchars($c['name'])) ?>', '<?= $c['number'] ?>', '<?= addslashes(htmlspecialchars($c['image'])) ?>')"
                          title="Edit"><i class="fas fa-edit"></i></button>

                        <form method="POST" onsubmit="return confirmDelete(this)" style="display:inline-block;">
                          <input type="hidden" name="delete_contestant" value="<?= $c['id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; endif; ?>
              </tbody>
            </table>

            <?php endif; ?>
          </div>
        </div>

    <?php
    } // end selected_event exists
endif; // end event_id check
?>

      </div>
    </main>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Edit Contestant</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="update_contestant" value="1">
        <input type="hidden" name="cid" id="edit_cid">
        <input type="hidden" name="old_image" id="edit_old_image">

        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" id="edit_name" class="form-control" required pattern="[A-Za-z\s]+" title="Letters and spaces only">
        </div>

        <div class="mb-3">
          <label class="form-label">Number</label>
          <input type="number" name="number" id="edit_number" class="form-control">
        </div>

        <div class="mb-3">
          <label class="form-label">Replace Image (optional)</label>
          <input type="file" name="image" accept="image/*" class="form-control">
          <div id="edit_preview" class="mt-2"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Save changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body text-center p-3">
        <img id="imageModalImg" src="" alt="Full image" style="max-width:100%; height:auto;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include('./includes/footer.php'); ?>

<script>
function confirmDelete(form) {
    event.preventDefault();
    Swal.fire({
        title: 'Delete this contestant?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33'
    }).then(result => {
        if (result.isConfirmed) form.submit();
    });
    return false;
}

function openEditModal(id, name, number, image) {
    document.getElementById('edit_cid').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_number').value = number;
    document.getElementById('edit_old_image').value = image || '';
    document.getElementById('edit_preview').innerHTML = image ? `<img src="${image}" width="120" class="rounded border">` : '';
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function openImageModal(src) {
    if (!src) return;
    document.getElementById('imageModalImg').src = src;
    var m = new bootstrap.Modal(document.getElementById('imageModal'));
    m.show();
}
</script>
