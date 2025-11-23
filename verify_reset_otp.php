<?php
// verify_reset_otp.php - Verify OTP for password reset
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'iprog_sms.php';

if (!isset($_SESSION['password_reset'])) {
    header('Location: forgot_password.php');
    exit;
}

$reset_data = $_SESSION['password_reset'];
$contact = $reset_data['contact'];
$expires_ts = $reset_data['otp_expires'] ?? (time() + 300);
$expires_iso = date('c', $expires_ts);
$error = '';

// Maximum OTP attempts
$max_attempts = 5;
$remaining_attempts = $max_attempts - ($reset_data['attempts'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($otp)) {
        $error = "Please enter the OTP code";
    } elseif (time() > $expires_ts) {
        $error = "OTP has expired. Please request a new one";
    } elseif ($remaining_attempts <= 0) {
        $error = "Too many failed attempts. Please request a new OTP";
        unset($_SESSION['password_reset']);
        log_security_event('password_reset_max_attempts', ['user_id' => $reset_data['user_id']]);
    } else {
        // Verify OTP
        $res = iprog_verify_otp($contact, $otp);
        
        if (!$res['success']) {
            $_SESSION['password_reset']['attempts'] = ($reset_data['attempts'] ?? 0) + 1;
            $remaining_attempts--;
            $error = "Invalid OTP code. {$remaining_attempts} attempts remaining";
            log_security_event('password_reset_otp_failed', ['user_id' => $reset_data['user_id']]);
        } else {
            // OTP verified - proceed to reset password
            $_SESSION['password_reset']['verified'] = true;
            log_security_event('password_reset_otp_verified', ['user_id' => $reset_data['user_id']]);
            header('Location: reset_password.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Password Reset</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <i data-lucide="smartphone" class="text-purple-600 w-8 h-8"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Verify OTP</h1>
            <p class="text-gray-600">Enter the code sent to your mobile</p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-green-500 text-white rounded-full">
                    <i data-lucide="check" class="w-6 h-6"></i>
                </div>
                <span class="ml-2 text-sm font-medium text-gray-600">Request</span>
            </div>
            <div class="w-16 h-1 bg-purple-600 mx-4"></div>
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-purple-600 text-white rounded-full font-semibold">2</div>
                <span class="ml-2 text-sm font-semibold text-purple-600">Verify</span>
            </div>
            <div class="w-16 h-1 bg-gray-300 mx-4"></div>
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-gray-300 text-gray-600 rounded-full font-semibold">3</div>
                <span class="ml-2 text-sm font-medium text-gray-500">Reset</span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-start">
                <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- User Info -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <i data-lucide="user" class="text-purple-600 w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                <div class="flex-1">
                    <p class="text-sm text-purple-700 font-semibold">Resetting password for:</p>
                    <p class="text-lg font-bold text-purple-900"><?= e($reset_data['full_name']) ?></p>
                    <p class="text-sm text-purple-700 mt-1">OTP sent to: <?= e($contact) ?></p>
                </div>
            </div>
        </div>

        <!-- Timer Display -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center bg-gray-100 rounded-full px-4 py-2">
                <i data-lucide="clock" class="text-gray-600 w-4 h-4 mr-2"></i>
                <span class="text-sm text-gray-600">Code expires in: </span>
                <span id="timer" class="ml-2 font-bold text-purple-600">05:00</span>
            </div>
        </div>

        <!-- Attempts Remaining -->
        <?php if ($remaining_attempts < $max_attempts): ?>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-6">
                <div class="flex items-center">
                    <i data-lucide="alert-triangle" class="text-orange-600 w-5 h-5 mr-2"></i>
                    <span class="text-sm text-orange-700 font-semibold">
                        <?= $remaining_attempts ?> verification attempts remaining
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- OTP Form -->
        <form method="POST" action="" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3 text-center">
                    Enter 4-6 Digit OTP Code
                </label>
                <input type="text" name="otp" id="otp" required
                       pattern="\d{4,6}"
                       maxlength="6"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       class="w-full text-center text-3xl font-bold tracking-widest px-4 py-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="______">
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-4 rounded-lg transition duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                Verify OTP
            </button>
        </form>

        <!-- Resend OTP -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 mb-3">Didn't receive the code?</p>
            <form method="POST" action="resend_reset_otp.php" class="inline">
                <button type="submit"
                        class="text-purple-600 hover:text-purple-700 font-semibold text-sm flex items-center justify-center mx-auto">
                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                    Resend OTP
                </button>
            </form>
        </div>

        <!-- Cancel -->
        <div class="mt-6 text-center">
            <a href="forgot_password.php" class="text-sm text-gray-600 hover:text-gray-800">
                ← Cancel and start over
            </a>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.replace();
        
        // Countdown timer
        let expiresAt = new Date("<?= $expires_iso ?>").getTime();
        const timerEl = document.getElementById('timer');
        
        function updateTimer() {
            const now = Date.now();
            const diff = expiresAt - now;
            
            if (diff <= 0) {
                timerEl.textContent = "Expired";
                timerEl.classList.add('text-red-600');
                timerEl.classList.remove('text-purple-600');
                clearInterval(timerInterval);
                return;
            }
            
            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            timerEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            if (diff < 60000) {
                timerEl.classList.add('text-orange-600');
                timerEl.classList.remove('text-purple-600');
            }
        }
        
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
        
        // Auto-focus OTP input
        document.getElementById('otp').focus();
        
        // Format OTP input
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>