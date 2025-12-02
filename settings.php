<?php
include __DIR__ . '/controllers/settings_data.php';
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Settings - AppointmentEase</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }
    .active-tab { border-color: #8B5CF6; color: #8B5CF6; }
    .inactive-tab { border-color: transparent; color: #6B7280; }
    .inactive-tab:hover { color: #374151; border-color: #D1D5DB; }
    .error-border { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
    .error-text { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">
  <div class="flex h-screen overflow-hidden">
    
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="flex-1 overflow-auto">
      <div class="p-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Settings</h1>
            <p class="text-gray-500 mt-1">Manage your account information and security.</p>
        </div>

        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({ icon: 'error', title: 'Error', text: "<?= addslashes($error) ?>", confirmButtonColor: '#ef4444' });
                });
            </script>
        <?php endif; ?>

        <?php if ($success): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({ icon: 'success', title: 'Success', text: "<?= addslashes($success) ?>", confirmButtonColor: '#8B5CF6' });
                });
            </script>
        <?php endif; ?>

        <div class="border-b border-gray-200 mb-6 max-w-4xl mx-auto">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button type="button" data-tab-id="profile" class="tab-button inline-flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition duration-200">
                    <i data-lucide="user" width="20"></i> Profile Information
                </button>
                <button type="button" data-tab-id="security" class="tab-button inline-flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition duration-200">
                    <i data-lucide="lock" width="20"></i> Account Security
                </button>
            </nav>
        </div>

        <div class="max-w-4xl mx-auto">
            
            <div id="profile" class="tab-content bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Update Your Personal Details</h2>
                
                <form method="POST" id="profileForm" novalidate>
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="first_name" id="first_name" required value="<?= htmlspecialchars($admin_info['first_name']) ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500 validation-field">
                            <span class="error-msg"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="last_name" id="last_name" required value="<?= htmlspecialchars($admin_info['last_name']) ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500 validation-field">
                            <span class="error-msg"></span>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number (09XXXXXXXXX)</label>
                            <input type="tel" name="phone" id="phone" required value="<?= htmlspecialchars($admin_info['phone']) ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500 validation-field">
                            <span class="error-msg"></span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username (Read-only)</label>
                            <p class="mt-1 block w-full text-gray-500 border border-gray-200 bg-gray-50 rounded-lg p-3">
                                <?= htmlspecialchars($admin_info['username']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl transition duration-150 shadow-md">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

            <div id="security" class="tab-content hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Change Password</h2>
                
                <form method="POST" id="passwordForm" novalidate>
                    <input type="hidden" name="change_password" value="1">

                    <div class="space-y-6">
                        <div class="form-group">
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500 validation-field">
                            <span class="error-msg"></span>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" id="new_password" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500 validation-field">
                            <p class="text-xs text-gray-500 mt-1">
                                8+ chars, 1 uppercase, 1 lowercase, 1 number.
                            </p>
                            <span class="error-msg"></span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500 validation-field">
                            <span class="error-msg"></span>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-xl transition duration-150 shadow-md">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
      </div>
    </main>
  </div>

<script src="js/settings.js" defer></script>

</body>
</html>