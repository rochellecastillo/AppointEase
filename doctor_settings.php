<?php
include __DIR__ . '/controllers/doctor_settings_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .error-border { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
        .error-text { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
    </style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/doctor_sidebar.php'; ?>

        <main class="flex-1 overflow-auto w-full">
            <div class="md:hidden bg-white p-4 border-b flex justify-between items-center sticky top-0 z-30">
                <span class="font-bold text-lg text-slate-800">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-slate-100 rounded-lg"><i data-lucide="menu" width="20"></i></button>
            </div>

            <div class="p-6 md:p-8 max-w-5xl mx-auto">
                
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-slate-900">Doctor Settings</h1>
                    <p class="text-slate-500">Update your professional profile and account security.</p>
                </div>

                <?php if ($message): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: '<?= $msg_type ?>',
                                title: '<?= $msg_type === "success" ? "Success" : "Error" ?>',
                                text: '<?= htmlspecialchars($message) ?>',
                                confirmButtonColor: '#2563eb'
                            });
                        });
                    </script>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 text-center sticky top-6 shadow-sm">
                            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                <div class="relative inline-block mb-4 group">
                                    <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-slate-50 shadow-inner mx-auto">
                                        <img id="avatarPreview" src="<?= $avatar ?>" alt="Profile" class="w-full h-full object-cover transition-all duration-300">
                                    </div>
                                    <label for="avatarInput" class="absolute bottom-0 right-0 bg-green-600 text-white p-2 rounded-full cursor-pointer hover:bg-green-700 transition shadow-lg border-2 border-white">
                                        <i data-lucide="camera" class="w-4 h-4"></i>
                                    </label>
                                    <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/*" onchange="previewAndSubmit(this)">
                                </div>
                                <h2 class="text-lg font-bold text-slate-800">Dr. <?= e($info['last_name']) ?></h2>
                                <p class="text-sm text-green-600 font-medium"><?= e($info['specialization']) ?></p>
                                <p class="text-xs text-slate-400 mt-1">ID: <?= e($user_id) ?></p>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-slate-100">
                                <i data-lucide="user-cog" class="text-green-600 w-5 h-5"></i>
                                <h3 class="text-lg font-bold text-slate-800">Professional Profile</h3>
                            </div>
                            
                            <form method="POST" id="profileForm" novalidate>
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                                        <input type="text" name="first_name" id="first_name" value="<?= e($info['first_name']) ?>" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                                        <input type="text" name="last_name" id="last_name" value="<?= e($info['last_name']) ?>" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                </div>

                                <div class="mb-4 form-group">
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Specialization</label>
                                    <select name="specialization" id="specialization" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 bg-white validation-field">
                                        <option value="">Select...</option>
                                        <?php foreach($specs as $s): ?>
                                            <option value="<?= e($s) ?>" <?= ($info['specialization'] == $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="error-msg"></span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone Number</label>
                                        <input type="text" name="contact" id="contact" value="<?= e($info['contact']) ?>" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Gender</label>
                                        <select name="gender" id="gender" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 bg-white validation-field">
                                            <option value="Male" <?= ($info['gender']=='Male')?'selected':'' ?>>Male</option>
                                            <option value="Female" <?= ($info['gender']=='Female')?'selected':'' ?>>Female</option>
                                            <option value="Other" <?= ($info['gender']=='Other')?'selected':'' ?>>Other</option>
                                        </select>
                                        <span class="error-msg"></span>
                                    </div>
                                </div>

                                <div class="mb-6 form-group">
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Clinic Address / Office</label>
                                    <textarea name="address" id="address" rows="2" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition validation-field"><?= e($info['address']) ?></textarea>
                                    <span class="error-msg"></span>
                                </div>

                                <input type="hidden" name="bdate" value="<?= e($info['bdate']) ?>">

                                <button type="submit" name="update_profile" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-md shadow-green-100 w-full md:w-auto">
                                    Save Changes
                                </button>
                            </form>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-slate-100">
                                <i data-lucide="lock" class="text-red-500 w-5 h-5"></i>
                                <h3 class="text-lg font-bold text-slate-800">Security</h3>
                            </div>

                            <form method="POST" id="passwordForm" novalidate>
                                <input type="hidden" name="change_password" value="1">

                                <div class="space-y-4 mb-6">
                                    <div class="form-group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Current Password</label>
                                        <input type="password" name="current_password" id="current_password" required class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-red-500 transition validation-field">
                                        <span class="error-msg"></span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">New Password</label>
                                            <input type="password" name="new_password" id="new_password" required class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-red-500 transition validation-field">
                                            <span class="error-msg"></span>
                                        </div>
                                        <div class="form-group">
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Confirm New</label>
                                            <input type="password" name="confirm_password" id="confirm_password" required class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-red-500 transition validation-field">
                                            <span class="error-msg"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="px-6 py-3 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition w-full md:w-auto">
                                    Update Password
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/doctor_settings.js" defer></script>

</body>
</html>