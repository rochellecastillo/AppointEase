<?php
// reset_password.php - Set new password after OTP verification
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

if (!isset($_SESSION['password_reset']) || !isset($_SESSION['password_reset']['verified']) || $_SESSION['password_reset']['verified'] !== true) {
    header('Location: forgot_password.php');
    exit;
}

$reset_data = $_SESSION['password_reset'];
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please enter both password fields";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Validate password strength
        $password_validation = validate_password_strength($new_password);
        
        if (!$password_validation['valid']) {
            $error = implode('<br>', $password_validation['errors']);
        } else {
            try {
                // Hash the new password
                $hashed_password = hash_password($new_password);
                
                // Update password in database
                $stmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $reset_data['user_id']]);
                
                log_security_event('password_reset_completed', [
                    'user_id' => $reset_data['user_id'],
                    'username' => $reset_data['user_name']
                ]);
                
                // Clear reset session
                unset($_SESSION['password_reset']);
                $success = true;
                
            } catch (Exception $e) {
                $error = "An error occurred while resetting your password. Please try again.";
                log_security_event('password_reset_error', [
                    'user_id' => $reset_data['user_id'],
                    'error' => $e->getMessage()
                ]);
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
    <title>Reset Password - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <i data-lucide="key" class="text-purple-600 w-8 h-8"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Set New Password</h1>
            <p class="text-gray-600">Create a strong password for your account</p>
        </div>

        <?php if (!$success): ?>
            <!-- Progress Steps -->
            <div class="flex items-center justify-center mb-8">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-green-500 text-white rounded-full">
                        <i data-lucide="check" class="w-6 h-6"></i>
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-600">Request</span>
                </div>
                <div class="w-16 h-1 bg-green-500 mx-4"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-green-500 text-white rounded-full">
                        <i data-lucide="check" class="w-6 h-6"></i>
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-600">Verify</span>
                </div>
                <div class="w-16 h-1 bg-purple-600 mx-4"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-purple-600 text-white rounded-full font-semibold">3</div>
                    <span class="ml-2 text-sm font-semibold text-purple-600">Reset</span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <div class="flex items-start">
                        <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                        <div><?= $error ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Info -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i data-lucide="user" class="text-purple-600 w-5 h-5 mr-3"></i>
                    <div>
                        <p class="text-sm text-purple-700 font-semibold">Resetting password for:</p>
                        <p class="text-lg font-bold text-purple-900"><?= e($reset_data['user_name']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Password Requirements -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i data-lucide="info" class="text-blue-600 w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-2">Password Requirements:</p>
                        <ul class="space-y-1 text-xs">
                            <li class="flex items-center" id="req-length">
                                <i data-lucide="circle" class="w-3 h-3 mr-2"></i>
                                At least 8 characters long
                            </li>
                            <li class="flex items-center" id="req-uppercase">
                                <i data-lucide="circle" class="w-3 h-3 mr-2"></i>
                                One uppercase letter (A-Z)
                            </li>
                            <li class="flex items-center" id="req-lowercase">
                                <i data-lucide="circle" class="w-3 h-3 mr-2"></i>
                                One lowercase letter (a-z)
                            </li>
                            <li class="flex items-center" id="req-number">
                                <i data-lucide="circle" class="w-3 h-3 mr-2"></i>
                                One number (0-9)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Password Form -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        New Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" name="new_password" id="new_password" required
                               minlength="8"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-12">
                        <button type="button" onclick="togglePassword('new_password')" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i data-lucide="eye" id="eye-new_password" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-12">
                        <button type="button" onclick="togglePassword('confirm_password')" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i data-lucide="eye" id="eye-confirm_password" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Match Indicator -->
                <div id="match-indicator" class="hidden"></div>

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-4 rounded-lg transition duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                    Reset Password
                </button>
            </form>

        <?php else: ?>
            <!-- Success Message -->
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                    <i data-lucide="check-circle" class="text-green-600 w-12 h-12"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Password Reset Successful!</h2>
                <p class="text-gray-600 mb-6">Your password has been successfully reset</p>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i data-lucide="shield-check" class="text-green-600 w-5 h-5 mt-0.5 mr-3 flex-shrink-0"></i>
                        <div class="text-sm text-green-700 text-left">
                            <p class="font-semibold mb-1">Security Tips:</p>
                            <ul class="space-y-1 text-xs">
                                <li>• Don't share your password with anyone</li>
                                <li>• Use a unique password for this account</li>
                                <li>• Change your password regularly</li>
                                <li>• Enable two-factor authentication if available</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <a href="login.php" 
                   class="inline-flex items-center justify-center bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200 shadow-lg">
                    <i data-lucide="log-in" class="w-5 h-5 mr-2"></i>
                    Sign In Now
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.replace();
        
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById('eye-' + fieldId);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                field.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            
            if (typeof lucide !== 'undefined') lucide.replace();
        }
        
        // Password strength validation with visual feedback
        const passwordInput = document.getElementById('new_password');
        const confirmInput = document.getElementById('confirm_password');
        const matchIndicator = document.getElementById('match-indicator');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                // Check requirements
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password)
                };
                
                // Update UI
                updateRequirement('req-length', requirements.length);
                updateRequirement('req-uppercase', requirements.uppercase);
                updateRequirement('req-lowercase', requirements.lowercase);
                updateRequirement('req-number', requirements.number);
                
                checkPasswordMatch();
            });
        }
        
        if (confirmInput) {
            confirmInput.addEventListener('input', checkPasswordMatch);
        }
        
        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            if (!element) return;
            
            const icon = element.querySelector('i');
            if (met) {
                element.classList.add('text-green-600');
                element.classList.remove('text-blue-700');
                icon.setAttribute('data-lucide', 'check-circle');
            } else {
                element.classList.remove('text-green-600');
                element.classList.add('text-blue-700');
                icon.setAttribute('data-lucide', 'circle');
            }
            
            if (typeof lucide !== 'undefined') lucide.replace();
        }
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length === 0) {
                matchIndicator.classList.add('hidden');
                return;
            }
            
            matchIndicator.classList.remove('hidden');
            
            if (password === confirm) {
                matchIndicator.className = 'bg-green-50 border border-green-200 text-green-700 p-3 rounded flex items-center';
                matchIndicator.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5 mr-2"></i><span class="text-sm">Passwords match!</span>';
            } else {
                matchIndicator.className = 'bg-red-50 border border-red-200 text-red-700 p-3 rounded flex items-center';
                matchIndicator.innerHTML = '<i data-lucide="x-circle" class="w-5 h-5 mr-2"></i><span class="text-sm">Passwords do not match</span>';
            }
            
            if (typeof lucide !== 'undefined') lucide.replace();
        }
    </script>
</body>
</html>