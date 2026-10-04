<?php 
include('./includes/header.php'); 
include('../includes/log_helper.php');
?>
<div class="wrapper">
    <?php include('./includes/sidebar.php'); ?>
    <div class="main">
        <?php include('./includes/nav.php'); ?>

        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3>Manage Users</h3>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0">User Accounts</h5>

                        <!-- 🔍 Search Form (modern style) -->
                        <form method="get" class="d-flex align-items-center bg-white rounded-pill shadow-sm px-3 py-1" style="gap:6px;">
                            <i class="fas fa-search text-muted"></i>
                            <input type="text" name="search" class="form-control border-0 form-control-sm shadow-none" 
                                   placeholder="Search name or email..."
                                   style="width:180px;"
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                                Search
                            </button>
                        </form>
                    </div>

                    <div class="card-body table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require '../config/dbcon.php';

                                // ✅ Pagination setup
                                $limit = 10; 
                                $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
                                $offset = ($page - 1) * $limit;

                                // ✅ Search filter
                                $search = $_GET['search'] ?? '';
                                $where = '';
                                $params = [];
                                $types = '';

                                if (!empty($search)) {
                                    $where = "WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR email LIKE ?";
                                    $search_param = "%$search%";
                                    $params = [$search_param, $search_param];
                                    $types = "ss";
                                }

                                // ✅ Count total
                                $count_sql = "SELECT COUNT(*) AS total FROM users $where";
                                $count_stmt = $conn->prepare($count_sql);
                                if (!empty($where)) $count_stmt->bind_param($types, ...$params);
                                $count_stmt->execute();
                                $total = $count_stmt->get_result()->fetch_assoc()['total'];
                                $total_pages = ceil($total / $limit);

                                // ✅ Fetch users
                                $sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
                                $stmt = $conn->prepare($sql);
                                if (!empty($where)) {
                                    $types .= "ii";
                                    $params[] = $limit;
                                    $params[] = $offset;
                                    $stmt->bind_param($types, ...$params);
                                } else {
                                    $stmt->bind_param("ii", $limit, $offset);
                                }

                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result && $result->num_rows > 0) {
                                    while ($user = $result->fetch_assoc()) {
                                        $statusBadge = match ($user['status']) {
                                            'active' => "<span class='badge bg-success'>Active</span>",
                                            'inactive' => "<span class='badge bg-danger'>Inactive</span>",
                                            default => "<span class='badge bg-warning text-dark'>Pending</span>"
                                        };

                                        echo "<tr>
                                            <td>{$user['id']}</td>
                                            <td>{$user['first_name']} {$user['last_name']}</td>
                                            <td>{$user['email']}</td>
                                            <td><span class='badge bg-info text-dark'>{$user['role']}</span></td>
                                            <td>{$statusBadge}</td>
                                            <td>" . date('Y-m-d', strtotime($user['created_at'])) . "</td>
                                            <td class='text-center'>
                                                <div class='d-flex justify-content-center gap-2 flex-wrap'>
                                                    <button class='btn btn-sm btn-success activateBtn' data-id='{$user['id']}' title='Activate'>
                                                        <i class='fas fa-check'></i>
                                                    </button>
                                                    <button class='btn btn-sm btn-warning deactivateBtn' data-id='{$user['id']}' title='Deactivate'>
                                                        <i class='fas fa-ban'></i>
                                                    </button>
                                                    <button class='btn btn-sm btn-info resetBtn' data-id='{$user['id']}' title='Reset Password'>
                                                        <i class='fas fa-key'></i>
                                                    </button>
                                                    <button class='btn btn-sm btn-secondary roleBtn' data-id='{$user['id']}' title='Change Role'>
                                                        <i class='fas fa-user-edit'></i>
                                                    </button>
                                                    <button class='btn btn-sm btn-danger deleteBtn' data-id='{$user['id']}' title='Delete'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center text-muted'>No users found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <!-- ✅ Modern Rounded Pagination -->
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
                            <!-- Prev -->
                            <a href="?page=<?= max(1, $page - 1) ?>&search=<?= urlencode($search) ?>" 
                               class="<?= ($page <= 1) ? 'disabled' : '' ?>">&#8249;</a>

                            <!-- Page Numbers -->
                            <?php
                            $visible = 2;
                            $start = max(1, $page - $visible);
                            $end = min($total_pages, $page + $visible);

                            if ($start > 1) {
                                echo '<a href="?page=1&search='.urlencode($search).'">1</a>';
                                if ($start > 2) echo '<span>...</span>';
                            }

                            for ($i = $start; $i <= $end; $i++) {
                                $active = ($i == $page) ? 'active' : '';
                                echo "<a class='$active' href='?page=$i&search=".urlencode($search)."'>$i</a>";
                            }

                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) echo '<span>...</span>';
                                echo '<a href="?page='.$total_pages.'&search='.urlencode($search).'">'.$total_pages.'</a>';
                            }
                            ?>

                            <!-- Next -->
                            <a href="?page=<?= min($total_pages, $page + 1) ?>&search=<?= urlencode($search) ?>" 
                               class="<?= ($page >= $total_pages) ? 'disabled' : '' ?>">&#8250;</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include("./includes/copy.php"); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>


<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* your existing JS — untouched */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.activateBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            Swal.fire({
                title: "Activate Account?",
                text: "This will allow the user to log in.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, Activate",
                confirmButtonColor: "#198754"
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `user_actions.php?action=activate&id=${id}`;
                }
            });
        });
    });

    document.querySelectorAll('.deactivateBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            Swal.fire({
                title: "Deactivate Account?",
                text: "User will not be able to log in.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Deactivate",
                confirmButtonColor: "#dc3545"
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `user_actions.php?action=deactivate&id=${id}`;
                }
            });
        });
    });

    document.querySelectorAll('.resetBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            Swal.fire({
                title: "Reset Password?",
                text: "A new password will be generated and sent to the user's email.",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "Yes, Reset",
                confirmButtonColor: "#0d6efd"
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `user_actions.php?action=reset&id=${id}`;
                }
            });
        });
    });

    document.querySelectorAll('.roleBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            Swal.fire({
                title: "Change Role",
                input: "select",
                inputOptions: {
                    superadmin: "Super Admin",
                    admin: "Admin",
                    judge: "Judge"
                },
                inputPlaceholder: "Select new role",
                showCancelButton: true,
                confirmButtonText: "Change Role"
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    window.location.href = `user_actions.php?action=change_role&id=${id}&role=${result.value}`;
                }
            });
        });
    });

    document.querySelectorAll('.deleteBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            Swal.fire({
                title: "Delete User?",
                text: "This action cannot be undone!",
                icon: "error",
                showCancelButton: true,
                confirmButtonText: "Yes, Delete",
                confirmButtonColor: "#dc3545"
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `user_actions.php?action=delete&id=${id}`;
                }
            });
        });
    });
});
</script>
