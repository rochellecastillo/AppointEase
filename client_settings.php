<?php
include __DIR__ . '/controllers/client_settings_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .error-border { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
        .error-text { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/client_sidebar.php'; ?>

        <main class="flex-1 overflow-auto relative">
            <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="p-6 max-w-5xl mx-auto">
                
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
                    <p class="text-gray-500">Manage your profile information and account security.</p>
                </div>

                <?php if ($message): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: '<?= $msg_type ?>',
                                title: '<?= $msg_type === "success" ? "Success" : "Error" ?>',
                                text: '<?= htmlspecialchars($message) ?>',
                                confirmButtonColor: '#7c3aed'
                            });
                        });
                    </script>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center sticky top-6">
                            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                <div class="relative inline-block mb-4 group">
                                    <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-purple-50 shadow-inner mx-auto">
                                        <img id="avatarPreview" src="<?= $avatar ?>" alt="Profile" class="w-full h-full object-cover transition-all duration-300">
                                    </div>
                                    <label for="avatarInput" class="absolute bottom-0 right-0 bg-purple-600 text-white p-2 rounded-full cursor-pointer hover:bg-purple-700 transition shadow-lg border-2 border-white">
                                        <i data-lucide="camera" class="w-4 h-4"></i>
                                    </label>
                                    <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/*" onchange="previewAndSubmit(this)">
                                </div>
                                <h2 class="text-lg font-bold text-gray-800"><?= e($info['first_name'] . ' ' . $info['last_name']) ?></h2>
                                <p class="text-sm text-gray-500">Patient ID: <?= e($user_id) ?></p>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-gray-100">
                                <i data-lucide="user" class="text-purple-600 w-5 h-5"></i>
                                <h3 class="text-lg font-bold text-gray-800">Personal Information</h3>
                            </div>
                            
                            <form method="POST" id="profileForm" novalidate>
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">First Name</label>
                                        <input type="text" name="first_name" id="first_name" value="<?= e($info['first_name']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Middle Name</label>
                                        <input type="text" name="middle_name" id="middle_name" value="<?= e($info['middle_name']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Last Name</label>
                                        <input type="text" name="last_name" id="last_name" value="<?= e($info['last_name']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date of Birth</label>
                                        <input type="date" name="bdate" id="bdate" value="<?= e($info['bdate']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gender</label>
                                        <select name="gender" id="gender" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition validation-field">
                                            <option value="Male" <?= strtolower($info['gender']) == 'male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= strtolower($info['gender']) == 'female' ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= strtolower($info['gender']) == 'other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Contact Number</label>
                                        <input type="text" name="contact" id="contact" value="<?= e($info['contact']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                </div>

                                <div class="mb-6 form-group">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Address</label>
                                    <textarea name="address" id="address" rows="2" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition validation-field"><?= e($info['address']) ?></textarea>
                                    <span class="error-msg"></span>
                                </div>

                                <button type="submit" name="update_profile" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition shadow-md w-full md:w-auto">
                                    Save Changes
                                </button>
                            </form>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-gray-100">
                                <i data-lucide="lock" class="text-red-500 w-5 h-5"></i>
                                <h3 class="text-lg font-bold text-gray-800">Security</h3>
                            </div>

                            <form method="POST" id="passwordForm" novalidate>
                                <input type="hidden" name="change_password" value="1">

                                <div class="space-y-4 mb-6">
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Password</label>
                                        <input type="password" name="current_password" id="current_password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-red-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New Password</label>
                                            <input type="password" name="new_password" id="new_password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-red-500 transition validation-field">
                                            <span class="error-msg"></span>
                                        </div>
                                        <div class="form-group">
                                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirm New Password</label>
                                            <input type="password" name="confirm_password" id="confirm_password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-red-500 transition validation-field">
                                            <span class="error-msg"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition w-full md:w-auto">
                                    Update Password
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        // 1. Avatar Preview
        function previewAndSubmit(input) {
            const file = input.files[0];
            if(file) {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Max size is 2MB.' });
                    return;
                }
                // Preview
                const reader = new FileReader();
                reader.onload = function (e) { document.getElementById('avatarPreview').src = e.target.result; }
                reader.readAsDataURL(file);
                
                // Submit automatically
                document.getElementById('avatarForm').submit();
            }
        }

        // 2. Validation Logic
        document.addEventListener('DOMContentLoaded', () => {
            
            // Helper: Show Error
            const showError = (input, msg) => {
                input.classList.add('error-border');
                const group = input.closest('.form-group');
                if(group) {
                    let span = group.querySelector('.error-msg');
                    if(!span) { span = document.createElement('span'); span.className='error-msg error-text'; group.appendChild(span); }
                    span.textContent = msg;
                    span.classList.add('error-text');
                }
                return false;
            };

            // Helper: Clear Error
            const clearError = (input) => {
                input.classList.remove('error-border');
                const group = input.closest('.form-group');
                if(group) {
                    const span = group.querySelector('.error-msg');
                    if(span) span.textContent = '';
                }
                return true;
            };

            // Profile Validation
            const profileForm = document.getElementById('profileForm');
            if(profileForm) {
                profileForm.addEventListener('submit', (e) => {
                    let valid = true;
                    
                    const fname = document.getElementById('first_name');
                    if(!/^[a-zA-Z\s.-]+$/.test(fname.value.trim())) valid = showError(fname, "Invalid format (letters only).");
                    else clearError(fname);

                    const lname = document.getElementById('last_name');
                    if(!/^[a-zA-Z\s.-]+$/.test(lname.value.trim())) valid = showError(lname, "Invalid format (letters only).");
                    else clearError(lname);

                    const contact = document.getElementById('contact');
                    if(!/^(09|\+639)\d{9}$/.test(contact.value.trim())) valid = showError(contact, "Invalid PH mobile number.");
                    else clearError(contact);

                    if(!valid) {
                        e.preventDefault();
                        Swal.fire({ icon: 'warning', title: 'Check Inputs', text: 'Please correct errors in the form.' });
                    }
                });
            }

            // Password Validation
            const passForm = document.getElementById('passwordForm');
            if(passForm) {
                passForm.addEventListener('submit', (e) => {
                    let valid = true;
                    const current = document.getElementById('current_password');
                    const newP = document.getElementById('new_password');
                    const confP = document.getElementById('confirm_password');

                    if(!current.value) valid = showError(current, "Required.");
                    else clearError(current);

                    if(newP.value.length < 8) valid = showError(newP, "Min 8 chars.");
                    else clearError(newP);

                    if(newP.value !== confP.value) valid = showError(confP, "Passwords do not match.");
                    else clearError(confP);

                    if(!valid) {
                        e.preventDefault();
                        Swal.fire({ icon: 'warning', title: 'Check Password', text: 'Please fix password errors.' });
                    }
                });
            }

            // Real-time clearing
            document.querySelectorAll('.validation-field').forEach(el => {
                el.addEventListener('input', () => clearError(el));
            });

        });

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>