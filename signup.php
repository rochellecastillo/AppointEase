<?php
include __DIR__ . '/controllers/signup_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - AppointEase</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { 
            background-color: #f8fafc;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 24px 24px;
        }
        
        .input-field { transition: all 0.2s ease-in-out; }
        .input-field:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); 
            border-color: #3b82f6;
            background-color: #ffffff;
        }

        /* Custom Scrollbar for form section */
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 md:p-6" data-server-error="<?= !empty($errors) ? '1' : '0' ?>">

    <div class="w-full max-w-7xl bg-white rounded-[2rem] shadow-2xl shadow-blue-200/50 overflow-hidden grid md:grid-cols-12 min-h-[850px] border border-slate-100">

        <div class="hidden md:flex md:col-span-4 flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-blue-900 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-400 rounded-full blur-3xl opacity-20 translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-teal-400 rounded-full blur-3xl opacity-20 -translate-x-1/2 translate-y-1/2"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-10">
                    <div class="bg-white/10 backdrop-blur-sm p-2 rounded-xl border border-white/10">
                        <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight">AppointEase</span>
                </div>

                <h1 class="text-3xl font-bold leading-tight mb-6">Join Our <br>Community</h1>
                <p class="text-blue-100 text-base leading-relaxed mb-8 opacity-90">
                    Create an account to manage your appointments, view medical history, and connect with specialists seamlessly.
                </p>

                <div class="space-y-5">
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                        <div class="bg-blue-500/20 p-2 rounded-lg shrink-0"><i data-lucide="calendar-check" class="w-5 h-5 text-blue-200"></i></div>
                        <div>
                            <p class="font-bold text-sm">Instant Booking</p>
                            <p class="text-xs text-blue-200/70 mt-0.5">Real-time doctor availability.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                        <div class="bg-teal-500/20 p-2 rounded-lg shrink-0"><i data-lucide="shield-check" class="w-5 h-5 text-teal-200"></i></div>
                        <div>
                            <p class="font-bold text-sm">Secure Records</p>
                            <p class="text-xs text-blue-200/70 mt-0.5">HIPAA compliant data protection.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-auto pt-8 text-sm text-blue-200/80">
                Already have an account? <a href="login.php" class="text-white font-bold hover:underline hover:text-blue-200 transition">Sign in</a>
            </div>
        </div>

        <div class="md:col-span-8 bg-white relative flex flex-col h-full">
            
            <div class="sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-slate-100 px-8 py-4 flex justify-between items-center">
                <a href="index.php" class="flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-blue-600 transition group">
                    <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                    Back to Home
                </a>
                
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">1</div>
                        <span class="text-xs font-bold text-slate-700">Details</span>
                    </div>
                    <div class="w-8 h-0.5 bg-slate-200"></div>
                    <div class="flex items-center gap-2 opacity-50">
                        <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 text-xs font-bold flex items-center justify-center">2</div>
                        <span class="text-xs font-bold text-slate-700">Verify</span>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scroll p-8 md:p-12">
                
                <div class="max-w-3xl mx-auto">
                    <div class="md:hidden mb-6 flex items-center gap-2">
                        <div class="bg-blue-600 p-1.5 rounded-lg text-white">
                            <i data-lucide="activity" class="w-5 h-5"></i>
                        </div>
                        <span class="text-lg font-bold text-slate-900">UntalanGH</span>
                    </div>

                    <h2 class="text-2xl font-bold text-slate-900 mb-2">Patient Registration</h2>
                    <p class="text-slate-500 mb-8">Please fill in your details accurately.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-50 border border-red-100 rounded-xl p-4 mb-8 animate-pulse">
                            <div class="flex">
                                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5"></i>
                                <div class="ml-3">
                                    <h3 class="text-sm font-bold text-red-800">Submission Failed</h3>
                                    <ul class="mt-1 text-sm text-red-700 list-disc list-inside space-y-1">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-8" id="signupForm">
                        <input type="hidden" name="action" value="start_signup">

                        <div class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100">
                            <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wider mb-6 flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4"></i> Personal Information
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">First Name *</label>
                                    <input type="text" name="first_name" required value="<?= htmlspecialchars($prefill['first_name'] ?? '') ?>"
                                        class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white" placeholder="John">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Middle Name</label>
                                    <input type="text" name="middle_name" value="<?= htmlspecialchars($prefill['middle_name'] ?? '') ?>"
                                        class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white" placeholder="(Optional)">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Last Name *</label>
                                    <input type="text" name="last_name" required value="<?= htmlspecialchars($prefill['last_name'] ?? '') ?>"
                                        class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white" placeholder="Doe">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Birth Date *</label>
                                    <input type="date" name="bdate" required value="<?= htmlspecialchars($prefill['bdate'] ?? '') ?>"
                                        max="<?= date('Y-m-d', strtotime('-13 years')) ?>"
                                        class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-slate-600">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Gender *</label>
                                    <div class="relative">
                                        <select name="gender" required class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white appearance-none cursor-pointer">
                                            <option value="">Select</option>
                                            <option value="Male" <?= ($prefill['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= ($prefill['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        </select>
                                        <i data-lucide="chevron-down" class="absolute right-4 top-3 w-4 h-4 text-slate-400 pointer-events-none"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100">
                            <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wider mb-6 flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-4 h-4"></i> Contact Details
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Complete Address *</label>
                                    <input type="text" name="address" required value="<?= htmlspecialchars($prefill['address'] ?? '') ?>"
                                        class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white" placeholder="House No, Street, Brgy, City">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mobile Number *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-slate-400 font-bold text-sm">+63</span>
                                        </div>
                                        <input type="tel" name="contact" required value="<?= htmlspecialchars($prefill['contact'] ?? '') ?>"
                                            placeholder="9171234567" pattern="^(09|\+639)[0-9]{9}$"
                                            class="input-field w-full pl-12 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white">
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                                        <i data-lucide="info" class="w-3 h-3"></i> OTP will be sent here for verification.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100">
                            <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wider mb-6 flex items-center gap-2">
                                <i data-lucide="shield-lock" class="w-4 h-4"></i> Account Security
                            </h3>

                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username *</label>
                                    <input type="text" name="user_name" required value="<?= htmlspecialchars($prefill['user_name'] ?? '') ?>"
                                        minlength="4" maxlength="30"
                                        class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white" placeholder="Choose a unique username">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password *</label>
                                        <div class="relative">
                                            <input type="password" name="password" id="password" required minlength="8"
                                                class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white pr-10">
                                            <button type="button" onclick="togglePassword('password', 'eye-password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                                <i data-lucide="eye" id="eye-password" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirm Password *</label>
                                        <div class="relative">
                                            <input type="password" name="confirm_password" id="confirm_password" required
                                                class="input-field w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white pr-10">
                                            <button type="button" onclick="togglePassword('confirm_password', 'eye-confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                                <i data-lucide="eye" id="eye-confirm_password" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class="flex items-start gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" name="terms_accepted" required class="mt-1 h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                <span class="text-sm text-slate-600">
                                    I agree to the <a href="terms.php" class="text-blue-600 font-bold hover:underline">Terms of Service</a> and <a href="privacy.php" class="text-blue-600 font-bold hover:underline">Privacy Policy</a> of Untalan General Hospital.
                                </span>
                            </label>
                        </div>

                        <div class="pt-4 pb-8">
                            <button type="submit" class="w-full flex justify-center items-center py-4 px-6 rounded-xl text-white font-bold bg-blue-600 hover:bg-blue-700 transition shadow-xl shadow-blue-600/20 transform hover:-translate-y-0.5">
                                Create Account & Send OTP
                                <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === "password") {
                input.type = "text";
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = "password";
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
    <script src="js/signup.js" defer></script>

</body>
</html>