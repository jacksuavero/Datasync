<?php
include('./includes/header.php');
include('../includes/log_helper.php');
require '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$judge_id = $_SESSION['user_id'] ?? null;
if (!$judge_id) {
    header("Location: ../index.php");
    exit();
}

$event_id = $_GET['event_id'] ?? null;
$criteria_id = $_GET['criteria_id'] ?? null;

// ✅ Check if event exists and judge is assigned
if ($event_id) {
    $stmt = $conn->prepare("
        SELECT e.event_name, e.status, e.is_started, e.loading_screen,
               ej.id AS assigned
        FROM events e
        LEFT JOIN event_judges ej ON e.id = ej.event_id AND ej.judge_id = ?
        WHERE e.id = ?
    ");
    $stmt->bind_param("ii", $judge_id, $event_id);
    $stmt->execute();
    $stmt->bind_result($event_name, $status, $is_started, $loading_screen, $assigned);
    $stmt->fetch();
    $stmt->close();

    // 🟥 Judge not assigned to this event
    if (empty($assigned)) {
        echo "
        <div style='display:flex;justify-content:center;align-items:center;height:100vh;background:#111;color:white;'>
            <div class='text-center'>
                <h2>🚫 Access Denied</h2>
                <p>You are not assigned to this event.</p>
            </div>
        </div>";
        include('./includes/footer.php');
        exit;
    }

    // 🟥 Event already ended
    if ($status === 'Ended') {
        echo "
        <div style='display:flex;justify-content:center;align-items:center;height:100vh;background:#111;color:white;'>
            <div class='text-center'>
                <h2>🚫 Event Ended</h2>
                <p>You can no longer access this event.</p>
            </div>
        </div>";
        include('./includes/footer.php');
        exit;
    }

// 🟡 Event not yet started
if ((int)$is_started === 0) {

    // Fix path: convert "./uploads/..." → "../admin/uploads/"
    $loading_screen = preg_replace('#^\./uploads/#', '../admin/uploads/', $loading_screen);

    $accentColor = "#f0f0f0"; // light grey/white for readability

    // Text style with lighter blur and square/frosted background
    $textStyle = "
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 600;
        font-size: 1.6rem;
        color: {$accentColor};
        background: rgba(40, 40, 40, 0.2); /* lighter semi-transparent */
        backdrop-filter: blur(12px); /* lighter blur */
        -webkit-backdrop-filter: blur(12px);
        padding: 40px 60px; /* wider padding to make it look like a square */
        border-radius: 20px;
        display: inline-block;
        text-align: center;
        max-width: 90vw;
        min-width: 300px;
        min-height: 120px; /* ensures a consistent square/rectangle shape */
    ";

    // Button style with lighter blur
    $buttonStyle = "
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 10;
        background: rgba(40, 40, 40, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: {$accentColor};
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        border: none;
        padding: 10px 16px;
        border-radius: 12px;
        cursor: pointer;
    ";

    if (!empty($loading_screen) && preg_match('/\.(mp4|webm|ogg)$/i', $loading_screen)) {
        echo '
        <video id="loadingVideo" src="'.htmlspecialchars($loading_screen).'" autoplay loop muted playsinline
               style="position: fixed; top:0; left:0; width:100%; height:100%; object-fit: cover; z-index:-1;"></video>

        <div style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        ">
            <h4 style="'.$textStyle.'">
                ⏳ Waiting for <span>'.htmlspecialchars($event_name).'</span> to start...
            </h4>
        </div>

        <button id="muteBtn" style="'.$buttonStyle.'">
            <i class="fas fa-volume-mute"></i> Mute
        </button>

        <script>
            const video = document.getElementById("loadingVideo");
            const btn = document.getElementById("muteBtn");
            video.muted = true;
            btn.addEventListener("click", () => {
                video.muted = !video.muted;
                btn.innerHTML = video.muted 
                    ? "<i class=\'fas fa-volume-mute\'></i> Mute" 
                    : "<i class=\'fas fa-volume-up\'></i> Unmute";
            });
            window.addEventListener("resize", () => {
                video.style.width = "100%";
                video.style.height = "100%";
            });
            setInterval(() => location.reload(), 10000);
        </script>';
    } elseif (!empty($loading_screen)) {
        echo '
        <img src="'.htmlspecialchars($loading_screen).'" alt="Loading Screen"
             style="position: fixed; top:0; left:0; width:100%; height:100%; object-fit: cover; z-index:-1;">

        <div style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        ">
            <h4 style="'.$textStyle.'">
                ⏳ Waiting for <span>'.htmlspecialchars($event_name).'</span> to start...
            </h4>
        </div>

        <button id="muteBtn" style="'.$buttonStyle.'">
            <i class="fas fa-volume-mute"></i> Mute
        </button>

        <script>
            const btnImg = document.getElementById("muteBtn");
            btnImg.addEventListener("click", () => {});
            window.addEventListener("resize", () => {});
            setInterval(() => location.reload(), 10000);
        </script>';
    } else {
        echo '
        <div style="
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: black;
            text-align: center;
        ">
            <h3 style="'.$textStyle.'">⏳ Waiting for the event to start...</h3>
            <script>setInterval(() => location.reload(), 10000);</script>
        </div>';
    }

    include('./includes/footer.php');
    exit;
}






}
?>


<div class="main ">
<?php include('./includes/nav.php'); ?>

<main class="content">
<div class="container-fluid p-0">



<?php if (!$event_id): ?>
    <h3>Judge Dashboard</h3>
    <p class="text-muted">You can only see events assigned to you.</p>
    <div class="card shadow-sm p-4">
        <h5>Your Assigned Events</h5><hr>
        <?php
        $res = $conn->query("
            SELECT e.id, e.event_name, e.start_date, e.end_date, e.venue, e.contestant_type
            FROM event_judges ej
            JOIN events e ON ej.event_id = e.id
            WHERE ej.judge_id = '$judge_id'
        ");
        if ($res && $res->num_rows > 0) {
            echo '<div class="list-group">';
            while ($row = $res->fetch_assoc()) {
                echo '<a href="index.php?event_id='.$row['id'].'" class="list-group-item list-group-item-action">
                        <strong>'.htmlspecialchars($row['event_name']).'</strong><br>
                        <small>📅 '.htmlspecialchars($row['start_date']).' — '.htmlspecialchars($row['end_date']).'</small><br>
                        <small>📍 '.htmlspecialchars($row['venue']).'</small><br>
                        <small>Type: '.htmlspecialchars($row['contestant_type']).'</small>
                      </a>';
            }
            echo '</div>';
        } else echo '<p class="text-muted">No events assigned yet.</p>';
        ?>
    </div>

<?php elseif (!$criteria_id): ?>
    <?php $event = $conn->query("SELECT * FROM events WHERE id='". $conn->real_escape_string($event_id) ."'")->fetch_assoc(); ?>
    <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
    <a href="index.php" class="btn btn-secondary btn-sm mb-3">← Back</a>
    <div class="card shadow-sm p-4">
        <h5>Select Category to Judge</h5><hr>
        <?php
        $criteria = $conn->query("SELECT * FROM criteria WHERE event_id='". $conn->real_escape_string($event_id) ."' ORDER BY id ASC");
        if ($criteria && $criteria->num_rows > 0) {
            echo '<div class="list-group">';
            while ($cat = $criteria->fetch_assoc()) {
                echo '<a href="index.php?event_id='.$event_id.'&criteria_id='.$cat['id'].'" class="list-group-item list-group-item-action">
                        <strong>'.htmlspecialchars($cat['name']).'</strong>
                        <small>('.htmlspecialchars($cat['percentage']).'%)</small>
                      </a>';
            }
            echo '</div>';
        } else echo '<p class="text-muted">No categories found for this event.</p>';
        ?>
    </div>

<?php else: ?>
<?php
// --- EVENT + CRITERIA INFO ---
$event = $conn->query("SELECT * FROM events WHERE id='". $conn->real_escape_string($event_id) ."'")->fetch_assoc();
$criteria = $conn->query("SELECT * FROM criteria WHERE id='". $conn->real_escape_string($criteria_id) ."'")->fetch_assoc();
$maxScore = (float)($criteria['percentage'] ?? 100);

// --- LOAD SAVED SCORES ---
$scores = $conn->query("
    SELECT contestant_id, score, is_finalized
    FROM scores
    WHERE judge_id='".$conn->real_escape_string($judge_id)."'
      AND event_id='".$conn->real_escape_string($event_id)."'
      AND criteria_id='".$conn->real_escape_string($criteria_id)."'
");
$saved_scores = [];
$is_finalized = false;
if ($scores && $scores->num_rows > 0) {
    while ($s = $scores->fetch_assoc()) {
        $saved_scores[$s['contestant_id']] = $s['score'];
        if ($s['is_finalized'] == 1) $is_finalized = true;
    }
}

// --- DETERMINE CRITERIA ORDER ---
$criteria_order = $conn->query("SELECT id FROM criteria WHERE event_id='$event_id' ORDER BY id ASC");
$criteria_ids = [];
while ($r = $criteria_order->fetch_assoc()) $criteria_ids[] = $r['id'];
$current_index = array_search($criteria_id, $criteria_ids);
$is_first_criteria = ($current_index === 0);

// --- FALLBACK: if this is NOT first criteria but there are NO top finalists saved yet,
// treat it as first criteria so judges/admins can continue scoring (avoids empty lists)
$top_count_q = $conn->query("
    SELECT COUNT(*) AS c FROM contestants 
    WHERE event_id='".$conn->real_escape_string($event_id)."' 
      AND is_top_finalist = 1
");
$top_count = ($top_count_q && $top_count_q->num_rows>0) ? intval($top_count_q->fetch_assoc()['c']) : 0;
if (!$is_first_criteria && $top_count === 0) {
    // no finalists set yet -> behave like first criteria (show non-eliminated contestants)
    $is_first_criteria = true;
}

// --- DETERMINE IF PAIRED EVENT ---
$pairs_count_q = $conn->query("SELECT COUNT(*) AS cnt FROM pairs WHERE event_id='". $conn->real_escape_string($event_id) ."'");
$pairs_count = ($pairs_count_q && $pairs_count_q->num_rows>0) ? intval($pairs_count_q->fetch_assoc()['cnt']) : 0;

$mr_list = [];
$ms_list = [];
$solo_list = [];

// --- FETCH CONTESTANTS BASED ON ELIMINATION/FINALIST STATUS ---
if ($pairs_count > 0) {
    $pairQuery = $conn->query("
        SELECT 
            mr.id AS mr_id, mr.name AS mr_name, mr.number AS mr_number, mr.image AS mr_image,
            mr.is_eliminated AS mr_elim, mr.is_top_finalist AS mr_final,
            ms.id AS ms_id, ms.name AS ms_name, ms.number AS ms_number, ms.image AS ms_image,
            ms.is_eliminated AS ms_elim, ms.is_top_finalist AS ms_final
        FROM pairs p
        INNER JOIN contestants mr ON p.mr_id = mr.id
        INNER JOIN contestants ms ON p.ms_id = ms.id
        WHERE p.event_id = '".$conn->real_escape_string($event_id)."'
        ORDER BY COALESCE(mr.number,0) ASC
    ");

    while ($pair = $pairQuery->fetch_assoc()) {
        // Show in first criteria: those who are NOT eliminated.
        // For later criteria show only those who are marked is_top_finalist (=mr_final/ms_final).
        $mr_show = $is_first_criteria ? !$pair['mr_elim'] : $pair['mr_final'];
        $ms_show = $is_first_criteria ? !$pair['ms_elim'] : $pair['ms_final'];

        if ($mr_show) $mr_list[] = $pair;
        if ($ms_show) $ms_list[] = $pair;
    }
} else {
    $solo_query = $conn->query("
        SELECT * FROM contestants 
        WHERE event_id='".$conn->real_escape_string($event_id)."' 
        ORDER BY number ASC
    ");
    while ($c = $solo_query->fetch_assoc()) {
        $show = $is_first_criteria ? !$c['is_eliminated'] : $c['is_top_finalist'];
        if ($show) $solo_list[] = $c;
    }
}
?>

<div class="card shadow-sm mb-4 border-0 overflow-hidden">
    <!-- Top Section: Event & Score -->
    <div class="card-header p-4" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
        <div class="row align-items-center">
            <!-- Event Title & Category -->
            <div class="col-md-8 text-white">
                <h4 class="mb-2 fw-bold">
                    🎤 <?php echo htmlspecialchars($event['event_name']); ?>
                </h4>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-white text-primary">CATEGORY</span>
                    <strong><?php echo htmlspecialchars($criteria['name']); ?></strong>
                </div>
            </div>
            
            <!-- Max Score -->
            <div class="col-md-4 text-end">
                <div class="bg-white rounded p-3 shadow">
                    <small class="text-muted text-uppercase d-block" style="font-size: 0.7em;">Max Score</small>
                    <div class="display-5 fw-bold text-primary"><?php echo $maxScore; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bottom Section: Instructions -->
    <div class="card-body p-4 bg-light">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-start">
                    <div class="me-3" style="font-size: 1.8em;">📋</div>
                    <div style="font-size: 0.95em;">
                        <strong class="d-block mb-2">Instructions for Judges:</strong>
                        <ul class="mb-0" style="padding-left: 1.2em; line-height: 1.8;">
                            <li>Enter a score between <strong>0</strong> and <strong class="text-success"><?php echo $maxScore; ?></strong> for each contestant</li>
                            <li>You must fill in all required scores before reviewing</li>
                            <li>Click <strong>"Review Scores"</strong> to verify and edit before final submission</li>
                            <li>Once submitted, scores for this category will be <strong>locked</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <a href="index.php?event_id=<?php echo $event_id; ?>" 
                   class="btn btn-outline-secondary btn-sm">
                    ← Back to Categories
                </a>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="save_scores.php" id="scoreForm">
<input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
<input type="hidden" name="judge_id" value="<?php echo $judge_id; ?>">
<input type="hidden" name="criteria_id" value="<?php echo $criteria_id; ?>">
<input type="hidden" id="__maxScore" value="<?php echo htmlspecialchars($maxScore); ?>">

<div class="card shadow-sm p-4">
<h5>Contestants Scoring</h5><hr>

<?php if ($pairs_count > 0): ?>
<div class="row">
    <!-- MR -->
    <div class="col-md-6 border-end">
        <h5 class="text-center text-primary mb-3">Mr Contestants</h5>
        <?php foreach ($mr_list as $con): 
            $existing_score = $saved_scores[$con['mr_id']] ?? '';
            $img = !empty($con['mr_image']) ? $con['mr_image'] : '../assets/default-avatar.png';
            // disabled when not first criteria and not marked finalist (mr_final==0)
            $disabled = (!$is_first_criteria && !$con['mr_final']) ? 'disabled' : '';
        ?>
        <div class="d-flex align-items-center border rounded p-3 mb-3 shadow-sm <?php echo ($disabled)?'bg-light text-muted opacity-75':''; ?>">
            <img src="<?php echo htmlspecialchars($img); ?>" class="rounded-circle me-3" style="width:70px;height:70px;object-fit:cover;">
            <div style="flex:1">
                <strong>No. <?php echo htmlspecialchars($con['mr_number']); ?></strong><br>
                <?php echo htmlspecialchars($con['mr_name']); ?>
            </div>
            <input type="number" step="1" min="0" max="<?php echo $maxScore; ?>"
       name="scores[<?php echo $con['mr_id']; ?>]" 
       value="<?php echo htmlspecialchars($existing_score); ?>" 
       class="form-control text-center score-input ms-3"
       style="width:100px" <?php echo $disabled; ?> <?php echo $is_finalized?'readonly':'required'; ?>
       data-name="<?php echo htmlspecialchars($con['mr_name']); ?>"
       data-img="<?php echo htmlspecialchars($img); ?>"
       data-number="<?php echo htmlspecialchars($con['mr_number']); ?>"
       data-gender="mr">

        </div>
        <?php endforeach; ?>
    </div>

    <!-- MS -->
    <div class="col-md-6">
        <h5 class="text-center text-danger mb-3">Ms Contestants</h5>
        <?php foreach ($ms_list as $con): 
            $existing_score = $saved_scores[$con['ms_id']] ?? '';
            $img = !empty($con['ms_image']) ? $con['ms_image'] : '../assets/default-avatar.png';
            $disabled = (!$is_first_criteria && !$con['ms_final']) ? 'disabled' : '';
        ?>
        <div class="d-flex align-items-center border rounded p-3 mb-3 shadow-sm <?php echo ($disabled)?'bg-light text-muted opacity-75':''; ?>">
            <img src="<?php echo htmlspecialchars($img); ?>" class="rounded-circle me-3" style="width:70px;height:70px;object-fit:cover;">
            <div style="flex:1">
                <strong>No. <?php echo htmlspecialchars($con['ms_number']); ?></strong><br>
                <?php echo htmlspecialchars($con['ms_name']); ?>
            </div>
            <input type="number" step="1" min="0" max="<?php echo $maxScore; ?>"
       name="scores[<?php echo $con['ms_id']; ?>]" 
       value="<?php echo htmlspecialchars($existing_score); ?>" 
       class="form-control text-center score-input ms-3"
       style="width:100px" <?php echo $disabled; ?> <?php echo $is_finalized?'readonly':'required'; ?>
       data-name="<?php echo htmlspecialchars($con['ms_name']); ?>"
       data-img="<?php echo htmlspecialchars($img); ?>"
       data-number="<?php echo htmlspecialchars($con['ms_number']); ?>"
       data-gender="ms">
 
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php else: ?>
<!-- SOLO -->
<div class="table-responsive">
    <table class="table table-bordered text-center align-middle">
        <thead><tr><th>Contestant</th><th>Score (0–<?php echo $maxScore; ?>)</th></tr></thead>
        <tbody>
            <?php foreach ($solo_list as $con): 
                $existing_score = $saved_scores[$con['id']] ?? '';
                $img = !empty($con['image']) ? $con['image'] : '../assets/default-avatar.png';
                $disabled = (!$is_first_criteria && !$con['is_top_finalist']) ? 'disabled' : '';
            ?>
            <tr class="<?php echo ($disabled)?'bg-light text-muted opacity-75':''; ?>">
                <td class="text-start">
                    <img src="<?php echo htmlspecialchars($img); ?>" class="rounded-circle me-2" style="width:60px;height:60px;object-fit:cover;">
                    <strong>No. <?php echo htmlspecialchars($con['number']); ?></strong> - 
                    <?php echo htmlspecialchars($con['name']); ?>
                </td>
                <td>
                   <?php
    // combine disabled condition for finalists + finalized flag
    $solo_disabled = ($disabled || $is_finalized) ? 'disabled' : '';
    $solo_style = ($solo_disabled) ? 'background-color:#e9ecef;color:#6c757d;' : '';
?>
<input type="number" step="1" min="0" max="<?php echo $maxScore; ?>"
   name="scores[<?php echo $con['id']; ?>]" 
   value="<?php echo htmlspecialchars($existing_score); ?>" 
   class="form-control text-center score-input"
   style="<?php echo $solo_style; ?>"
   <?php echo $solo_disabled ?: 'required'; ?>
   data-name="<?php echo htmlspecialchars($con['name']); ?>"
   data-img="<?php echo htmlspecialchars($img); ?>"
   data-number="<?php echo htmlspecialchars($con['number']); ?>"
   data-gender="solo">


                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
</div>

<?php if (!$is_finalized): ?>
    <div class="text-end mt-3">
        <button type="button" class="btn btn-success px-4" id="reviewBtn">Review Scores</button>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center mt-3">Scores are finalized and locked.</div>
<?php endif; ?>
</form>
<?php endif; ?>
</div>
</main>
<?php include("./includes/copy.php"); ?>
</div>
</div>



<!-- keep all scripts below unchanged -->
<!-- REVIEW MODAL + JS (unchanged) -->
<?php include('./includes/footer.php'); ?>


<!-- REVIEW MODAL -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Review & Edit Scores</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="reviewSingleWrapper" style="display:none;">
          <h5 class="text-center mb-3">Review Scores</h5>
          <div id="reviewSingle" style="max-height:60vh; overflow-y:auto;"></div>
        </div>

        <div id="reviewSplitWrapper" style="display:none;">
          <div class="row">
            <div class="col-md-6 border-end">
              <h5 class="text-center text-primary mb-3">Mr Contestants</h5>
              <div id="reviewMr" style="max-height:60vh; overflow-y:auto;"></div>
            </div>
            <div class="col-md-6">
              <h5 class="text-center text-danger mb-3">Ms Contestants</h5>
              <div id="reviewMs" style="max-height:60vh; overflow-y:auto;"></div>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
        <button type="submit" form="scoreForm" class="btn btn-success">Submit Final Scores</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {

  const reviewBtn = document.getElementById("reviewBtn");
  const maxScore = parseFloat(document.getElementById('__maxScore')?.value || 100);
  const pairsCount = <?php echo json_encode($pairs_count); ?>;

  if (!reviewBtn) return;

  reviewBtn.addEventListener("click", () => {

    const mrContainer = document.getElementById("reviewMr");
    const msContainer = document.getElementById("reviewMs");
    const singleContainer = document.getElementById("reviewSingle");
    const splitWrapper = document.getElementById("reviewSplitWrapper");
    const singleWrapper = document.getElementById("reviewSingleWrapper");

    mrContainer.innerHTML = "";
    msContainer.innerHTML = "";
    singleContainer.innerHTML = "";

    let valid = true;

    // ===== VALIDATE MAIN FORM INPUTS =====
    document.querySelectorAll(".score-input").forEach(input => {
      const value = input.value !== '' ? parseFloat(input.value) : NaN;

      if (isNaN(value) || value < 0 || value > maxScore) {
        valid = false;
        input.classList.add("is-invalid");
      } else {
        input.classList.remove("is-invalid");
      }
    });

    if (!valid) {
      Swal.fire({
        icon: "error",
        title: "Invalid Scores",
        html: `Please enter valid scores between <b>0</b> and <b>${maxScore}</b> for all contestants.`,
        confirmButtonColor: "#d33"
      });
      return;
    }

    // ===== BUILD REVIEW CARDS =====
    document.querySelectorAll(".score-input").forEach(input => {
      const name = input.dataset.name;
      const img = input.dataset.img;
      const number = input.dataset.number;
      const target = input.name;
      const gender = (input.dataset.gender || 'solo').toLowerCase();
      const value = input.value;

      const card = `
      <div class="d-flex align-items-center border rounded p-2 mb-2 shadow-sm">
        <img src="${img}" class="rounded-circle me-2" style="width:60px;height:60px;object-fit:cover;">
        <div style="flex:1">
          <strong>No. ${number}</strong><br>${name}
        </div>
        <input type="number" min="0" max="${maxScore}" 
               class="form-control modal-score text-center ms-2"
               data-target="${target}"
               value="${value}"
               style="width:90px;">
      </div>`;

      if (pairsCount > 0) {
        if (gender === 'mr') mrContainer.innerHTML += card;
        else if (gender === 'ms') msContainer.innerHTML += card;
        else singleContainer.innerHTML += card;
      } else {
        singleContainer.innerHTML += card;
      }
    });

    // ===== ONLY SHOW PROPER LAYOUT =====
    if (pairsCount > 0) {
      splitWrapper.style.display = "block";
      singleWrapper.style.display = "none";
    } else {
      splitWrapper.style.display = "none";
      singleWrapper.style.display = "block";
    }

    // Apply validators to modal inputs
    attachValidationHandlers();

    new bootstrap.Modal(document.getElementById("reviewModal")).show();
  });

  // ===== SYNC MODAL INPUTS BACK TO FORM =====
  function syncModalScores() {
    document.querySelectorAll(".modal-score").forEach(ms => {
      const targetName = ms.dataset.target;
      const formInput = document.querySelector(`[name="${targetName}"]`);
      if (formInput) formInput.value = ms.value;
    });
  }

  document.getElementById("reviewModal")
    .addEventListener("hidden.bs.modal", syncModalScores);


  // ===== ENSURE VALIDATION =====
  function attachValidationHandlers() {
    const allInputs = document.querySelectorAll(".score-input, .modal-score");

    allInputs.forEach(input => {
      input.addEventListener('input', () => {
        let val = input.value.replace(/\D/g, ''); // digits only

        if (val === "") val = "";
        else val = Math.min(parseInt(val), maxScore);

        input.value = val;
      });
    });
  }

  attachValidationHandlers(); // run for initial inputs


  // ===== FINAL SUBMIT VALIDATION =====
  const form = document.getElementById("scoreForm");

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    syncModalScores();

    // revalidate all inputs before final submit
    let valid = true;
    document.querySelectorAll(".score-input").forEach(input => {
      const value = parseFloat(input.value);

      if (isNaN(value) || value < 0 || value > maxScore) {
        valid = false;
        input.classList.add("is-invalid");
      } else {
        input.classList.remove("is-invalid");
      }
    });

    if (!valid) {
      Swal.fire({
        icon: "error",
        title: "Invalid Score",
        text: `All scores must be between 0 and ${maxScore}.`
      });
      return;
    }

    Swal.fire({
      icon: "success",
      title: "Scores Submitted!",
      text: "Thank you for scoring.",
      confirmButtonColor: "#28a745"
    }).then(() => {
      form.submit();
    });
  });

});
</script>



<?php include('./includes/footer.php'); ?>
