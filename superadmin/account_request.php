<?php 
include('./includes/header.php'); 
require_once './config/dbcon.php'; 
include('../includes/log_helper.php');// Ensure this sets up $conn correctly

// ✅ Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}

// ✅ Correct query using SELECT *
$sql = "
    SELECT r.*, e.event_name, 
           CONCAT(u.first_name, ' ', u.last_name) AS created_by
    FROM request r
    LEFT JOIN events e ON r.event_id = e.id
    LEFT JOIN users u ON e.created_by = u.id
    WHERE r.status = 'Pending'
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("❌ SQL Prepare Error: " . $conn->error);
}

if (!$stmt->execute()) {
    die("❌ SQL Execute Error: " . $stmt->error);
}

$result = $stmt->get_result();

if (!$result) {
    die("❌ SQL Get Result Error: " . $conn->error);
}
?>

<div class="wrapper">
    <!-- Sidebar Section -->
    <?php include('./includes/sidebar.php'); ?>

    <div class="main">
        <!-- Navbar Section -->
        <?php include('./includes/nav.php'); ?>

        <main class="content px-4 py-4">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="fw-bold">Account Requests</h1>
                </div>

               <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Display Success or Error Messages -->
<?php if (isset($_SESSION['message'])): ?>
    <script>
        window.onload = function() {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo addslashes($_SESSION['message']); ?>',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        };
    </script>
    <?php unset($_SESSION['message']); ?>
<?php elseif (isset($_SESSION['error'])): ?>
    <script>
        window.onload = function() {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo addslashes($_SESSION['error']); ?>',
                confirmButtonColor: '#d33',
                confirmButtonText: 'OK'
            });
        };
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


                <!-- Account Requests Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Pending Account Requests</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Middle Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Event Name</th>
                                    <th>Created By</th>

                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['firstname']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['lastname']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['middlename']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                                        echo "<td>" . (!empty($row['event_name']) ? htmlspecialchars($row['event_name']) : "<em>N/A</em>") . "</td>";
                                        echo "<td>" . (!empty($row['created_by']) ? htmlspecialchars($row['created_by']) : "<em>N/A</em>") . "</td>";

                                      echo "<td class='text-center'>
        <a href='approve.php?id=" . htmlspecialchars($row['id']) . "' 
           class='btn btn-sm btn-success me-1' 
           title='Approve Request'>
            <i class='fas fa-check'></i>
        </a>
        <a href='reject.php?id=" . htmlspecialchars($row['id']) . "' 
           class='btn btn-sm btn-danger' 
           title='Reject Request'>
            <i class='fas fa-times'></i>
        </a>
      </td>";

                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center text-muted'>No pending requests found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <?php include("./includes/copy.php"); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>
