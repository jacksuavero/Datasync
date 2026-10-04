<?php 
include('./includes/header.php'); 
require '../config/dbcon.php';

// ✅ Ensure only logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ✅ Auto-update event statuses (runs every refresh)
$update_sql = "
    UPDATE events
    SET status = CASE
        WHEN CURDATE() < start_date THEN 'Upcoming'
        WHEN CURDATE() BETWEEN start_date AND end_date THEN 'Ongoing'
        WHEN CURDATE() > end_date THEN 'Ended'
    END
";
$conn->query($update_sql);
?>

<div class="wrapper">
    <?php include('./includes/sidebar.php') ?>

    <div class="main">
        <?php include('./includes/nav.php') ?>

        <style>
        .event-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #0d6efd;
        }
        </style>

        <main class="content">
            <div class="container-fluid p-0">
                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3>Admin Dashboard</h3>
                    </div>
                </div>

                <?php
                // ✅ Stats for events created by this admin
                $sql = "
                     SELECT 
            COUNT(*) AS total_events,
            SUM(CASE WHEN TRIM(status) = 'Ongoing' THEN 1 ELSE 0 END) AS ongoing_events,
            SUM(CASE WHEN TRIM(status) = 'Upcoming' THEN 1 ELSE 0 END) AS upcoming_events,
            SUM(CASE WHEN TRIM(status) = 'Ended' THEN 1 ELSE 0 END) AS ended_events
        FROM events
        WHERE created_by = '$user_id'
                ";
                $result = $conn->query($sql);
                $stats = $result->fetch_assoc() ?? ['total_events'=>0,'ongoing_events'=>0,'upcoming_events'=>0,'ended_events'=>0];
                ?>

                <!-- ================== Event Stats Cards ================== -->
                <div class="row mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Total Events</h5>
                                    <h2 class="mb-0"><?php echo $stats['total_events']; ?></h2>
                                </div>
                                <i data-feather="calendar" class="text-primary" width="36" height="36"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Ongoing</h5>
                                    <h2 class="mb-0 text-success"><?php echo $stats['ongoing_events']; ?></h2>
                                </div>
                                <i data-feather="activity" class="text-success" width="36" height="36"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Upcoming</h5>
                                    <h2 class="mb-0 text-warning"><?php echo $stats['upcoming_events']; ?></h2>
                                </div>
                                <i data-feather="clock" class="text-warning" width="36" height="36"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Ended</h5>
                                    <h2 class="mb-0 text-primary"><?php echo $stats['ended_events']; ?></h2>
                                </div>
                                <i data-feather="check-circle" class="text-primary" width="36" height="36"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================== Events Overview & Calendar Side by Side ================== -->
                <div class="row">
                    <!-- Left Column: Events Overview -->
                    <div class="col-lg-8 col-md-12 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Your Events Overview</h5>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-hover">
                                        <thead class="table-light">
                                    <tr>
                                    <th>Event Name</th>
                                    <th>Judges</th>
                                    <th>Contestants</th>
                                    <th>Status</th>
                                </tr>
                                </thead>

                                <tbody>
                                <?php
                                $sql = "
                                    SELECT 
                                        e.id AS event_id,
                                        e.event_name,
                                        e.start_date,
                                        e.end_date,
                                        e.logo,
                                        e.status,
                                        (SELECT COUNT(*) FROM contestants c WHERE c.event_id = e.id) AS contestant_count,
                                        GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS judges
                                    FROM events e
                                    LEFT JOIN event_judges ej ON e.id = ej.event_id
                                    LEFT JOIN users u ON ej.judge_id = u.id
                                    WHERE e.created_by = '$user_id'
                                    GROUP BY e.id
                                    ORDER BY e.created_at DESC
                                    LIMIT 5
                                ";

                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {

                                    while ($row = $result->fetch_assoc()) {

                                        $start = date('M d, Y', strtotime($row['start_date']));
                                        $end   = date('M d, Y', strtotime($row['end_date']));

                                        $statusClass = match(strtolower($row['status'])) {
                                            'ongoing'  => 'bg-success',
                                            'upcoming' => 'bg-warning text-dark',
                                            'ended'    => 'bg-primary',
                                            default    => 'bg-secondary'
                                        };

                                        echo "<tr>";

                                        echo "<td>
                                                <div class='d-flex align-items-center gap-3'>
                                                    <div>";
                                        
                                        if (!empty($row['logo'])) {
                                            echo "<img src='" . htmlspecialchars($row['logo']) . "' width='50' height='50' class='rounded-circle border'>";
                                        } else {
                                            echo "<span class='text-muted'>No logo</span>";
                                        }

                                        echo "      </div>

                                                    <div>
                                                        <strong>" . htmlspecialchars($row['event_name']) . "</strong><br>
                                                        <small class='text-muted'>{$start} - {$end}</small>
                                                    </div>
                                                </div>
                                            </td>";

                                        echo "<td>";
                                        echo (!empty($row['judges']))
                                            ? htmlspecialchars($row['judges'])
                                            : "<em class='text-muted'>No judges</em>";
                                        echo "</td>";
                                        echo "<td>";
                                        echo ($row['contestant_count'] > 0)
                                            ? $row['contestant_count']
                                            : "<em class='text-muted'>0</em>";
                                        echo "</td>";

                                        echo "<td>
                                                <span class='badge rounded-pill {$statusClass} px-3 py-2'>
                                                    " . htmlspecialchars($row['status']) . "
                                                </span>
                                            </td>";

                                        echo "</tr>";
                                    }

                                } else {
                                    echo "<tr><td colspan='4' class='text-center text-muted'>No events found</td></tr>";
                                }
                                ?>
                                </tbody>
                                </table>
                                </div>

                                <a href="view_events.php" class="text-decoration-none">
                                    <p class="text-center mt-3">View All Events</p>
                                </a>
                                </div>
                                </div>
                                </div>

                                <div class="col-lg-4 col-md-12">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Calendar</h5>
                                        </div>
                                        <div class="card-body d-flex">
                                            <div class="align-self-center w-100">
                                                <div class="chart">
                                                    <div id="datetimepicker-dashboard"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div> 
                                </div>
                                </main>

                                <?php include("./includes/copy.php") ?>
                                </div>
                                </div>

                                <?php include('./includes/footer.php') ?>

                                <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    var date = new Date();
                                    var defaultDate = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();

                                    document.getElementById("datetimepicker-dashboard").flatpickr({
                                        inline: true,
                                        prevArrow: "<span class='fas fa-chevron-left'></span>",
                                        nextArrow: "<span class='fas fa-chevron-right'></span>",
                                        defaultDate: defaultDate
                                    });
                                });
                                </script>