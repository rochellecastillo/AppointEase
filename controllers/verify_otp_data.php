<?php
// verify_otp.php - Universal OTP Verification (Signup & Password Reset Only)
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'iprog_sms.php'; 

// 1. Security: Ensure a process was started
if (!isset($_SESSION['otp_action']) || !isset($_SESSION['otp_payload'])) {
    header('Location: login.php');
    exit;
}

$action = $_SESSION['otp_action'];
$payload = $_SESSION['otp_payload'];
$phone = $payload['contact'] ?? '';

// UI Configuration
$page_title = "Verify Your Account";
$success_msg = "Verification successful!";
$redirect_url = "login.php";
$redirect_btn_text = "Go to Login";

// Configure Page Logic based on Action
switch ($action) {
    case 'add_doctor':
        $page_title = "Verify Doctor Registration";
        $success_msg = "Doctor account created successfully!";
        $redirect_url = "doctors_info_report.php";
        $redirect_btn_text = "Return to Doctor List";
        break;
    case 'add_patient_admin':
        $page_title = "Verify Patient Registration";
        $success_msg = "Patient account created successfully!";
        $redirect_url = "users_list.php.php"; // Or users_list.php
        $redirect_btn_text = "Return to Patient List";
        break;
    case 'forgot_password':
        $page_title = "Verify for Password Reset";
        $success_msg = "Identity verified. Redirecting to password reset...";
        $redirect_url = "reset_new_password.php"; 
        $redirect_btn_text = "Set New Password";
        break;
    case 'signup':
    default:
        $page_title = "Verify Your Account";
        $success_msg = "Account created successfully!";
        $redirect_url = "login.php";
        $redirect_btn_text = "Sign In Now";
        break;
}

// User ID Generator Helper
function generate_user_id($pdo) {
    $base = 'U' . date('ymd');
    $i = 1;
    while (true) {
        $uid = $base . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT 1 FROM tblinfo WHERE user_id = ? LIMIT 1");
        $stmt->execute([$uid]);
        if (!$stmt->fetch()) return $uid;
        $i++;
        if ($i > 9999) throw new Exception("Failed to generate ID");
    }
}

$error = '';
$success = false;
$expires_ts = $payload['otp_expires'] ?? (time() + 300);
$expires_iso = date('c', $expires_ts);

// 2. Handle Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (empty($otp)) {
        $error = "Please enter the OTP code";
    } elseif (time() > $expires_ts) {
        $error = "OTP has expired. Please request a new one";
    } else {
        
        // A. Verify OTP
        $verification = iprog_verify_otp($phone, $otp);

        if ($verification['success']) {
            
            // B. Perform Action
            try {
                $pdo->beginTransaction();

                if ($action === 'forgot_password') {
                    // --- CASE 1: FORGOT PASSWORD ---
                    $_SESSION['allow_password_reset'] = true;
                    $_SESSION['reset_user_id'] = $payload['user_id'];
                    
                    unset($_SESSION['otp_action']);
                    unset($_SESSION['otp_payload']);
                    
                    header('Location: reset_new_password.php');
                    exit;

                } elseif ($action === 'signup') {
                    // --- CASE 2: PATIENT SIGNUP ---
                    $user_id = generate_user_id($pdo);
                    
                    // Insert Info
                    $stmt = $pdo->prepare("INSERT INTO tblinfo (user_id, last_name, first_name, middle_name, bdate, gender, address, contact, specialization, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $payload['last_name'], $payload['first_name'], $payload['middle_name'], $payload['bdate'], $payload['gender'], $payload['address'], $payload['contact'], '', '']);
                    
                    // Insert User
                    $stmt = $pdo->prepare("INSERT INTO tbluser (user_id, user_name, password, user_type, status) VALUES (?, ?, ?, 'user', 1)");
                    $stmt->execute([$user_id, $payload['user_name'], $payload['password']]);
                }

                $pdo->commit();
                
                // Cleanup
                unset($_SESSION['otp_action']);
                unset($_SESSION['otp_payload']);
                
                $success = true;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "System Error: " . $e->getMessage();
            }

        } else {
            $error = "Invalid OTP Code. Please try again.";
        }
    }
}
?>