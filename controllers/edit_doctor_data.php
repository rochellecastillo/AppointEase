<?php
// edit_doctor.php - Update Doctor Profile without email
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Admin Access
session_require_auth(['admin']);

// 1. Get Doctor ID
$doctor_id = $_GET['id'] ?? null;
if (!$doctor_id) {
    header('Location: doctors_info_report.php');
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
        $spec = trim($_POST['specialization']);
        $contact = trim($_POST['contact']);
        $address = trim($_POST['address']);
        $gender = $_POST['gender'];
        $bdate = $_POST['bdate'];

        // Handle Image Upload
        $image_sql = "";
        $params = [$fname, $mname, $lname, $spec, $contact, $address, $gender, $bdate];

        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "doc_" . $doctor_id . "_" . time() . "." . $file_ext;
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
                first_name = ?, middle_name = ?, last_name = ?, specialization = ?, 
                contact = ?, address = ?, gender = ?, bdate = ? 
                $image_sql 
                WHERE user_id = ?";
        
        $params[] = $doctor_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $msg = "Doctor profile updated successfully.";
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
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        die("Doctor not found.");
    }
    
    $stmtUser = $pdo->prepare("SELECT status FROM tbluser WHERE user_id = ?");
    $stmtUser->execute([$doctor_id]);
    $userStatus = $stmtUser->fetchColumn();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>