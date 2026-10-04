<?php
require '../config/dbcon.php';
include('../includes/log_helper.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $type = $_POST['contestant_type'] ?? '';

    if (empty($event_id)) {
        die("Invalid input");
    }

    // Ensure upload folder exists
    $target_dir = "../uploads/contestants/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if ($type === 'mr&ms') {
        $mr_names = $_POST['mr_names'] ?? [];
        $ms_names = $_POST['ms_names'] ?? [];
        $pair_numbers = $_POST['pair_numbers'] ?? [];

        for ($i = 0; $i < count($mr_names); $i++) {
            $mr = trim($mr_names[$i]);
            $ms = trim($ms_names[$i]);
            $pair_number = trim($pair_numbers[$i] ?? '');

            // Handle MR image upload
            $mr_image = '';
            if (!empty($_FILES['mr_images']['name'][$i])) {
                $mr_img_name = time() . "_mr_" . basename($_FILES['mr_images']['name'][$i]);
                $mr_target = $target_dir . $mr_img_name;
                move_uploaded_file($_FILES['mr_images']['tmp_name'][$i], $mr_target);
                $mr_image = $mr_target;
            }

            // Handle MS image upload
            $ms_image = '';
            if (!empty($_FILES['ms_images']['name'][$i])) {
                $ms_img_name = time() . "_ms_" . basename($_FILES['ms_images']['name'][$i]);
                $ms_target = $target_dir . $ms_img_name;
                move_uploaded_file($_FILES['ms_images']['tmp_name'][$i], $ms_target);
                $ms_image = $ms_target;
            }

            if ($mr !== '' && $ms !== '') {
                // Insert MR
                $stmt = $conn->prepare("INSERT INTO contestants (event_id, name, number, image, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("isss", $event_id, $mr, $pair_number, $mr_image);
                $stmt->execute();
                $mr_id = $conn->insert_id;

                // Insert MS
                $stmt->bind_param("isss", $event_id, $ms, $pair_number, $ms_image);
                $stmt->execute();
                $ms_id = $conn->insert_id;

                // Link them as a pair
                $pairStmt = $conn->prepare("INSERT INTO pairs (event_id, mr_id, ms_id) VALUES (?, ?, ?)");
                $pairStmt->bind_param("iii", $event_id, $mr_id, $ms_id);
                $pairStmt->execute();
            }
        }

    } else {
        // For solo / ms gay / etc.
        $solo_names = $_POST['solo_names'] ?? [];
        $numbers = $_POST['contestant_numbers'] ?? [];

        $stmt = $conn->prepare("INSERT INTO contestants (event_id, name, number, image, created_at) VALUES (?, ?, ?, ?, NOW())");

        foreach ($solo_names as $i => $name) {
            $trimmed = trim($name);
            $number = trim($numbers[$i] ?? '');
            $image_path = '';

            if (!empty($_FILES['contestant_images']['name'][$i])) {
                $img_name = time() . "_" . basename($_FILES['contestant_images']['name'][$i]);
                $target_file = $target_dir . $img_name;
                move_uploaded_file($_FILES['contestant_images']['tmp_name'][$i], $target_file);
                $image_path = $target_file;
            }

            if ($trimmed !== '' && $number !== '') {
                $stmt->bind_param("isss", $event_id, $trimmed, $number, $image_path);
                $stmt->execute();
            }
        }
    }

    // ✅ SweetAlert2 Success Message + Redirect
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
Swal.fire({
  icon: 'success',
  title: 'Contestants Added!',
  text: 'All contestants have been added successfully.',
  confirmButtonColor: '#28a745',
  timer: 2500,
  showConfirmButton: false
}).then(() => {
  window.location.href = 'add_criteria.php';
});
</script>
</body>
</html>
HTML;
}
?>
