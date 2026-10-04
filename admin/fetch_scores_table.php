<?php
include('../config/dbcon.php');

$event_id = $_GET['event_id'] ?? null;
if (!$event_id) exit('No event selected');

$event = $conn->query("SELECT * FROM events WHERE id='$event_id'")->fetch_assoc();
$is_paired = $conn->query("SELECT COUNT(*) AS c FROM pairs WHERE event_id='$event_id'")->fetch_assoc()['c'] > 0;
$criteria = $conn->query("SELECT * FROM criteria WHERE event_id='$event_id'");
$criteria_arr = $criteria->fetch_all(MYSQLI_ASSOC);

function calcScore($conn, $cid, $criteria_arr) {
    $total = 0;
    foreach ($criteria_arr as $c) {
        $avg = $conn->query("SELECT AVG(score) AS a FROM scores WHERE contestant_id='$cid' AND criteria_id='{$c['id']}'")->fetch_assoc()['a'] ?? 0;
        $total += $avg * ($c['percentage']/100);
    }
    return $total;
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
  <h5><?= htmlspecialchars($event['event_name']) ?></h5>
  <a href="print_scores_summary.php?event_id=<?= $event_id ?>" target="_blank" class="btn btn-success btn-sm mt-2 mt-md-0">🖨️ Print</a>
</div>

<?php if ($is_paired): 
  $pairs = $conn->query("
      SELECT p.*, 
             mr.id AS mr_id, mr.name AS mr_name, mr.number AS mr_number, mr.is_eliminated AS mr_elim, mr.is_top_finalist AS mr_top,
             ms.id AS ms_id, ms.name AS ms_name, ms.number AS ms_number, ms.is_eliminated AS ms_elim, ms.is_top_finalist AS ms_top
      FROM pairs p
      JOIN contestants mr ON p.mr_id = mr.id
      JOIN contestants ms ON p.ms_id = ms.id
      WHERE p.event_id='$event_id'
  ");
  $mr=[]; $ms=[];
  while($p=$pairs->fetch_assoc()){
      $mr[]=['id'=>$p['mr_id'],'name'=>$p['mr_name'],'number'=>$p['mr_number'],'final'=>calcScore($conn,$p['mr_id'],$criteria_arr),'elim'=>$p['mr_elim'],'top'=>$p['mr_top']];
      $ms[]=['id'=>$p['ms_id'],'name'=>$p['ms_name'],'number'=>$p['ms_number'],'final'=>calcScore($conn,$p['ms_id'],$criteria_arr),'elim'=>$p['ms_elim'],'top'=>$p['ms_top']];
  }
  usort($mr, fn($a,$b)=>$b['final']<=>$a['final']);
  usort($ms, fn($a,$b)=>$b['final']<=>$a['final']);
?>

<!-- 👑 MISS -->
<h4 class="text-danger text-center mb-3">👑 Miss Candidates</h4>
<div class="table-responsive mb-5">
<table class="table table-bordered table-hover text-center align-middle">
<thead class="table-dark">
<tr>
  <th>Rank</th><th>No.</th><th>Name</th>
  <?php foreach($criteria_arr as $c): ?>
    <th><?= htmlspecialchars($c['name']) ?> (<?= $c['percentage'] ?>%)</th>
  <?php endforeach; ?>
  <th>Total / Avg</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php $rank=1; foreach($ms as $r): ?>
<tr data-id="<?= $r['id'] ?>" class="<?= $r['elim']?'table-danger opacity-75':($r['top']?'table-success':'') ?>">
  <td><?= $rank++ ?></td>
  <td><?= $r['number'] ?></td>
  <td><?= htmlspecialchars($r['name']) ?></td>
  <?php foreach($criteria_arr as $c):
    $avg=$conn->query("SELECT AVG(score) AS a FROM scores WHERE contestant_id='{$r['id']}' AND criteria_id='{$c['id']}'")->fetch_assoc()['a']??0;
    echo "<td>".number_format($avg,2)."</td>";
  endforeach; ?>
  <td><strong><?= number_format($r['final'],2) ?></strong></td>
  <td>
    <?php if($r['elim']): ?>
      <button class="btn btn-secondary btn-sm" disabled>Eliminated</button>
    <?php elseif($r['top']): ?>
      <button class="btn btn-warning btn-sm toggleTop">Remove Top</button>
    <?php else: ?>
      <button class="btn btn-success btn-sm toggleTop">Add to Top</button>
      <button class="btn btn-outline-danger btn-sm toggleElim">Eliminate</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- 🤴 MR -->
<h4 class="text-primary text-center mb-3">🤴 Mr Candidates</h4>
<div class="table-responsive">
<table class="table table-bordered table-hover text-center align-middle">
<thead class="table-dark">
<tr>
  <th>Rank</th><th>No.</th><th>Name</th>
  <?php foreach($criteria_arr as $c): ?>
    <th><?= htmlspecialchars($c['name']) ?> (<?= $c['percentage'] ?>%)</th>
  <?php endforeach; ?>
  <th>Total / Avg</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php $rank=1; foreach($mr as $r): ?>
<tr data-id="<?= $r['id'] ?>" class="<?= $r['elim']?'table-danger opacity-75':($r['top']?'table-success':'') ?>">
  <td><?= $rank++ ?></td>
  <td><?= $r['number'] ?></td>
  <td><?= htmlspecialchars($r['name']) ?></td>
  <?php foreach($criteria_arr as $c):
    $avg=$conn->query("SELECT AVG(score) AS a FROM scores WHERE contestant_id='{$r['id']}' AND criteria_id='{$c['id']}'")->fetch_assoc()['a']??0;
    echo "<td>".number_format($avg,2)."</td>";
  endforeach; ?>
  <td><strong><?= number_format($r['final'],2) ?></strong></td>
  <td>
    <?php if($r['elim']): ?>
      <button class="btn btn-secondary btn-sm" disabled>Eliminated</button>
    <?php elseif($r['top']): ?>
      <button class="btn btn-warning btn-sm toggleTop">Remove Top</button>
    <?php else: ?>
      <button class="btn btn-success btn-sm toggleTop">Add to Top</button>
      <button class="btn btn-outline-danger btn-sm toggleElim">Eliminate</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php else: 
// SOLO EVENT
$cons=$conn->query("SELECT * FROM contestants WHERE event_id='$event_id'");
$res=[];
while($c=$cons->fetch_assoc()){
  $res[]=[
    'id'=>$c['id'],'name'=>$c['name'],'number'=>$c['number'],
    'final'=>calcScore($conn,$c['id'],$criteria_arr),
    'elim'=>$c['is_eliminated'],'top'=>$c['is_top_finalist']
  ];
}
usort($res, fn($a,$b)=>$b['final']<=>$a['final']);
?>

<div class="table-responsive">
<table class="table table-bordered table-hover text-center align-middle">
<thead class="table-dark">
<tr>
  <th>Rank</th><th>No.</th><th>Name</th>
  <?php foreach($criteria_arr as $c): ?>
    <th><?= htmlspecialchars($c['name']) ?> (<?= $c['percentage'] ?>%)</th>
  <?php endforeach; ?>
  <th>Total / Avg</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php $rank=1; foreach($res as $r): ?>
<tr data-id="<?= $r['id'] ?>" class="<?= $r['elim']?'table-danger opacity-75':($r['top']?'table-success':'') ?>">
  <td><?= $rank++ ?></td>
  <td><?= $r['number'] ?></td>
  <td><?= htmlspecialchars($r['name']) ?></td>
  <?php foreach($criteria_arr as $c):
    $avg=$conn->query("SELECT AVG(score) AS a FROM scores WHERE contestant_id='{$r['id']}' AND criteria_id='{$c['id']}'")->fetch_assoc()['a']??0;
    echo "<td>".number_format($avg,2)."</td>";
  endforeach; ?>
  <td><strong><?= number_format($r['final'],2) ?></strong></td>
  <td>
    <?php if($r['elim']): ?>
      <button class="btn btn-secondary btn-sm" disabled>Eliminated</button>
    <?php elseif($r['top']): ?>
      <button class="btn btn-warning btn-sm toggleTop">Remove Top</button>
    <?php else: ?>
      <button class="btn btn-success btn-sm toggleTop">Add to Top</button>
      <button class="btn btn-outline-danger btn-sm toggleElim">Eliminate</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php endif; ?>

<!-- ✅ Include SweetAlert + JS same as view_scores -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const eventId = "<?= $event_id ?>";

  // Update contestant status (Eliminate / Toggle Top)
  function updateContestant(id, action) {
    return fetch("update_contestant_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ id, action })
    }).then(res => res.text());
  }

  // Handle elimination
  document.body.addEventListener("click", e => {
    const tr = e.target.closest("tr");
    if (!tr) return;
    const id = tr.dataset.id;

    if (e.target.classList.contains("toggleElim")) {
      Swal.fire({
        title: "Are you sure?",
        text: "You are about to eliminate this contestant.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, eliminate"
      }).then(result => {
        if (result.isConfirmed) {
          updateContestant(id, "eliminate").then(res => {
            if (res.trim() === "OK") {
              Swal.fire({
                icon: "info",
                title: "Eliminated",
                timer: 800,
                showConfirmButton: false
              });
            }
          });
        }
      });
    }

    if (e.target.classList.contains("toggleTop")) {
      updateContestant(id, "toggleTop").then(res => {
        if (res.trim() === "OK") {
          const isTop = tr.classList.contains("table-success");
          Swal.fire({
            icon: isTop ? "warning" : "success",
            title: isTop ? "Removed from Top" : "Added to Top",
            timer: 800,
            showConfirmButton: false
          });
        }
      });
    }
  });
});
</script>

<?php echo ob_get_clean(); ?>
