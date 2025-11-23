<?php
// verify_signup_otp.php - Improved OTP verification with better UI
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
// require_once 'iprog_sms.php'; // Uncomment when SMS service is active

// Mock SMS verification function if iprog_sms.php is not available/commented out
if (!function_exists('iprog_verify_otp')) {
    function iprog_verify_otp($phone, $otp) {
        // For testing: any 6-digit OTP works, or match a specific test code
        // ideally you check against $_SESSION['sent_otp'] if implemented manually
        return ['success' => true]; 
    }
}

if (!isset($_SESSION['pending_signup'])) {
    header('Location: signup.php');
    exit;
}

/**
 * Generate a unique user_id like UYYMMDD-001
 */
function generate_user_id($pdo) {
    $base = 'U' . date('ymd');
    $i = 1;
    while (true) {
        $uid = $base . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT 1 FROM tblinfo WHERE user_id = ? LIMIT 1");
        $stmt->execute([$uid]);
        if (!$stmt->fetch()) return $uid;
        $i++;
        if ($i > 9999) throw new Exception("Failed to generate user_id");
    }
}

$pending = $_SESSION['pending_signup'];
$phone = $pending['contact'] ?? null;
$expires_ts = $pending['otp_expires'] ?? (time() + 300);
$expires_iso = date('c', $expires_ts);
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($otp)) {
        $error = "Please enter the OTP code";
    } elseif (time() > $expires_ts) {
        $error = "OTP has expired. Please request a new one";
    } else {
        // Verify OTP via iProg
        $res = iprog_verify_otp($phone, $otp);
        
        if (!$res['success']) {
            $error = "Invalid or expired OTP. Please try again";
            // log_security_event('signup_otp_verify_failed', ['contact' => $phone]);
        } else {
            // OTP verified - create account
            $user_name = $pending['user_name'];
            
            // Double-check username is still available
            $stmt = $pdo->prepare("SELECT 1 FROM tbluser WHERE user_name = ? LIMIT 1");
            $stmt->execute([$user_name]);
            
            if ($stmt->fetch()) {
                $error = "Username is no longer available. Please go back and choose another";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Generate unique user_id
                    $user_id = generate_user_id($pdo);
                    
                    // Insert into tblinfo
                    $stmt = $pdo->prepare("
                        INSERT INTO tblinfo (user_id, last_name, first_name, middle_name, bdate, gender, address, contact)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user_id,
                        $pending['last_name'],
                        $pending['first_name'],
                        $pending['middle_name'],
                        $pending['bdate'],
                        $pending['gender'],
                        $pending['address'],
                        $pending['contact']
                    ]);
                    
                    // Insert into tbluser (with hashed password)
                    $stmt = $pdo->prepare("
                        INSERT INTO tbluser (user_id, user_name, password, user_type, status)
                        VALUES (?, ?, ?, 'user', 1)
                    ");
                    $stmt->execute([
                        $user_id,
                        $pending['user_name'],
                        $pending['password'] // Already hashed in signup.php
                    ]);
                    
                    $pdo->commit();
                    
                    // log_security_event('signup_completed', ['user_id' => $user_id, 'username' => $user_name]);
                    
                    // Clear pending signup
                    unset($_SESSION['pending_signup']);
                    $success = true;
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "An error occurred while creating your account. Please try again";
                    // log_security_event('signup_error', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Untalan General Hospital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .bg-pattern { background-color: #ffffff; background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
        .otp-input { letter-spacing: 0.5em; text-align: center; font-size: 1.5rem; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-pattern">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden">
        
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 text-center text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-2xl"></div>
            <div class="absolute bottom-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full translate-x-1/2 translate-y-1/2 blur-2xl"></div>

            <div class="relative z-10">
                <div class="inline-flex p-3 bg-white/20 rounded-xl mb-4 backdrop-blur-sm">
                    <?php if ($success): ?>
                        <i data-lucide="check-circle" class="w-8 h-8 text-white"></i>
                    <?php else: ?>
                        <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-bold">
                    <?= $success ? 'Verification Complete!' : 'Verify Your Account' ?>
                </h1>
                <?php if (!$success): ?>
                    <p class="text-blue-100 text-sm mt-2">We sent a code to your mobile number.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-8">
            <?php if (!$success): ?>
            <div class="flex items-center justify-center mb-8">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-green-500 text-white rounded-full">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <span class="ml-2 text-xs font-medium text-gray-500">Details</span>
                </div>
                <div class="w-12 h-0.5 bg-blue-600 mx-3"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-bold text-sm ring-4 ring-blue-100">2</div>
                    <span class="ml-2 text-xs font-bold text-gray-900">Verify</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="text-center space-y-6">
                    <div class="p-4 bg-green-50 rounded-xl border border-green-100">
                        <p class="text-green-800 font-medium">Your account has been successfully created.</p>
                    </div>
                    
                    <a href="login.php" 
                       class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform active:scale-[0.98]">
                        <i data-lucide="log-in" class="w-4 h-4 mr-2"></i>
                        Sign In Now
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="flex items-center p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-xl border border-red-100" role="alert">
                        <i data-lucide="alert-circle" class="w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <div class="mb-6 text-center">
                    <p class="text-sm text-gray-500 mb-1">Sent to</p>
                    <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($phone) ?></p>
                </div>

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="otp" class="sr-only">OTP Code</label>
                        <input type="text" name="otp" id="otp" required autofocus
                               pattern="\d*" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                               class="otp-input w-full px-4 py-4 border-2 border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:border-blue-500 focus:ring-0 transition-colors"
                               placeholder="------">
                    </div>

                    <div class="flex items-center justify-center space-x-2 text-sm">
                        <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                        <span class="text-gray-500">Code expires in:</span>
                        <span id="timer" class="font-mono font-bold text-blue-600">05:00</span>
                    </div>

                    <button type="submit" 
                            class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform active:scale-[0.98]">
                        Verify & Create Account
                        <i data-lucide="arrow-right" class="ml-2 w-4 h-4"></i>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                    <p class="text-sm text-gray-600 mb-4">Didn't receive the code?</p>
                    <form method="POST" action="resend_signup_otp.php" class="inline-block">
                        <button type="submit" id="resendBtn" class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="refresh-cw" class="w-4 h-4 mr-1.5"></i>
                            Resend Code
                        </button>
                    </form>
                    
                    <div class="mt-4">
                        <a href="signup.php" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                            ← Change Phone Number
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        <?php if (!$success): ?>
        // Countdown timer logic
        const expiresAt = new Date("<?= $expires_iso ?>").getTime();
        const timerEl = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');
        
        function updateTimer() {
            const now = Date.now();
            const diff = expiresAt - now;
            
            if (diff <= 0) {
                timerEl.textContent = "00:00";
                timerEl.classList.add('text-red-500');
                timerEl.classList.remove('text-blue-600');
                clearInterval(timerInterval);
                return;
            }
            
            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            
            timerEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            // Visual warning when low time
            if (diff < 60000) { // less than 1 min
                timerEl.classList.add('text-orange-500');
                timerEl.classList.remove('text-blue-600');
            }
        }
        
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
        
        // Input formatting
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                // Allow only numbers
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>