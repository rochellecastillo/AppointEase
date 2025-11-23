<?php
// verify_otp.php (iProg verify)
session_start();
require 'db.php';
require 'otp_store.php';
require 'iprog_sms.php';

if(!isset($_SESSION['pending_user'])){ header('Location: login.php'); exit; }

$pending = $_SESSION['pending_user'];
$phone = $pending['phone'] ?? null;
$user_id = $pending['user_id'] ?? null;
$otp_rec = $user_id ? get_otp_for_user($user_id) : null;
$expires_iso = $otp_rec ? date('c', $otp_rec['expires']) : date('c', time() + 300);
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $input = trim($_POST['otp'] ?? '');
  if($input === '') $error = "Enter the OTP.";
  else {
    $res = iprog_verify_otp($phone, $input);
    if(!$res['success']){
      $error = "Invalid or expired OTP. Please try again.";
      error_log("[AppointmentEase] iprog_verify_otp failed: " . print_r($res, true));
    } else {
      // verified: create session
      $stmt = $pdo->prepare("SELECT * FROM tbluser WHERE user_id = ? LIMIT 1");
      $stmt->execute([$user_id]);
      $user = $stmt->fetch();

      $_SESSION['user_db_id'] = $user['id'];
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['user_name'] = $user['user_name'];
      $_SESSION['user_type'] = $user['user_type'] ?? 'client';

      unset($_SESSION['pending_user']);
      if($user_id) delete_otp_for_user($user_id);

      header('Location: dashboard.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html>
<head><title>Verify OTP</title></head>
<body>
  <h2>Enter OTP</h2>
  <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
  <p>OTP is valid for <span id="timer">05:00</span></p>

  <form method="post" action="">
    <input name="otp" placeholder="6-digit code" required pattern="\d{4,6}">
    <button type="submit">Verify</button>
  </form>

  <form method="post" action="resend_otp.php">
    <button type="submit" id="resendBtn">Resend OTP</button>
  </form>

  <script>
    let expiresAt = new Date("<?= $expires_iso ?>").getTime();
    const timerEl = document.getElementById('timer');
    const resendBtn = document.getElementById('resendBtn');

    function updateTimer(){
      let now = Date.now();
      let diff = expiresAt - now;
      if(diff <= 0){
        timerEl.textContent = "Expired";
        resendBtn.disabled = false;
        clearInterval(iv);
        return;
      }
      resendBtn.disabled = true;
      let m = Math.floor(diff/60000);
      let s = Math.floor((diff%60000)/1000);
      timerEl.textContent = String(m).padStart(2,'0') + ":" + String(s).padStart(2,'0');
    }
    updateTimer();
    let iv = setInterval(updateTimer, 500);
  </script>
</body>
</html>
