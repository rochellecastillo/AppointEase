<?php
// resend_otp.php - Resend OTP for Universal Verification
require_once 'session_handler.php';
require_once 'iprog_sms.php';

// 1. Security Check
if (!isset($_SESSION['otp_action']) || !isset($_SESSION['otp_payload'])) {
    header('Location: login.php');
    exit;
}

$payload = $_SESSION['otp_payload'];
$phone = $payload['contact'] ?? '';

// 2. Rate Limiting (Optional but recommended)
// Limit resends to once every 60 seconds
if (isset($_SESSION['last_otp_sent']) && (time() - $_SESSION['last_otp_sent'] < 60)) {
    // Too fast
    echo "<script>alert('Please wait before requesting another code.'); window.history.back();</script>";
    exit;
}

// 3. Resend OTP
if (!empty($phone)) {
    $res = iprog_send_otp($phone);
    
    if ($res['success']) {
        // Update expiration and last sent time
        $_SESSION['otp_payload']['otp_expires'] = time() + (5 * 60); // Reset timer to 5 mins
        $_SESSION['last_otp_sent'] = time();
        
        // Redirect back with success flag (optional, or just reload)
        header("Location: verify_otp.php?resent=1");
        exit;
    } else {
        // Log error
        error_log("[Resend OTP] Failed for $phone: " . print_r($res, true));
        echo "<script>alert('Failed to resend OTP. Please try again later.'); window.history.back();</script>";
        exit;
    }
} else {
    // Error: Contact number missing from session
    header("Location: login.php");
    exit;
}
?>