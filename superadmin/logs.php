<?php 
include('./includes/header.php'); 
require '../config/dbcon.php';
include('../includes/log_helper.php');

if ($_SESSION['role'] !== 'superadmin') {
    header("Location: ../index.php");
    exit;
}
?>

<div class="wrapper">
    <?php include('./includes/sidebar.php'); ?>
    <div class="main">
        <?php include('./includes/nav.php'); ?>

        <main class="content">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>System Logs</h3>
                    <div>
                        <button class="btn btn-success btn-sm me-2" id="exportExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger btn-sm" id="exportPDF">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- 🔍 Filter Form -->
                        <form method="GET" class="row g-2 mb-3" id="filterForm">
                            <div class="col-md-3">
                                <input type="text" name="user" id="userFilter" class="form-control" placeholder="Filter by User" value="<?= $_GET['user'] ?? '' ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="event" id="eventFilter" class="form-control" placeholder="Filter by Event" value="<?= $_GET['event'] ?? '' ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="date" id="dateFilter" class="form-control" value="<?= $_GET['date'] ?? '' ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>

                        <?php
                        // ✅ Pagination setup
                        $limit = 10;
                        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
                        $offset = ($page - 1) * $limit;

                        // ✅ Filters
                        $where = [];
                        if (!empty($_GET['user'])) {
                            $u = $conn->real_escape_string($_GET['user']);
                            $where[] = "CONCAT(users.first_name, ' ', users.last_name) LIKE '%$u%'";
                        }
                        if (!empty($_GET['event'])) {
                            $e = $conn->real_escape_string($_GET['event']);
                            $where[] = "logs.description LIKE '%$e%'";
                        }
                        if (!empty($_GET['date'])) {
                            $d = $conn->real_escape_string($_GET['date']);
                            $where[] = "DATE(logs.created_at) = '$d'";
                        }
                        $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

                        // ✅ Count total logs
                        $count_sql = "
                            SELECT COUNT(*) AS total 
                            FROM logs 
                            JOIN users ON users.id = logs.user_id
                            $whereSQL
                        ";
                        $count_result = $conn->query($count_sql);
                        $total = $count_result->fetch_assoc()['total'] ?? 0;
                        $total_pages = ceil($total / $limit);

                        // ✅ Fetch logs
                        $sql = "
                            SELECT logs.*, CONCAT(users.first_name, ' ', users.last_name) AS user_name
                            FROM logs 
                            JOIN users ON users.id = logs.user_id
                            $whereSQL
                            ORDER BY logs.created_at DESC
                            LIMIT $limit OFFSET $offset
                        ";
                        $result = $conn->query($sql);
                        ?>

                        <table class="table table-hover table-bordered align-middle" id="logsTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    $i = $offset + 1;
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>{$i}</td>
                                                <td>" . htmlspecialchars($row['user_name']) . "</td>
                                                <td>" . htmlspecialchars($row['action']) . "</td>
                                                <td>" . htmlspecialchars($row['description']) . "</td>
                                                <td>" . htmlspecialchars($row['created_at']) . "</td>
                                              </tr>";
                                        $i++;
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>No logs found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <!-- ✅ Modern Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <style>
                        .pagination-modern {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            gap: 8px;
                            margin-top: 25px;
                        }
                        .pagination-modern a, .pagination-modern span {
                            display: inline-flex;
                            justify-content: center;
                            align-items: center;
                            width: 40px;
                            height: 40px;
                            border-radius: 10px;
                            font-weight: 600;
                            font-size: 15px;
                            color: #007bff;
                            background: #fff;
                            border: 1px solid #dee2e6;
                            text-decoration: none;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                            transition: all 0.25s ease;
                        }
                        .pagination-modern a:hover {
                            background: #007bff;
                            color: #fff;
                            transform: translateY(-2px);
                        }
                        .pagination-modern .active {
                            background: #007bff;
                            color: #fff;
                            border-color: #007bff;
                            box-shadow: 0 0 10px rgba(0,123,255,0.3);
                        }
                        .pagination-modern .disabled {
                            opacity: 0.4;
                            pointer-events: none;
                        }
                        </style>

                        <div class="pagination-modern">
                            <?php
                            $query = $_GET;
                            $visible = 2;
                            $prev = $page - 1;
                            $next = $page + 1;

                            // Prev
                            $query['page'] = $prev;
                            $prevLink = '?' . http_build_query($query);
                            echo "<a href='$prevLink' class='" . ($page <= 1 ? 'disabled' : '') . "'>&#8249;</a>";

                            $start = max(1, $page - $visible);
                            $end = min($total_pages, $page + $visible);

                            if ($start > 1) {
                                $query['page'] = 1;
                                echo "<a href='?" . http_build_query($query) . "'>1</a>";
                                if ($start > 2) echo "<span>...</span>";
                            }

                            for ($i = $start; $i <= $end; $i++) {
                                $query['page'] = $i;
                                $active = ($i == $page) ? 'active' : '';
                                echo "<a class='$active' href='?" . http_build_query($query) . "'>$i</a>";
                            }

                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) echo "<span>...</span>";
                                $query['page'] = $total_pages;
                                echo "<a href='?" . http_build_query($query) . "'>$total_pages</a>";
                            }

                            // Next
                            $query['page'] = $next;
                            $nextLink = '?' . http_build_query($query);
                            echo "<a href='$nextLink' class='" . ($page >= $total_pages ? 'disabled' : '') . "'>&#8250;</a>";
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include('./includes/copy.php'); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>

<!-- ✅ Export Scripts -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
document.getElementById('exportExcel').addEventListener('click', function() {
    const wb = XLSX.utils.table_to_book(document.getElementById('logsTable'), {sheet: "Logs"});
    XLSX.writeFile(wb, 'logs_export.xlsx');
});

document.getElementById('exportPDF').addEventListener('click', function() {
    import('https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js').then(() => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.text("System Logs", 14, 15);
        doc.autoTable({ html: '#logsTable', startY: 20 });
        doc.save('logs_export.pdf');
    });
});
</script>

<!-- ✅ Auto Reset Filter on Clear -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById('filterForm');
    const inputs = form.querySelectorAll('input');

    // 🔁 Auto reset to default when all fields are cleared
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            const isAllEmpty = Array.from(inputs).every(inp => !inp.value.trim());
            if (isAllEmpty) {
                window.location.href = window.location.pathname;
            }
        });
    });

    // ✅ Press Enter = Submit Filter
    form.addEventListener('keypress', e => {
        if (e.key === 'Enter') form.submit();
    });
});
</script>
