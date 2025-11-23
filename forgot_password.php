<?php
// forgot_password.php - Request password reset
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'iprog_sms.php';

// Redirect if already logged in
if (session_is_logged_in()) {
    header('Location: ' . (session_get_user_type() === 'admin' ? 'admin_home.php' : 
           (session_get_user_type() === 'doctor' ? 'doctor_home.php' : 'client_home.php')));
    exit;
}

$error = '';
$step = 1; // Step 1: Enter username/contact, Step 2: OTP sent

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'request_reset') {
        $username_or_contact = trim($_POST['username_or_contact'] ?? '');
        
        // Rate limiting
        $rate_check = check_rate_limit('forgot_password_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 3, 900);
        
        if (!$rate_check['allowed']) {
            $minutes = ceil($rate_check['retry_after'] / 60);
            $error = "Too many password reset attempts. Please try again in {$minutes} minutes.";
            log_security_event('forgot_password_rate_limit', ['input' => $username_or_contact]);
        } elseif (empty($username_or_contact)) {
            $error = 'Please enter your username or contact number';
        } else {
            try {
                // Search for user by username or contact number
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.user_name, i.first_name, i.last_name, i.contact
                    FROM tbluser u
                    LEFT JOIN tblinfo i ON u.user_id = i.user_id
                    WHERE (u.user_name = ? OR i.contact = ?) AND u.status = 1
                    LIMIT 1
                ");
                
                $normalized_contact = normalize_phone_ph($username_or_contact);
                $stmt->execute([$username_or_contact, $normalized_contact]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && !empty($user['contact'])) {
                    // Store reset request in session
                    $_SESSION['password_reset'] = [
                        'user_id' => $user['user_id'],
                        'user_name' => $user['user_name'],
                        'contact' => $user['contact'],
                        'full_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                        'otp_expires' => time() + (5 * 60),
                        'attempts' => 0
                    ];
                    
                    // Send OTP
                    $res = iprog_send_otp($user['contact'], null);
                    
                    if (!$res['success']) {
                        error_log("[AppointmentEase] Forgot password OTP send failed: " . print_r($res, true));
                        $error = "Failed to send OTP. Please try again later.";
                        unset($_SESSION['password_reset']);
                        log_security_event('forgot_password_otp_send_failed', ['user_id' => $user['user_id']]);
                    } else {
                        log_security_event('forgot_password_otp_sent', ['user_id' => $user['user_id']]);
                        header('Location: verify_reset_otp.php');
                        exit;
                    }
                } else {
                    // Don't reveal if user exists or not (security)
                    $error = "If this account exists, an OTP will be sent to the registered mobile number.";
                    log_security_event('forgot_password_user_not_found', ['input' => $username_or_contact]);
                    
                    // Add delay to prevent timing attacks
                    sleep(2);
                }
            } catch (Exception $e) {
                $error = "An error occurred. Please try again later.";
                log_security_event('forgot_password_error', ['error' => $e->getMessage()]);
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
    <title>Forgot Password - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <i data-lucide="lock" class="text-purple-600 w-8 h-8"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Forgot Password?</h1>
            <p class="text-gray-600">Don't worry, we'll help you reset it</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-start">
                <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Info Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <i data-lucide="info" class="text-blue-600 w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                <div class="text-sm text-blue-700">
                    <p class="font-semibold mb-1">How password reset works:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Enter your username or mobile number</li>
                        <li>We'll send an OTP to your registered mobile</li>
                        <li>Verify the OTP and create a new password</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="action" value="request_reset">
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2 flex items-center">
                    <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                    Username or Mobile Number
                </label>
                <input type="text" name="username_or_contact" required autofocus
                       value="<?= e($_POST['username_or_contact'] ?? '') ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                       placeholder="Enter username or 09XXXXXXXXX">
                <p class="text-xs text-gray-500 mt-2">
                    Enter the username or mobile number associated with your account
                </p>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-3 rounded-lg transition duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center justify-center">
                <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                Send Reset Code
            </button>
        </form>

        <!-- Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or</span>
            </div>
        </div>

        <!-- Back to Login -->
        <div class="text-center space-y-3">
            <a href="login.php" 
               class="inline-flex items-center text-purple-600 hover:text-purple-700 font-semibold">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                Back to Login
            </a>
            
            <div class="text-sm text-gray-600">
                Don't have an account? 
                <a href="signup.php" class="text-purple-600 hover:text-purple-700 font-semibold">Sign up</a>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-start">
                <i data-lucide="shield-check" class="text-green-600 w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                <div class="text-xs text-gray-600">
                    <p class="font-semibold text-gray-700 mb-1">Security Notice</p>
                    <p>For your security, password reset links expire after 5 minutes. If you didn't request this, please ignore this page.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.replace();
    </script>
</body>
</html>