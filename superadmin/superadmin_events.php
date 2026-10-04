<?php include('./includes/header.php') ?>

<div class="wrapper">
    <!-- ================= Sidebar Section ================= -->
    <?php include('./includes/sidebar.php') ?>
    <!-- ================= Sidebar Section ================= -->

    <div class="main">
        <!-- ================= Navbar Section ================= -->
        <?php include('./includes/nav.php') ?>
        <!-- ================= Navbar Section ================= -->

        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3>All Events</h3>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Event List</h5>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>ID</th>
                                    <th>Logo</th> <!-- ✅ Added logo column -->
                                    <th>Event Name</th>
                                    <th>Category</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Created By</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // ✅ Fetch all events and their creators (include logo)
                                $sql = "
                                    SELECT 
                                        e.id AS event_id,
                                        e.event_name,
                                        e.event_category,
                                        e.start_date,
                                        e.end_date,
                                        e.status,
                                        e.logo,
                                        CONCAT(u.first_name, ' ', u.last_name) AS created_by
                                    FROM events e
                                    LEFT JOIN users u ON e.created_by = u.id
                                    ORDER BY e.id DESC
                                ";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                       $logoPath = "../admin/" . $row['logo'];
$logoDisplay = !empty($row['logo'])
    ? "<img src='{$logoPath}' alt='Logo' width='50' height='50' class='rounded-circle border'>"
    : "<span class='text-muted'>No logo</span>";


                                        echo "
                                            <tr>
                                                <td>{$row['event_id']}</td>
                                                <td class='text-center'>$logoDisplay</td> <!-- ✅ Logo cell -->
                                                <td>{$row['event_name']}</td>
                                                <td>{$row['event_category']}</td>
                                                <td>{$row['start_date']}</td>
                                                <td>{$row['end_date']}</td>
                                                <td>{$row['created_by']}</td>
                                                <td>{$row['status']}</td>
                                                <td class='text-center'>
                                                    <a href='judges_full_report.php?event_id={$row['event_id']}' class='btn btn-sm btn-primary'>
                                                        <i class='fas fa-chart-line'></i> View Report
                                                    </a>
                                                </td>
                                            </tr>
                                        ";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center text-muted'>No events found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <?php include("./includes/copy.php") ?>
    </div>
</div>

<?php include('./includes/footer.php') ?>
