<?php
// login.php - Hospital-themed Login Page with Security
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Redirect if already logged in
if (session_is_logged_in()) {
    $redirect_map = [
        'admin' => 'admin_home.php',
        'doctor' => 'doctor_home.php',
        'user' => 'client_home.php'
    ];
    $user_type = strtolower($_SESSION['user_type'] ?? 'user');
    header('Location: ' . ($redirect_map[$user_type] ?? 'index.php'));
    exit;
}

$error = '';
$success = '';

// Handle logout message
if (isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
}

if (isset($_GET['session_expired'])) {
    $error = 'Your session has expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $redirect = $_GET['redirect'] ?? '';
    
    // Rate limiting check
    $rate_check = check_rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 900);
    
    if (!$rate_check['allowed']) {
        $minutes = ceil($rate_check['retry_after'] / 60);
        $error = "Too many login attempts. Please try again in {$minutes} minutes.";
        log_security_event('login_rate_limit_exceeded', ['username' => $username]);
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT u.*, i.first_name, i.last_name 
                                  FROM tbluser u 
                                  LEFT JOIN tblinfo i ON u.user_id = i.user_id 
                                  WHERE u.user_name = ? AND u.status = 1 
                                  LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if password is hashed (starts with $2y$ for bcrypt)
                if (substr($user['password'], 0, 4) === '$2y$') {
                    // New hashed password
                    $password_valid = verify_password($password, $user['password']);
                } else {
                    // Legacy plain text password - validate and upgrade
                    $password_valid = ($password === $user['password']);
                    
                    if ($password_valid) {
                        // Upgrade to hashed password
                        $new_hash = hash_password($password);
                        $update_stmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
                        $update_stmt->execute([$new_hash, $user['user_id']]);
                        
                        log_security_event('password_upgraded_to_hash', ['user_id' => $user['user_id']]);
                    }
                }
                
                if ($password_valid) {
                    // Successful login
                    session_init_user($user);
                    
                    log_security_event('login_success', [
                        'user_id' => $user['user_id'],
                        'user_type' => $user['user_type']
                    ]);
                    
                    // Redirect based on user type
                    if (!empty($redirect) && filter_var($redirect, FILTER_VALIDATE_URL) === false) {
                        header('Location: ' . $redirect);
                    } else {
                        $redirect_map = [
                            'admin' => 'admin_home.php',
                            'doctor' => 'doctor_home.php',
                            'user' => 'client_home.php'
                        ];
                        $user_type = strtolower($user['user_type']);
                        header('Location: ' . ($redirect_map[$user_type] ?? 'index.php'));
                    }
                    exit;
                } else {
                    $error = 'Invalid username or password';
                    log_security_event('login_failed', ['username' => $username, 'reason' => 'invalid_password']);
                }
            } else {
                $error = 'Invalid username or password';
                log_security_event('login_failed', ['username' => $username, 'reason' => 'user_not_found']);
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            log_security_event('login_error', ['username' => $username, 'error' => $e->getMessage()]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Untalan General Hospital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background as fallback */
        }
        
        /* Custom Background Pattern */
        .bg-pattern {
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .input-group:focus-within label {
            color: #2563eb; /* Blue-600 */
        }
        
        .input-group:focus-within i {
            color: #2563eb;
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); /* Blue ring */
        }

        /* Animated Gradient Text */
        .text-gradient {
            background: linear-gradient(to right, #1e40af, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-pattern">

    <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden grid md:grid-cols-2 min-h-[600px]">
        
        <div class="hidden md:flex flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-indigo-700 text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full translate-x-1/2 translate-y-1/2 blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i data-lucide="activity" class="w-8 h-8 text-white"></i>
                    </div>
                    <span class="text-xl font-bold tracking-wide">AppointEase</span>
                </div>
                
                <h1 class="text-4xl font-bold leading-tight mb-6">
                    Your Health, <br/>Our Priority.
                </h1>
                <p class="text-blue-100 text-lg leading-relaxed mb-8">
                    Experience seamless healthcare management with Untalan General Hospital's advanced scheduling system.
                </p>
                
                <div class="space-y-4">
                    <div class="flex items-center space-x-4 bg-white/10 p-4 rounded-xl backdrop-blur-sm border border-white/10">
                        <div class="bg-white/20 p-2 rounded-full">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-semibold">24/7 Access</p>
                            <p class="text-sm text-blue-200">Book anytime, anywhere</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 bg-white/10 p-4 rounded-xl backdrop-blur-sm border border-white/10">
                        <div class="bg-white/20 p-2 rounded-full">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-semibold">Secure System</p>
                            <p class="text-sm text-blue-200">Your data is protected</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-12 text-sm text-blue-200 flex justify-between items-center">
                <span>© 2025 Untalan General Hospital</span>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-white transition">Privacy</a>
                    <a href="#" class="hover:text-white transition">Terms</a>
                </div>
            </div>
        </div>

        <div class="p-8 md:p-12 flex flex-col justify-center relative">
            
            <div class="md:hidden mb-8 text-center">
                <div class="inline-flex p-3 bg-blue-50 rounded-xl mb-4">
                    <i data-lucide="activity" class="w-8 h-8 text-blue-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Untalan General Hospital</h2>
            </div>

            <div class="w-full max-w-md mx-auto">
                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back!</h2>
                    <p class="text-gray-500">Please enter your details to sign in.</p>
                </div>

                <?php if ($success): ?>
                    <div class="flex items-center p-4 mb-6 text-sm text-green-700 bg-green-50 rounded-xl border border-green-100" role="alert">
                        <i data-lucide="check-circle-2" class="w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span><?= e($success) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="flex items-center p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-xl border border-red-100" role="alert">
                        <i data-lucide="alert-circle" class="w-5 h-5 mr-3 flex-shrink-0"></i>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <div class="input-group">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2 transition-colors">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="user" class="w-5 h-5 text-gray-400 transition-colors"></i>
                            </div>
                            <input type="text" name="username" id="username" required autofocus
                                value="<?= e($_POST['username'] ?? '') ?>"
                                class="input-field w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-blue-500"
                                placeholder="Enter your username">
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-medium text-gray-700 transition-colors">Password</label>
                            <a href="forgot_password.php" class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-5 h-5 text-gray-400 transition-colors"></i>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="input-field w-full pl-11 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-blue-500"
                                placeholder="Enter your password">
                            <button type="button" onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                                <i data-lucide="eye" id="eye-icon" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                        <label for="remember" class="ml-2 block text-sm text-gray-700 cursor-pointer">Remember me for 30 days</label>
                    </div>

                    <button type="submit" 
                        class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform active:scale-[0.98]">
                        Sign in
                        <i data-lucide="arrow-right" class="ml-2 w-4 h-4"></i>
                    </button>
                </form>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">Don't have an account?</span>
                    </div>
                </div>

                <a href="signup.php" 
                   class="w-full flex justify-center items-center py-3.5 px-4 border-2 border-gray-200 rounded-xl shadow-sm text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                    Create an account
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            
            // Re-initialize icons to update the eye icon
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        
        // Auto-dismiss success/error messages after 5 seconds with fade out
        setTimeout(() => {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>