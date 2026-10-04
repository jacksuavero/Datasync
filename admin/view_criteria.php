<?php
include('./includes/header.php'); 
?>
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
   Handle Update All Criteria
   ------------------------- */
if (isset($_POST['update_all_criteria'])) {
    $names = $_POST['name'] ?? [];
    $percentages = $_POST['percentage'] ?? [];

    $total = 0;
    foreach ($percentages as $p) {
        $total += intval($p);
    }

    if ($total != 100) {
        echo "<script>Swal.fire('Error','Total percentage of all criteria must be exactly 100. Current total: $total','error').then(()=>history.back());</script>";
        exit;
    }

    foreach ($names as $id => $name) {
        $cid = intval($id);
        $name = trim($name);
        $percentage = intval($percentages[$id] ?? 0);

        $stmt = $conn->prepare("UPDATE criteria SET name=?, percentage=? WHERE id=?");
        $stmt->bind_param("sii", $name, $percentage, $cid);
        $stmt->execute();
        $stmt->close();

        logAction($conn, $user_id, "Updated Criterion", "Updated criterion: $name (ID:$cid)");
    }

    // Redirect back to same event page
    $first_id = intval(array_key_first($names));
    $stmt2 = $conn->prepare("SELECT event_id FROM criteria WHERE id=? LIMIT 1");
    $stmt2->bind_param("i",$first_id);
    $stmt2->execute();
    $event_id_redirect = $stmt2->get_result()->fetch_assoc()['event_id'] ?? 0;
    $stmt2->close();

    echo "<script>Swal.fire('Updated','All criteria updated successfully','success').then(()=>{ window.location.href='view_criteria.php?event_id={$event_id_redirect}'; });</script>";
    exit;
}

/* -------------------------
   Handle Delete Criterion
   ------------------------- */
if (isset($_POST['delete_criterion'])) {
    $cid = intval($_POST['delete_criterion']);

    $stmt = $conn->prepare("SELECT name, event_id FROM criteria WHERE id=?");
    $stmt->bind_param("i",$cid);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$info) {
        echo "<script>Swal.fire('Error','Criterion not found','error').then(()=>history.back());</script>";
        exit;
    }

    $stmt2 = $conn->prepare("DELETE FROM criteria WHERE id=?");
    $stmt2->bind_param("i",$cid);
    $ok = $stmt2->execute();
    $stmt2->close();

    if ($ok) {
        logAction($conn,$user_id,"Deleted Criterion","Deleted criterion: ".$info['name']." (ID:$cid)");

        // Redirect to same page with event_id
        $event_id_redirect = intval($info['event_id']);
        echo "<script>
            Swal.fire('Deleted','Criterion deleted','success')
            .then(()=>{ window.location.href = 'view_criteria.php?event_id={$event_id_redirect}'; });
        </script>";
        exit;
    } else {
        echo "<script>Swal.fire('Error','Failed to delete criterion','error').then(()=>history.back());</script>";
        exit;
    }
}

/* -------------------------
   Fetch events dropdown (Upcoming + Ongoing)
   ------------------------- */
$ev_sql = ($role==='superadmin')
    ? "SELECT id,event_name,status FROM events WHERE status IN ('Upcoming','Ongoing') ORDER BY event_name ASC"
    : "SELECT id,event_name,status FROM events WHERE created_by=? AND status IN ('Upcoming','Ongoing') ORDER BY event_name ASC";

if ($role==='superadmin') {
    $ev_stmt = $conn->prepare($ev_sql);
} else {
    $ev_stmt = $conn->prepare($ev_sql);
    $ev_stmt->bind_param("i",$user_id);
}
$ev_stmt->execute();
$events = $ev_stmt->get_result();
$ev_stmt->close();
?>

<div class="wrapper">
  <?php include('./includes/sidebar.php'); ?>
  <div class="main">
    <?php include('./includes/nav.php'); ?>
    <main class="content">
      <div class="container-fluid p-4">
        <h3 class="mb-4">Manage Criteria</h3>

        <!-- Event Selection Card -->
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
                      <option value="<?= $ev['id'] ?>" <?= (isset($_GET['event_id']) && intval($_GET['event_id'])===intval($ev['id']))?'selected':'' ?>>
                        <?= htmlspecialchars($ev['event_name']) ?> (<?= htmlspecialchars($ev['status']) ?>)
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>
            </form>
          </div>
        </div>

<?php
if (isset($_GET['event_id']) && intval($_GET['event_id'])>0):
    $event_id = intval($_GET['event_id']);

    // Verify event access
    $chk_sql = ($role==='superadmin')
        ? "SELECT * FROM events WHERE id=? AND status IN ('Upcoming','Ongoing') LIMIT 1"
        : "SELECT * FROM events WHERE id=? AND created_by=? AND status IN ('Upcoming','Ongoing') LIMIT 1";

    $chk = $conn->prepare($chk_sql);
    if ($role==='superadmin') $chk->bind_param("i",$event_id);
    else $chk->bind_param("ii",$event_id,$user_id);
    $chk->execute();
    $selected_event = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$selected_event) {
        echo '<div class="alert alert-warning">Selected event not found or you do not have permission.</div>';
    } else {
        $crit_q = $conn->prepare("SELECT * FROM criteria WHERE event_id=? ORDER BY id ASC");
        $crit_q->bind_param("i",$event_id);
        $crit_q->execute();
        $criteria = $crit_q->get_result();
        $crit_q->close();
?>
        <!-- Criteria Table Card (All-at-once Edit) -->
        <form method="POST">
          <input type="hidden" name="update_all_criteria" value="1">
          <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0 fw-bold"><?= htmlspecialchars($selected_event['event_name']) ?> — Edit Criteria</h5>
            </div>
            <div class="card-body table-responsive">
              <table class="table table-bordered table-hover align-middle text-center table-blue">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Percentage</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($criteria->num_rows===0): ?>
                  <tr><td colspan="4" class="text-muted">No criteria found.</td></tr>
                <?php else: while ($c = $criteria->fetch_assoc()): ?>
                  <tr>
                    <td><?= $c['id'] ?></td>
                    <td>
                      <input type="text" name="name[<?= $c['id'] ?>]" value="<?= htmlspecialchars($c['name']) ?>" class="form-control" required>
                    </td>
                    <td>
                      <input type="number" name="percentage[<?= $c['id'] ?>]" value="<?= $c['percentage'] ?>" min="1" max="100" class="form-control" required>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-2">
                        <form method="POST" onsubmit="return confirmDelete(this)" style="display:inline-block;">
                          <input type="hidden" name="delete_criterion" value="<?= $c['id'] ?>">
                          <button class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; endif; ?>
                </tbody>
              </table>
              <div class="mt-3 text-end">
                <button type="submit" class="btn btn-success">Save All Changes</button>
              </div>
            </div>
          </div>
        </form>

<?php
    }
endif;
?>

      </div>
    </main>
  </div>
</div>

<?php include('./includes/footer.php'); ?>

<script>
function confirmDelete(form){
    event.preventDefault();
    Swal.fire({
        title:'Delete this criterion?',
        text:"This action cannot be undone.",
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#d33'
    }).then((result)=>{
        if(result.isConfirmed) form.submit();
    });
    return false;
}
</script>

<style>
.table-blue thead { background: #87CEFA !important; color: #003366 !important; }
.table-blue tbody tr { background: #ffffff !important; }
.table-blue tbody tr:hover { background: #dff2ff !important; }
.table-blue th, .table-blue td { vertical-align: middle; }
</style>
