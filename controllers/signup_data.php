<?php
// signup.php - Patient Registration linked to 'appointment.sql' schema
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'iprog_sms.php'; // SMS Helper

// Redirect if already logged in
if (session_is_logged_in()) {
    header('Location: ' . (session_get_user_type() === 'admin' ? 'admin_home.php' : 
           (session_get_user_type() === 'doctor' ? 'doctor_home.php' : 'client_home.php')));
    exit;
}

$errors = [];
$prefill = $_POST ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_signup') {
    // 1. Collect & Sanitize Inputs
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $bdate = trim($_POST['bdate'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $user_name = trim($_POST['user_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $terms_accepted = isset($_POST['terms_accepted']);

    // 2. Validate Required Fields
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($bdate)) $errors[] = "Birth date is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($contact)) $errors[] = "Contact number is required";
    if (empty($user_name)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (!$terms_accepted) $errors[] = "You must accept the terms and conditions";

    // 3. Database Schema Validation (Based on appointment.sql)
    // tblinfo constraints: last_name(40), first_name(50), middle_name(40), address(200)
    if (strlen($last_name) > 40) $errors[] = "Last name is too long (Max 40 characters)";
    if (strlen($first_name) > 50) $errors[] = "First name is too long (Max 50 characters)";
    if (strlen($middle_name) > 40) $errors[] = "Middle name is too long (Max 40 characters)";
    if (strlen($address) > 200) $errors[] = "Address is too long (Max 200 characters)";
    
    // tbluser constraints: user_name(30)
    if (strlen($user_name) > 30) $errors[] = "Username is too long (Max 30 characters)";

    // 4. Validate Logic (Phone, Password, Age)
    $contact_norm = normalize_phone_ph($contact); 
    // Assuming normalize_phone_ph returns format 09xxxxxxxxx. If not, implement standard normalization.
    if (!preg_match('/^(09|\+639)\d{9}$/', $contact_norm)) {
         $errors[] = "Invalid Philippine mobile number format (e.g., 09171234567)";
    }

    if (empty($errors)) {
        // Simple password strength check
        if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
            $errors[] = "Password must be at least 8 chars and contain 1 uppercase letter and 1 number.";
        }
    }

    if (!empty($bdate)) {
        $birth_date = new DateTime($bdate);
        $now = new DateTime();
        $age = $now->diff($birth_date)->y;
        if ($age < 13) $errors[] = "You must be at least 13 years old to register";
    }

    // 5. Check Availability (Username & Contact)
    if (empty($errors)) {
        // Check tbluser for username
        $stmt = $pdo->prepare("SELECT 1 FROM tbluser WHERE user_name = ? LIMIT 1");
        $stmt->execute([$user_name]);
        if ($stmt->fetch()) $errors[] = "Username already taken.";

        // // Check tblinfo for contact
        // $stmt = $pdo->prepare("SELECT 1 FROM tblinfo WHERE contact = ? LIMIT 1");
        // $stmt->execute([$contact_norm]);
        // if ($stmt->fetch()) $errors[] = "This contact number is already registered.";
    }

    // 6. Process Signup -> Send to Universal OTP
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare Universal Session Payload
        $_SESSION['otp_action'] = 'signup';
        $_SESSION['otp_payload'] = [
            // Info Table
            'last_name'   => $last_name,
            'first_name'  => $first_name,
            'middle_name' => $middle_name,
            'bdate'       => $bdate,
            'gender'      => $gender,
            'address'     => $address,
            'contact'     => $contact_norm,
            'specialization' => '', // REQUIRED by DB (NOT NULL), implies 'Patient'
            'image'       => '',    // Matches your DB default for new users
            
            // User Table
            'user_name'   => $user_name,
            'password'    => $hashed_password,
            'otp_expires' => time() + (5 * 60)
        ];

        // Send OTP
        $res = iprog_send_otp($contact_norm);
        
        if ($res['success']) {
            header('Location: verify_otp.php'); // Universal OTP Page
            exit;
        } else {
            $errors[] = "Failed to send OTP. Please try again.";
            error_log("OTP Send Error: " . print_r($res, true));
        }
    }
}
?>