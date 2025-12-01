<?php
// add_patient.php - Admin: Add Patient with OTP Verification
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';
require_once 'iprog_sms.php';

// Enforce Admin Access
session_require_auth(['admin']);

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Collect & Sanitize Inputs
        $fname = trim($_POST['first_name']);
        $mname = trim($_POST['middle_name']); 
        $lname = trim($_POST['last_name']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $contact = trim($_POST['contact']);
        $gender = $_POST['gender'];
        $bdate = $_POST['bdate'];
        $address = trim($_POST['address']);
        
        // Normalize contact
        $contact_norm = normalize_phone_ph($contact);

        // 2. Validation
        if (empty($fname) || empty($lname) || empty($username) || empty($password) || empty($contact)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Check for duplicate username
        $stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE user_name = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) throw new Exception("Username is already taken.");

        // Check for duplicate contact
        $stmt = $pdo->prepare("SELECT id FROM tblinfo WHERE contact = ?");
        $stmt->execute([$contact_norm]);
        if ($stmt->rowCount() > 0) throw new Exception("Contact number already registered.");

        // 3. Handle Image (Optional)
        $image_filename = null;
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed)) throw new Exception("Invalid file format (JPG, PNG, GIF only).");

            $new_filename = "pat_" . time() . "." . $file_ext;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_filename)) {
                $image_filename = $new_filename;
            } else {
                throw new Exception("Failed to upload image.");
            }
        }

        // 4. Setup OTP Session (email removed)
        $_SESSION['otp_action'] = 'add_patient_admin';
        $_SESSION['otp_payload'] = [
            'first_name' => $fname,
            'middle_name' => $mname,
            'last_name' => $lname,
            'contact' => $contact_norm,
            'address' => $address,
            'gender' => $gender,
            'bdate' => $bdate,
            'image' => $image_filename,
            'username' => $username,
            'password' => $password, 
            'otp_expires' => time() + (5 * 60)
        ];

        // 5. Send OTP
        $otp_res = iprog_send_otp($contact_norm);
        if ($otp_res['success']) {
            header("Location: verify_otp.php");
            exit();
        } else {
            if($image_filename && file_exists("uploads/$image_filename")) unlink("uploads/$image_filename");
            throw new Exception("Failed to send OTP. Check connection/credits.");
        }

    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msg_type = "error";
    }
}
?>