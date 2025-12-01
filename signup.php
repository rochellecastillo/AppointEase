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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .bg-pattern { background-color: #ffffff; background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
        .input-field:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-pattern">

    <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden grid md:grid-cols-5 min-h-[800px]">
        
        <div class="hidden md:flex md:col-span-2 flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-indigo-700 text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full translate-x-1/2 translate-y-1/2 blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i data-lucide="activity" class="w-8 h-8 text-white"></i>
                    </div>
                    <span class="text-xl font-bold tracking-wide">AppointEase</span>
                </div>
                
                <h1 class="text-3xl font-bold leading-tight mb-6">Join Our Community</h1>
                <p class="text-blue-100 text-lg leading-relaxed mb-8">Create an account to manage your appointments and health records seamlessly.</p>
                
                <div class="space-y-6">
                    <div class="flex items-start space-x-4">
                        <div class="bg-white/20 p-2 rounded-full mt-1"><i data-lucide="check" class="w-4 h-4"></i></div>
                        <div><p class="font-semibold">Easy Scheduling</p><p class="text-sm text-blue-200">Book appointments in seconds.</p></div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="bg-white/20 p-2 rounded-full mt-1"><i data-lucide="check" class="w-4 h-4"></i></div>
                        <div><p class="font-semibold">Secure Records</p><p class="text-sm text-blue-200">Your medical history, safe and sound.</p></div>
                    </div>
                </div>
            </div>
            
            <div class="relative z-10 mt-auto pt-8 text-sm text-blue-200">
                Already have an account? <a href="login.php" class="text-white font-semibold underline hover:text-blue-100">Sign in</a>
            </div>
        </div>

        <div class="md:col-span-3 p-8 md:p-12 overflow-y-auto h-full">
            
            <div class="md:hidden mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800">AppointEase</h2>
                <p class="text-gray-500">Patient Registration</p>
            </div>

            <div class="flex items-center justify-center mb-10">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-bold text-sm ring-4 ring-blue-100">1</div>
                    <span class="ml-2 text-sm font-semibold text-gray-900">Details</span>
                </div>
                <div class="w-16 h-1 bg-gray-200 mx-4 rounded"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 text-gray-500 rounded-full font-bold text-sm">2</div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Verify</span>
                </div>
            </div>

            <div class="max-w-2xl mx-auto">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Create your account</h2>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-8">
                    <input type="hidden" name="action" value="start_signup">

                    <section>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center pb-2 border-b border-gray-200">
                            <i data-lucide="user" class="w-5 h-5 mr-2 text-blue-600"></i> Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" required value="<?= htmlspecialchars($prefill['first_name'] ?? '') ?>"
                                    class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" required value="<?= htmlspecialchars($prefill['last_name'] ?? '') ?>"
                                    class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name (Optional)</label>
                                <input type="text" name="middle_name" value="<?= htmlspecialchars($prefill['middle_name'] ?? '') ?>"
                                    class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Birth Date <span class="text-red-500">*</span></label>
                                <input type="date" name="bdate" required value="<?= htmlspecialchars($prefill['bdate'] ?? '') ?>" max="<?= date('Y-m-d', strtotime('-13 years')) ?>"
                                    class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                                <select name="gender" required class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none bg-white">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= ($prefill['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($prefill['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($prefill['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center pb-2 border-b border-gray-200">
                            <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-blue-600"></i> Contact Details
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Complete Address <span class="text-red-500">*</span></label>
                                <input type="text" name="address" required value="<?= htmlspecialchars($prefill['address'] ?? '') ?>" placeholder="Unit, Street, Barangay, City, Province"
                                    class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="smartphone" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <input type="tel" name="contact" required value="<?= htmlspecialchars($prefill['contact'] ?? '') ?>" placeholder="09171234567" pattern="^(09|\+639)[0-9]{9}$"
                                        class="input-field w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">We will send an OTP to verify this number.</p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center pb-2 border-b border-gray-200">
                            <i data-lucide="shield" class="w-5 h-5 mr-2 text-blue-600"></i> Account Security
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                                <input type="text" name="user_name" required value="<?= htmlspecialchars($prefill['user_name'] ?? '') ?>" minlength="4" maxlength="30"
                                    class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="password" id="password" required minlength="8"
                                            class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none pr-10">
                                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <i data-lucide="eye" id="eye-password" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="confirm_password" id="confirm_password" required
                                            class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none pr-10">
                                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <i data-lucide="eye" id="eye-confirm_password" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Password must be at least 8 characters, include an uppercase letter, a lowercase letter, and a number.</p>
                        </div>
                    </section>

                    <div class="pt-4">
                        <label class="flex items-start">
                            <input type="checkbox" name="terms_accepted" required class="mt-1 mr-3 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-600">
                                I agree to the <a href="terms.php" target="_blank" class="text-blue-600 hover:underline">Terms and Conditions</a> 
                                and <a href="privacy.php" target="_blank" class="text-blue-600 hover:underline">Privacy Policy</a>.
                            </span>
                        </label>
                    </div>

                    <div class="pt-4">
                        <button type="submit" 
                            class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform active:scale-[0.98]">
                            Sign Up & Send OTP
                            <i data-lucide="arrow-right" class="ml-2 w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
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
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    </script>
</body>
</html>