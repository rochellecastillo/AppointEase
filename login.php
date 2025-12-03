<?php
include __DIR__ . '/controllers/login_data.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Untalan General Hospital</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { 
            background-color: #f8fafc;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 24px 24px;
        }
        
        /* Smooth Input Focus */
        .input-field {
            transition: all 0.2s ease-in-out;
        }
        .input-field:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); 
            border-color: #3b82f6;
        }

        .btn-spinner {
            width: 18px; height: 18px; margin-left: 8px; display: inline-block; vertical-align: middle;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 md:p-8" data-server-error="<?= empty($error) ? '0' : '1' ?>">

    <div class="w-full max-w-5xl bg-white rounded-[2rem] shadow-2xl shadow-blue-200/50 overflow-hidden grid md:grid-cols-2 min-h-[650px] border border-slate-100 relative">

        <div class="hidden md:flex flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-blue-900 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500 rounded-full blur-3xl opacity-20 translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-teal-500 rounded-full blur-3xl opacity-20 -translate-x-1/2 translate-y-1/2"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-12">
                    <div class="bg-white/10 backdrop-blur-sm p-2 rounded-xl border border-white/10">
                        <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold leading-none">Untalan<span class="text-blue-300">GH</span></h1>
                        <p class="text-[10px] text-blue-200 font-bold tracking-widest uppercase">AppointEase</p>
                    </div>
                </div>

                <h2 class="text-4xl font-bold leading-tight mb-6">Welcome to Better <br/>Healthcare.</h2>
                <p class="text-blue-100 text-lg leading-relaxed font-light opacity-90">
                    Access your medical records, manage appointments, and connect with specialists securely.
                </p>
            </div>

            <div class="relative z-10 bg-white/10 backdrop-blur-md border border-white/10 p-6 rounded-2xl mt-8">
                <div class="flex items-center gap-4 mb-3">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full bg-blue-100 border-2 border-blue-900"></div>
                        <div class="w-8 h-8 rounded-full bg-teal-100 border-2 border-blue-900"></div>
                        <div class="w-8 h-8 rounded-full bg-indigo-100 border-2 border-blue-900 flex items-center justify-center text-[10px] text-blue-900 font-bold">+2k</div>
                    </div>
                    <div class="text-sm font-medium">Trusted by Patients</div>
                </div>
                <div class="flex items-center gap-2 text-xs text-blue-200">
                    <i data-lucide="shield-check" class="w-4 h-4 text-teal-300"></i>
                    <span>HIPAA Compliant & Secure</span>
                </div>
            </div>

            <div class="relative z-10 mt-auto pt-8 text-xs text-blue-300/60">
                © 2025 Untalan General Hospital
            </div>
        </div>

        <div class="p-8 md:p-12 lg:p-16 flex flex-col justify-center bg-white relative">
            
            <a href="index.php" class="absolute top-6 left-6 md:top-8 md:left-8 flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-blue-600 transition-all group p-2 rounded-lg hover:bg-slate-50">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                Back to Home
            </a>
            
            <div class="md:hidden mt-12 mb-8 flex items-center gap-2">
                <div class="bg-blue-600 p-1.5 rounded-lg text-white">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                </div>
                <span class="text-lg font-bold text-slate-900">UntalanGH</span>
            </div>

            <div class="mb-10 mt-4 md:mt-0">
                <h2 class="text-3xl font-bold text-slate-900 mb-2">Sign In</h2>
                <p class="text-slate-500">Please enter your credentials to access your portal.</p>
            </div>

            <?php if ($success): ?>
                <div class="flex items-center p-4 mb-6 text-sm text-green-700 bg-green-50 rounded-xl border border-green-100 animate-pulse">
                    <i data-lucide="check-circle-2" class="w-5 h-5 mr-3 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="flex items-center p-4 mb-6 text-sm text-red-700 bg-red-50 rounded-xl border border-red-100">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" class="space-y-5" novalidate>
                
                <div>
                    <label for="username" class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="user" class="w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        </div>
                        <input type="text" name="username" id="username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               class="input-field w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none"
                               placeholder="Enter your username" required />
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <a href="forgot_password.php" class="text-sm text-blue-600 font-medium hover:text-blue-700">Forgot password?</a>
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        </div>
                        <input type="password" name="password" id="password"
                               class="input-field w-full pl-11 pr-12 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none"
                               placeholder="Enter your password" required />
                        
                        <button type="button" id="togglePasswordBtn" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <button id="submitBtn" type="submit"
                        class="w-full flex justify-center items-center py-4 px-4 rounded-xl text-white font-bold bg-blue-600 hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 hover:shadow-blue-600/40 transform hover:-translate-y-0.5 mt-2">
                    <span id="btnText">Sign In</span>
                    <svg id="btnSpinner" class="btn-spinner hidden" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.4 31.4"></circle>
                    </svg>
                    <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-slate-500 text-sm">
                    Don't have an account? 
                    <a href="signup.php" class="text-blue-600 font-bold hover:text-blue-700 hover:underline">Create Account</a>
                </p>
            </div>
        </div>
    </div>

    <script src="js/login.js" defer></script>

</body>
</html>