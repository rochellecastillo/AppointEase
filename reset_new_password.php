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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .bg-pattern { background-color: #ffffff; background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
        .input-field:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-pattern">

    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden grid md:grid-cols-2 min-h-[550px]">
        
        <div class="hidden md:flex flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-indigo-700 text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full translate-x-1/2 translate-y-1/2 blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                    </div>
                    <span class="text-xl font-bold tracking-wide">Security</span>
                </div>
                
                <h1 class="text-3xl font-bold leading-tight mb-4">Secure Your Account</h1>
                <p class="text-blue-100 text-lg leading-relaxed mb-6">Create a strong, unique password to protect your medical data.</p>
                
                <div class="bg-white/10 p-4 rounded-xl border border-white/20 backdrop-blur-sm">
                    <div class="flex items-start space-x-3">
                        <i data-lucide="lightbulb" class="w-5 h-5 mt-1 text-yellow-300"></i>
                        <div class="text-sm text-blue-50">
                            <p class="font-bold mb-1">Password Tip:</p>
                            <p>Use a phrase only you know, mixed with numbers and symbols for better security.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="relative z-10 mt-auto text-sm text-blue-200">
                Authentication secured by AppointEase
            </div>
        </div>

        <div class="p-8 md:p-12 flex flex-col justify-center">
            
            <div class="md:hidden mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800">AppointEase</h2>
                <p class="text-gray-500">Security Update</p>
            </div>

            <div class="w-full">
                
                <?php if ($success): ?>
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6 animate-bounce">
                            <i data-lucide="check" class="text-green-600 w-10 h-10"></i>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Password Reset!</h2>
                        <p class="text-gray-600 mb-8">Your account has been successfully updated. You can now log in with your new credentials.</p>
                        
                        <a href="login.php" 
                           class="inline-flex w-full items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl transition duration-200 shadow-lg transform active:scale-95">
                            <i data-lucide="log-in" class="w-5 h-5 mr-2"></i>
                            Back to Login
                        </a>
                    </div>

                <?php else: ?>
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Set New Password</h2>
                        <div class="flex items-center mt-2 text-sm text-gray-500">
                            <span>Resetting for:</span>
                            <span class="ml-2 px-2 py-0.5 bg-blue-50 text-blue-700 font-semibold rounded-md border border-blue-100">
                                <?= htmlspecialchars($user_display_name) ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                            <div class="flex items-start">
                                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0"></i>
                                <span class="text-sm text-red-700"><?= $error ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Password must contain:</p>
                        <ul class="grid grid-cols-2 gap-2 text-xs text-gray-500">
                            <li id="req-len" class="flex items-center"><i data-lucide="circle" class="w-3 h-3 mr-2"></i> 8+ Characters</li>
                            <li id="req-up" class="flex items-center"><i data-lucide="circle" class="w-3 h-3 mr-2"></i> Uppercase</li>
                            <li id="req-low" class="flex items-center"><i data-lucide="circle" class="w-3 h-3 mr-2"></i> Lowercase</li>
                            <li id="req-num" class="flex items-center"><i data-lucide="circle" class="w-3 h-3 mr-2"></i> Number</li>
                        </ul>
                    </div>

                    <form method="POST" action="" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <div class="relative">
                                <input type="password" name="new_password" id="new_password" required autofocus
                                    class="input-field w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none transition-colors pr-10"
                                    placeholder="••••••••">
                                <button type="button" onclick="togglePassword('new_password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-600">
                                    <i data-lucide="eye" id="eye-new_password" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <input type="password" name="confirm_password" id="confirm_password" required
                                    class="input-field w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none transition-colors pr-10"
                                    placeholder="••••••••">
                                <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-600">
                                    <i data-lucide="eye" id="eye-confirm_password" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <p id="match-msg" class="text-xs mt-2 hidden flex items-center font-medium"></p>
                        </div>

                        <button type="submit" 
                            class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-200 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform active:scale-[0.98]">
                            <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                            Update Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.getElementById('eye-' + id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Real-time Validation Logic
        const pass = document.getElementById('new_password');
        const conf = document.getElementById('confirm_password');
        
        if(pass && conf) {
            pass.addEventListener('input', validate);
            conf.addEventListener('input', checkMatch);
        }

        function validate() {
            const v = pass.value;
            updateCheck('req-len', v.length >= 8);
            updateCheck('req-up', /[A-Z]/.test(v));
            updateCheck('req-low', /[a-z]/.test(v));
            updateCheck('req-num', /[0-9]/.test(v));
            checkMatch();
        }

        function updateCheck(id, valid) {
            const el = document.getElementById(id);
            const icon = el.querySelector('i'); 
            
            if (valid) {
                el.classList.remove('text-gray-500');
                el.classList.add('text-green-600', 'font-medium');
                icon.setAttribute('data-lucide', 'check-circle');
                icon.classList.add('text-green-600');
            } else {
                el.classList.add('text-gray-500');
                el.classList.remove('text-green-600', 'font-medium');
                icon.setAttribute('data-lucide', 'circle');
                icon.classList.remove('text-green-600');
            }
            lucide.createIcons();
        }

        function checkMatch() {
            const msg = document.getElementById('match-msg');
            if (!conf.value) {
                msg.classList.add('hidden');
                return;
            }
            msg.classList.remove('hidden');
            if (pass.value === conf.value) {
                msg.innerHTML = '<i data-lucide="check" class="w-3 h-3 mr-1"></i> Passwords match';
                msg.className = "text-xs mt-2 flex items-center font-medium text-green-600";
            } else {
                msg.innerHTML = '<i data-lucide="x" class="w-3 h-3 mr-1"></i> Passwords do not match';
                msg.className = "text-xs mt-2 flex items-center font-medium text-red-500";
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>