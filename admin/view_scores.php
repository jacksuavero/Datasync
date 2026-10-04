<?php  
include('./includes/header.php'); 
?>

<div class="wrapper">
  <?php include('./includes/sidebar.php'); ?>
  <div class="main">
    <?php include('./includes/nav.php'); ?>

    <main class="content">
      <div class="container-fluid p-4">
        <h3>View Candidates’ Scores</h3>
        <hr>

        <?php
        $event_id = $_GET['event_id'] ?? null;
        if (!$event_id): ?>
          <div class="card shadow-sm p-4">
            <h5>Select Event</h5>
            <form method="GET">
              <div class="mb-3">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-select" required>
                  <option value="">Select Event</option>
                  <?php
                  $user_id = $_SESSION['user_id'];
                  $role = $_SESSION['role'];
                  $query = ($role === 'superadmin') 
                      ? "SELECT * FROM events ORDER BY event_name ASC"
                      : "SELECT * FROM events WHERE created_by='$user_id' ORDER BY event_name ASC";
                  $events = $conn->query($query);
                  while ($e = $events->fetch_assoc()) {
                    echo '<option value="'.$e['id'].'">'.htmlspecialchars($e['event_name']).'</option>';
                  }
                  ?>
                </select>
              </div>
              <button type="submit" class="btn btn-primary">View Scores</button>
            </form>
          </div>

        <?php else: 
          $event = $conn->query("SELECT * FROM events WHERE id='$event_id'")->fetch_assoc();
          $is_paired = $conn->query("SELECT COUNT(*) AS c FROM pairs WHERE event_id='$event_id'")->fetch_assoc()['c'] > 0;

          $criteria = $conn->query("SELECT * FROM criteria WHERE event_id='$event_id'");
          $criteria_arr = $criteria->fetch_all(MYSQLI_ASSOC);

          function calcScore($conn, $cid, $criteria_arr) {
            $total = 0;
            foreach ($criteria_arr as $c) {
              $avg = $conn->query("
                SELECT AVG(score) AS a FROM scores 
                WHERE contestant_id='$cid' 
                AND criteria_id='{$c['id']}'
              ")->fetch_assoc()['a'] ?? 0;
              $total += $avg * ($c['percentage']/100);
            }
            return $total;
          }
        ?>

        <div class="card shadow-sm p-4">
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5><?php echo htmlspecialchars($event['event_name']); ?></h5>
            <a href="print_scores_summary.php?event_id=<?php echo $event_id; ?>" target="_blank" class="btn btn-success btn-sm mt-2 mt-md-0">🖨️ Print</a>
          </div>

          <?php if ($is_paired): 
            $pairs = $conn->query("
              SELECT 
                p.*, 
                mr.id AS mr_id, mr.name AS mr_name, mr.number AS mr_number, mr.is_eliminated AS mr_elim, mr.is_top_finalist AS mr_top,
                ms.id AS ms_id, ms.name AS ms_name, ms.number AS ms_number, ms.is_eliminated AS ms_elim, ms.is_top_finalist AS ms_top
              FROM pairs p
              JOIN contestants mr ON p.mr_id = mr.id
              JOIN contestants ms ON p.ms_id = ms.id
              WHERE p.event_id='$event_id'
            ");

            $mr = [];
            $ms = [];
            while ($p = $pairs->fetch_assoc()) {
              $mr[]=[
                'id'=>$p['mr_id'],'name'=>$p['mr_name'],'number'=>$p['mr_number'],
                'final'=>calcScore($conn,$p['mr_id'],$criteria_arr),
                'elim'=>$p['mr_elim'],'top'=>$p['mr_top']
              ];
              $ms[]=[
                'id'=>$p['ms_id'],'name'=>$p['ms_name'],'number'=>$p['ms_number'],
                'final'=>calcScore($conn,$p['ms_id'],$criteria_arr),
                'elim'=>$p['ms_elim'],'top'=>$p['ms_top']
              ];
            }
            usort($mr, fn($a,$b)=>$b['final']<=>$a['final']);
            usort($ms, fn($a,$b)=>$b['final']<=>$a['final']);
          ?>

          <!-- MISS SECTION -->
          <h4 class="text-danger text-center mb-3">👑 Miss Candidates</h4>
          <div class="table-responsive mb-5">
            <table class="table table-bordered table-hover text-center align-middle" id="missTable">
              <thead class="table-dark">
                <tr>
                  <th>Rank</th><th>No.</th><th>Name</th>
                  <?php foreach ($criteria_arr as $c): ?>
                    <th><?= htmlspecialchars($c['name']) ?> (<?= $c['percentage'] ?>%)</th>
                  <?php endforeach; ?>
                  <th>Total / Avg</th><th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $rank=1; foreach($ms as $r): ?>
                  <tr data-id="<?php echo $r['id']; ?>" class="<?php echo $r['elim']?'table-danger opacity-75':($r['top']?'table-success':''); ?>">
                    <td><?= $rank++ ?></td>
                    <td><?= $r['number'] ?></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <?php foreach($criteria_arr as $c): 
                      $avg = $conn->query("SELECT AVG(score) AS a FROM scores WHERE contestant_id='{$r['id']}' AND criteria_id='{$c['id']}'")->fetch_assoc()['a'] ?? 0;
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

          <!-- MR SECTION -->
          <h4 class="text-primary text-center mb-3">🤴 Mr Candidates</h4>
          <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle" id="mrTable">
              <thead class="table-dark">
                <tr>
                  <th>Rank</th><th>No.</th><th>Name</th>
                  <?php foreach ($criteria_arr as $c): ?>
                    <th><?= htmlspecialchars($c['name']) ?> (<?= $c['percentage'] ?>%)</th>
                  <?php endforeach; ?>
                  <th>Total / Avg</th><th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $rank=1; foreach($mr as $r): ?>
                  <tr data-id="<?php echo $r['id']; ?>" class="<?php echo $r['elim']?'table-danger opacity-75':($r['top']?'table-success':''); ?>">
                    <td><?= $rank++ ?></td>
                    <td><?= $r['number'] ?></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <?php foreach($criteria_arr as $c): 
                      $avg = $conn->query("SELECT AVG(score) AS a FROM scores WHERE contestant_id='{$r['id']}' AND criteria_id='{$c['id']}'")->fetch_assoc()['a'] ?? 0;
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
            // SOLO EVENTS
            $cons = $conn->query("SELECT * FROM contestants WHERE event_id='$event_id'");
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
            <table class="table table-bordered table-hover text-center align-middle" id="soloTable">
              <thead class="table-dark">
                <tr>
                  <th>Rank</th><th>No.</th><th>Name</th>
                  <?php foreach ($criteria_arr as $c): ?>
                    <th><?= htmlspecialchars($c['name']) ?> (<?= $c['percentage'] ?>%)</th>
                  <?php endforeach; ?>
                  <th>Total / Avg</th><th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $rank=1; foreach($res as $r): ?>
                  <tr data-id="<?php echo $r['id']; ?>" class="<?php echo $r['elim']?'table-danger opacity-75':($r['top']?'table-success':''); ?>">
                    <td><?= $rank++ ?></td>
                    <td><?= $r['number'] ?></td>
                    <td><?= htmlspecialchars($r['name']); ?></td>
                    <?php foreach($criteria_arr as $c): 
                      $avg = $conn->query("SELECT AVG(score) AS a FROM scores WHERE contestant_id='{$r['id']}' AND criteria_id='{$c['id']}'")->fetch_assoc()['a'] ?? 0;
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
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  // Send update to backend
  function updateContestant(id, action) {
    return fetch("update_contestant_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ id, action })
    }).then(res => res.text());
  }

  // Handle Elimination with confirmation
  function handleEliminate(id) {
    Swal.fire({
      title: "Are you sure?",
      text: "You are about to eliminate this contestant.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, eliminate",
    }).then(result => {
      if (result.isConfirmed) {
        updateContestant(id, "eliminate")
          .then(res => {
            if (res.trim() === "OK") {
              Swal.fire({
                icon: "info",
                title: "Eliminated",
                timer: 1000,
                showConfirmButton: false
              }).then(() => location.reload()); // refresh to reflect elimination
            } else {
              Swal.fire({ icon: "error", title: "Update failed", text: res });
            }
          })
          .catch(() => Swal.fire({ icon: "error", title: "Connection error" }));
      }
    });
  }

  // Handle Add to Top / Remove Top
  function handleToggleTop(id, tr) {
    updateContestant(id, "toggleTop")
      .then(res => {
        if (res.trim() === "OK") {
          const isCurrentlyTop = tr.classList.contains("table-success");

          if (!isCurrentlyTop) {
            // Added to Top
            Swal.fire({
              icon: "success",
              title: "Added to Top",
              timer: 800,
              showConfirmButton: false
            }).then(() => location.reload()); // refresh to update view
          } else {
            // Removed from Top
            Swal.fire({
              icon: "warning",
              title: "Removed from Top",
              timer: 800,
              showConfirmButton: false
            }).then(() => location.reload()); // refresh to restore default buttons
          }
        } else {
          Swal.fire({ icon: "error", title: "Update failed", text: res });
        }
      })
      .catch(() => Swal.fire({ icon: "error", title: "Connection error" }));
  }

  // Listen for button clicks
  document.addEventListener("click", e => {
    const tr = e.target.closest("tr");
    if (!tr) return;
    const id = tr.dataset.id;

    if (e.target.classList.contains("toggleElim")) {
      handleEliminate(id);
    } else if (e.target.classList.contains("toggleTop")) {
      handleToggleTop(id, tr);
    }
  });
});







document.addEventListener("DOMContentLoaded", () => {
  const eventId = new URLSearchParams(window.location.search).get("event_id");
  if (!eventId) return;

  let lastCheck = null;

  // Update contestant status (Elim / Top)
  function updateContestant(id, action) {
    return fetch("update_contestant_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ id, action })
    }).then(res => res.text());
  }

  // Event delegation for buttons
  document.body.addEventListener("click", e => {
    const tr = e.target.closest("tr");
    if (!tr) return;
    const id = tr.dataset.id;
    if (e.target.classList.contains("toggleElim")) {
      Swal.fire({
        title:"Are you sure?", text:"You are about to eliminate this contestant.", icon:"warning",
        showCancelButton:true, confirmButtonColor:"#d33", cancelButtonColor:"#3085d6", confirmButtonText:"Yes, eliminate"
      }).then(result=>{
        if(result.isConfirmed){
          updateContestant(id,"eliminate").then(res=>{
            if(res.trim()==="OK") Swal.fire({icon:"info",title:"Eliminated",timer:800,showConfirmButton:false});
          });
        }
      });
    } else if (e.target.classList.contains("toggleTop")) {
      updateContestant(id,"toggleTop").then(res=>{
        if(res.trim()==="OK") Swal.fire({icon:"success",title:"Updated Top",timer:800,showConfirmButton:false});
      });
    }
  });

    // ✅ Auto-refresh the table when new scores are added
  async function checkForUpdates(force = false) {
    try {
      const res = await fetch(`check_new_scores.php?event_id=${eventId}`);
      const data = await res.json();
      const latest = data.latest_time;

      // First load or new update
      if (force || (latest && latest !== lastCheck)) {
        const tableRes = await fetch(`fetch_scores_table.php?event_id=${eventId}`);
        const html = await tableRes.text();

        const container = document.querySelector(".card.shadow-sm.p-4");
        if (container) container.innerHTML = html;

        lastCheck = latest;
      }
    } catch (err) {
      console.error("Auto-refresh error:", err);
    }
  }

  // ✅ Run once immediately (for first criteria issue)
  checkForUpdates(true);

  // ✅ Then check every 3 seconds
  setInterval(checkForUpdates, 3000);
});


</script>


<?php include('./includes/footer.php'); ?>
