<?php
// resend_signup_otp.php
session_start();
require 'iprog_sms.php';
require 'otp_store.php';

if(!isset($_SESSION['pending_signup'])){
  header('Location: signup.php'); exit;
}
$phone = $_SESSION['pending_signup']['contact'] ?? null;
if(!$phone) { header('Location: signup.php'); exit; }

// rate limit: 3 per hour (session-based)
$now = time();
if(!isset($_SESSION['signup_otp_log'][$phone])) $_SESSION['signup_otp_log'][$phone] = [];
// purge older than 1 hour
$_SESSION['signup_otp_log'][$phone] = array_filter($_SESSION['signup_otp_log'][$phone], function($t) use($now){ return ($now - $t) < 3600; });
if(count($_SESSION['signup_otp_log'][$phone]) >= 3){
  die('Too many resend requests. Try again later.');
}

// send via iProg
$res = iprog_send_otp($phone, null);
if(!$res['success']){
  error_log("[AppointmentEase] resend_signup_iprog failed: " . print_r($res, true));
  die('Failed to resend OTP. Try again later.');
}

// update expiry and log
$expires_ts = time() + (5 * 60);
$_SESSION['pending_signup']['otp_expires'] = $expires_ts;
$_SESSION['signup_otp_log'][$phone][] = $now;

header('Location: verify_signup_otp.php');
exit;
