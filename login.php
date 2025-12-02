<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login - Untalan General Hospital</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .bg-pattern {
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .input-group:focus-within label, .input-group:focus-within i { color: #2563eb; }
        .input-field { transition: all 0.2s ease; }
        .input-field:focus { box-shadow: 0 0 0 4px rgba(59,130,246,0.08); }
        .field-error { color: #b91c1c; font-size: 0.875rem; margin-top: 0.375rem; display: none; }
        .input-error { border-color: #fca5a5 !important; background-color: #fff7f7; }
        .btn-spinner { width: 18px; height: 18px; margin-left: 8px; display: inline-block; vertical-align: middle; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 bg-pattern"
      data-server-error="<?= empty($error) ? '0' : '1' ?>">

<?php include __DIR__ . '/controllers/login_data.php'; ?>

    <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden grid md:grid-cols-2 min-h-[600px]">

        <!-- LEFT PANEL -->
        <div class="hidden md:flex flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-indigo-700 text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full translate-x-1/2 translate-y-1/2 blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <i data-lucide="activity" class="w-8 h-8 text-white"></i>
                    </div>
                    <span class="text-xl font-bold tracking-wide">AppointEase</span>
                </div>

                <h1 class="text-4xl font-bold leading-tight mb-6">Your Health, <br/>Our Priority.</h1>
                <p class="text-blue-100 text-lg leading-relaxed mb-8">
                    Experience seamless healthcare management with Untalan General Hospital's advanced scheduling system.
                </p>

                <div class="space-y-4">
                    <div class="flex items-center space-x-4 bg-white/10 p-4 rounded-xl backdrop-blur-sm border border-white/10">
                        <div class="bg-white/20 p-2 rounded-full"><i data-lucide="clock" class="w-5 h-5"></i></div>
                        <div>
                            <p class="font-semibold">24/7 Access</p>
                            <p class="text-sm text-blue-200">Book anytime, anywhere</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4 bg-white/10 p-4 rounded-xl backdrop-blur-sm border border-white/10">
                        <div class="bg-white/20 p-2 rounded-full"><i data-lucide="shield-check" class="w-5 h-5"></i></div>
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

        <!-- RIGHT PANEL -->
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
                    <div id="server-success" class="flex items-center p-4 mb-6 text-sm text-green-700 bg-green-50 rounded-xl border border-green-100">
                        <i data-lucide="check-circle-2" class="w-5 h-5 mr-3"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div id="server-error" class="flex items-center p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-xl border border-red-100">
                        <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form id="loginForm" method="POST" class="space-y-6" novalidate>

                    <div class="input-group">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                            </div>

                            <input type="text" name="username" id="username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   class="input-field w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl"
                                   placeholder="Enter your username" required />

                            <div id="usernameError" class="field-error"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <a href="forgot_password.php" class="text-sm text-blue-600">Forgot password?</a>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                            </div>

                            <input type="password" name="password" id="password"
                                   class="input-field w-full pl-11 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl"
                                   placeholder="Enter your password" required />

                            <button id="togglePasswordBtn" type="button"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400">
                                <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                            </button>

                            <div id="passwordError" class="field-error"></div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
                        <label for="remember" class="ml-2 text-sm text-gray-700">Remember me for 30 days</label>
                    </div>

                    <button id="submitBtn" type="submit"
                            class="w-full flex justify-center items-center py-3.5 px-4 rounded-xl text-white bg-blue-600 hover:bg-blue-700">
                        <span id="btnText">Sign in</span>
                        <svg id="btnSpinner" class="btn-spinner hidden" viewBox="0 0 50 50">
                            <circle cx="25" cy="25" r="20" fill="none" stroke="white" stroke-width="5"
                                    stroke-linecap="round" stroke-dasharray="31.4 31.4"></circle>
                        </svg>
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
                   class="w-full flex justify-center items-center py-3.5 px-4 border-2 border-gray-200 rounded-xl text-sm text-gray-700 bg-white hover:bg-gray-50">
                    Create an account
                </a>

            </div>
        </div>
    </div>

    <script src="js/login.js" defer></script>

</body>
</html>