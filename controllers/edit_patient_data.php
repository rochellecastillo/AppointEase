<?php
// edit_patient.php - Update Patient Profile without email
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Admin Access
session_require_auth(['admin']);

// 1. Get Patient ID
$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    header('Location: users_list.php');
    exit;
}

// 2. Handle Form Submission
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect inputs
        $fname = trim($_POST['first_name']);
        $mname = trim($_POST['middle_name']);
        $lname = trim($_POST['last_name']);
        $contact = trim($_POST['contact']);
        $address = trim($_POST['address']);
        $gender = $_POST['gender'];
        $bdate = $_POST['bdate'];

        // Handle Image Upload
        $image_sql = "";
        $params = [$fname, $mname, $lname, $contact, $address, $gender, $bdate];

        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "pat_" . $patient_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_filename;

            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($file_ext, $allowed)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_sql = ", image = ?";
                    $params[] = $new_filename;
                } else {
                    throw new Exception("Failed to upload image.");
                }
            } else {
                throw new Exception("Invalid file format. Only JPG, PNG & GIF allowed.");
            }
        }

        // Update Query without email
        $sql = "UPDATE tblinfo SET 
                first_name = ?, middle_name = ?, last_name = ?, 
                contact = ?, address = ?, gender = ?, bdate = ? 
                $image_sql 
                WHERE user_id = ?";
        
        $params[] = $patient_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $msg = "Patient profile updated successfully.";
            $msg_type = "success";
        } else {
            $msg = "Failed to update database.";
            $msg_type = "error";
        }

    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "error";
    }
}

// 3. Fetch Existing Data
try {
    $stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Patient not found.");
    }
    
    $stmtUser = $pdo->prepare("SELECT status FROM tbluser WHERE user_id = ?");
    $stmtUser->execute([$patient_id]);
    $userStatus = $stmtUser->fetchColumn();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>