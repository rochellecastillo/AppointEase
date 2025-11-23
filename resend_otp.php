<?php
// resend_otp.php (iProg)
session_start();
require 'db.php';
require 'otp_store.php';
require 'iprog_sms.php';

if(!isset($_SESSION['pending_user'])){ header('Location: login.php'); exit; }
$pending = $_SESSION['pending_user'];
$phone = $pending['phone'] ?? null;
$user_id = $pending['user_id'] ?? null;
if(!$phone) die('No phone to resend OTP to.');

// session-based rate-limit: 3 per hour
$now = time();
if(!isset($_SESSION['otp_send_log'][$phone])) $_SESSION['otp_send_log'][$phone] = [];
$_SESSION['otp_send_log'][$phone] = array_filter($_SESSION['otp_send_log'][$phone], function($t) use($now){
  return ($now - $t) < 3600;
});
if(count($_SESSION['otp_send_log'][$phone]) >= 3) die('Too many OTP requests. Try later.');

// send
$res = iprog_send_otp($phone, null);
if(!$res['success']){
  error_log("[AppointmentEase] iprog resend failed: " . print_r($res, true));
  die('Failed to resend OTP. Try again later.');
}
$expires_ts = time() + (5 * 60);
if($user_id) set_otp_for_user($user_id, 'PROVIDER_MANAGED', $expires_ts);
$_SESSION['otp_send_log'][$phone][] = $now;
header('Location: verify_otp.php');
exit;
