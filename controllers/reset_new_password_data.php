<?php
// reset_new_password.php - UI matched with Forgot Password & Signup
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

// 1. Security Check: Ensure OTP was verified
if (!isset($_SESSION['allow_password_reset']) || $_SESSION['allow_password_reset'] !== true || !isset($_SESSION['reset_user_id'])) {
    header('Location: forgot_password.php');
    exit;
}

$user_id = $_SESSION['reset_user_id'];
$error = '';
$success = false;
$user_display_name = "User";

// 2. Fetch User Details for Display
try {
    $stmt = $pdo->prepare("SELECT user_name FROM tbluser WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    if ($u) $user_display_name = $u['user_name'];
} catch (Exception $e) { /* Ignore display error */ }

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Basic Validation
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please enter both password fields";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Password Strength Validation
        if (strlen($new_password) < 8 || 
            !preg_match("/[A-Z]/", $new_password) || 
            !preg_match("/[a-z]/", $new_password) || 
            !preg_match("/[0-9]/", $new_password)) {
            $error = "Password must be 8+ chars with uppercase, lowercase, and a number.";
        } else {
            try {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update database
                $stmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                // Log and Clear Session
                // log_security_event('password_reset_completed', ['user_id' => $user_id]);
                
                unset($_SESSION['allow_password_reset']);
                unset($_SESSION['reset_user_id']);
                
                $success = true;
                
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>