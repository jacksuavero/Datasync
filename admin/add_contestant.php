<?php 
include('./includes/header.php'); 
include('../includes/log_helper.php');
?>

<div class="wrapper">
    <?php include('./includes/sidebar.php') ?>
    <div class="main">
        <?php include('./includes/nav.php') ?>

        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3>Add Contestants</h3>
                    </div>
                </div>

                <div class="card shadow-sm p-4">
                    <form id="contestantForm" method="POST" action="save_contestants.php" enctype="multipart/form-data">
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
                                    $raw = strtolower(trim($r['contestant_type'] ?? ''));
                                    if (
                                        (strpos($raw, 'mr') !== false && strpos($raw, 'ms') !== false) ||
                                        (strpos($raw, 'mr') !== false && strpos($raw, 'miss') !== false)
                                    ) {
                                        $norm = 'mr&ms';
                                    } elseif (strpos($raw, 'group') !== false) {
                                        $norm = 'group';
                                    } elseif (strpos($raw, 'solo') !== false) {
                                        $norm = 'solo';
                                    } elseif (strpos($raw, 'gay') !== false) {
                                        $norm = 'msgay';
                                    } elseif (preg_match('/\bmr\b/', $raw)) {
                                        $norm = 'mr';
                                    } elseif (preg_match('/\bms\b/', $raw)) {
                                        $norm = 'ms';
                                    } else {
                                        $norm = 'other';
                                    }

                                    $display = htmlspecialchars($r['event_name'] . " ({$r['contestant_type']})");
                                    echo "<option value=\"{$r['id']}\" data-type=\"{$norm}\">{$display}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Number of Contestants / Pairs -->
                        <div id="numWrapper" class="mb-3" style="display: none;">
                            <label id="numLabel" class="form-label">Enter number:</label>
                            <input type="number" id="numCount" class="form-control" min="1">
                        </div>

                        <input type="hidden" id="contestantType" name="contestant_type">

                        <!-- Button to generate contestant fields -->
                        <div class="mb-3 text-end" id="generateBtnWrapper" style="display: none;">
                            <button type="button" id="generateForm" class="btn btn-primary">Generate Form</button>
                        </div>

                        <!-- Dynamic contestant form container -->
                        <div id="contestantInputs"></div>

                        <!-- Save button -->
                        <div class="mt-3 text-end" id="saveBtnWrapper" style="display: none;">
                            <button type="submit" class="btn btn-success px-4">Save Contestants</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include("./includes/copy.php") ?>
    </div>
</div>

<?php include('./includes/footer.php') ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventSelect = document.getElementById('eventSelect');
    const numWrapper = document.getElementById('numWrapper');
    const numLabel = document.getElementById('numLabel');
    const numCount = document.getElementById('numCount');
    const generateBtnWrapper = document.getElementById('generateBtnWrapper');
    const generateFormBtn = document.getElementById('generateForm');
    const contestantInputs = document.getElementById('contestantInputs');
    const saveBtnWrapper = document.getElementById('saveBtnWrapper');
    const contestantTypeInput = document.getElementById('contestantType');

    let selectedType = '';

    eventSelect.addEventListener('change', function() {
        contestantInputs.innerHTML = '';
        saveBtnWrapper.style.display = 'none';
        const selected = this.options[this.selectedIndex];
        selectedType = selected.getAttribute('data-type');
        contestantTypeInput.value = selectedType;

        if (!selected.value) {
            numWrapper.style.display = 'none';
            generateBtnWrapper.style.display = 'none';
            return;
        }

        if (selectedType === 'mr&ms') {
            numLabel.textContent = 'Enter number of pairs:';
        } else {
            numLabel.textContent = 'Enter number of contestants:';
        }

        numWrapper.style.display = 'block';
        generateBtnWrapper.style.display = 'block';
    });

    generateFormBtn.addEventListener('click', function() {
        const count = parseInt(numCount.value);
        if (isNaN(count) || count <= 0) {
            alert('Please enter a valid number.');
            return;
        }

        contestantInputs.innerHTML = '';

        if (selectedType === 'mr&ms') {
            for (let i = 1; i <= count; i++) {
                contestantInputs.innerHTML += `
                    <div class="row mb-3 border rounded p-3">
                        <div class="col-md-2">
                            <label class="form-label">Pair ${i} Number</label>
                            <input type="number" name="pair_numbers[]" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Pair ${i} - Mr</label>
                            <input type="text" name="mr_names[]" class="form-control mb-2" placeholder="Enter Mr Name" required>
                            <input type="file" name="mr_images[]" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Pair ${i} - Ms</label>
                            <input type="text" name="ms_names[]" class="form-control mb-2" placeholder="Enter Ms Name" required>
                            <input type="file" name="ms_images[]" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                `;
            }
        } else {
            for (let i = 1; i <= count; i++) {
                contestantInputs.innerHTML += `
                    <div class="row mb-3 border rounded p-3">
                        <div class="col-md-3">
                            <label class="form-label">Contestant ${i} Number</label>
                            <input type="number" name="contestant_numbers[]" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Contestant ${i} Name</label>
                            <input type="text" name="solo_names[]" class="form-control" placeholder="Enter Name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contestant ${i} Image</label>
                            <input type="file" name="contestant_images[]" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                `;
            }
        }

        saveBtnWrapper.style.display = 'block';
    });
});



// Function to validate a name field
function validateNameField(input) {
    const pattern = /^[a-zA-Z\s]+$/;
    let errorEl = input.nextElementSibling;
    
    // Remove existing error
    if (errorEl && errorEl.classList.contains('name-error')) {
        errorEl.remove();
    }

    if (!pattern.test(input.value.trim())) {
        const error = document.createElement('div');
        error.classList.add('text-danger', 'name-error', 'mt-1');
        error.textContent = 'Names can only contain letters and spaces.';
        input.after(error);
        return false;
    }
    return true;
}

// Attach input event to all current and future name fields
document.getElementById('contestantInputs').addEventListener('input', function(e) {
    if (e.target.tagName === 'INPUT' && e.target.type === 'text') {
        validateNameField(e.target);
    }
});

// On form submit, validate all text inputs
document.getElementById('contestantForm').addEventListener('submit', function(e) {
    const textInputs = this.querySelectorAll('input[type="text"]');
    let valid = true;
    textInputs.forEach(input => {
        if (!validateNameField(input)) valid = false;
    });
    if (!valid) e.preventDefault();
});



document.getElementById('contestantForm').addEventListener('submit', function(e) {
    const nameInputs = this.querySelectorAll('input[type="text"]');
    const namePattern = /^[a-zA-Z\s]+$/; // letters and spaces only

    for (let input of nameInputs) {
        if (!namePattern.test(input.value.trim())) {
            e.preventDefault(); // stop form submission
            Swal.fire({
                icon: 'error',
                title: 'Invalid Name',
                text: 'Contestant names can only contain letters and spaces.',
                confirmButtonColor: '#d33',
                confirmButtonText: 'OK'
            });
            input.focus();
            return false;
        }
    }
});
</script>
