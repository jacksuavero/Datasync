<?php include('./includes/header.php'); 
include('../includes/log_helper.php');?>


<div class="wrapper">
    <!-- Sidebar -->
    <?php include('./includes/sidebar.php'); ?>

    <div class="main">
        <!-- Navbar -->
        <?php include('./includes/nav.php'); ?>

        <main class="content">
            <div class="container-fluid p-0">
                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3>Add Criteria</h3>
                    </div>
                </div>

                <div class="card shadow-sm p-4">
                    <form id="criteriaForm" method="POST" action="save_criteria.php">
                        <!-- Select Event -->
                        <div class="mb-3">
                            <label for="eventSelect" class="form-label">Select Event</label>
                            <select class="form-select" id="eventSelect" name="event_id" required>
                                <option value="">-- Select Event --</option>
                                <?php
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'superadmin') {
    $res = $conn->query("
        SELECT id, event_name, contestant_type 
        FROM events 
        WHERE status IN ('Upcoming', 'Ongoing')
        ORDER BY created_at DESC
    ");
} else {
    $res = $conn->query("
        SELECT id, event_name, contestant_type 
        FROM events 
        WHERE created_by = '$user_id'
        AND status IN ('Upcoming', 'Ongoing')
        ORDER BY created_at DESC
    ");
}

                                while ($r = $res->fetch_assoc()) {
                                    echo "<option value='{$r['id']}'>" . htmlspecialchars($r['event_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Number of Criteria -->
                        <div class="mb-3" id="numWrapper" style="display:none;">
                            <label class="form-label">Enter number of criteria:</label>
                            <input type="number" id="numCriteria" class="form-control" min="1" name="num_criteria">
                        </div>

                        <!-- Generate button -->
                        <div class="text-end mb-3" id="generateBtnWrapper" style="display:none;">
                            <button type="button" id="generateForm" class="btn btn-primary">Generate Form</button>
                        </div>

                        <!-- Dynamic form -->
                        <div id="criteriaInputs"></div>

                        <!-- Save button -->
                        <div class="text-end mt-3" id="saveBtnWrapper" style="display:none;">
                            <button type="submit" class="btn btn-primary px-4">Save Criteria</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include("./includes/copy.php"); ?>
    </div>
</div>

<?php include('./includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventSelect = document.getElementById('eventSelect');
    const numWrapper = document.getElementById('numWrapper');
    const numCriteria = document.getElementById('numCriteria');
    const generateBtnWrapper = document.getElementById('generateBtnWrapper');
    const generateFormBtn = document.getElementById('generateForm');
    const criteriaInputs = document.getElementById('criteriaInputs');
    const saveBtnWrapper = document.getElementById('saveBtnWrapper');
    const criteriaForm = document.getElementById('criteriaForm');

    eventSelect.addEventListener('change', function() {
        if (this.value) {
            numWrapper.style.display = 'block';
            generateBtnWrapper.style.display = 'block';
            criteriaInputs.innerHTML = '';
            saveBtnWrapper.style.display = 'none';
        } else {
            numWrapper.style.display = 'none';
            generateBtnWrapper.style.display = 'none';
        }
    });

    generateFormBtn.addEventListener('click', function() {
        const count = parseInt(numCriteria.value);
        if (isNaN(count) || count <= 0) {
            Swal.fire('Invalid', 'Please enter a valid number of criteria.', 'error');
            return;
        }

        criteriaInputs.innerHTML = '';
        for (let i = 1; i <= count; i++) {
            criteriaInputs.innerHTML += `
                <div class="row mb-3 border rounded p-3">
                    <div class="col-md-8">
                        <label class="form-label">Criteria ${i} Name</label>
                        <input type="text" name="criteria_names[]" class="form-control" placeholder="e.g., Beauty, Talent, Q&A" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Percentage (%)</label>
                        <input type="number" step="0.01" name="criteria_percents[]" class="form-control" placeholder="e.g., 20" required>
                    </div>
                </div>
            `;
        }

        saveBtnWrapper.style.display = 'block';
    });

    // Validate total percentage on form submit
    criteriaForm.addEventListener('submit', function(e) {
        e.preventDefault(); // prevent default submit
        const percentFields = document.querySelectorAll('input[name="criteria_percents[]"]');
        let total = 0;
        percentFields.forEach(f => total += parseFloat(f.value) || 0);
        total = parseFloat(total.toFixed(2));

        if (total !== 100) {
            Swal.fire('Error', 'Total percentage of all criteria must be exactly 100. Current total: ' + total + '%', 'error');
            return false;
        }

        // If total is 100, submit form via SweetAlert success
        Swal.fire({
            title: 'Confirm',
            text: 'All criteria percentages are valid. Do you want to save?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                criteriaForm.submit();
            }
        });
    });
});

// Real-time validation for criteria percentages
document.addEventListener('input', function(e) {
    if (e.target && e.target.name === 'criteria_percents[]') {
        const val = parseFloat(e.target.value);
        if (val < 0) {
            // Show SweetAlert error
            Swal.fire({
                icon: 'error',
                title: 'Invalid Input',
                text: 'Percentage cannot be negative.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });

            // Reset input to 0 or empty
            e.target.value = '';
        }
    }
});

</script>

