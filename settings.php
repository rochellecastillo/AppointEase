<?php
// settings.php - System settings page
require_once 'session_handler.php';
require_once 'security_helper.php'; // Provides e(), hash_password(), verify_password(), validate_password_strength(), validate_email(), normalize_phone_ph(), validate_phone_ph()
require_once 'db.php';

// Require admin authentication
session_require_auth(['admin']);

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// --- Handle Form Submissions ---

// 1. Handle PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize_output($_POST['first_name'] ?? '');
    $last_name = sanitize_output($_POST['last_name'] ?? '');
    $email = sanitize_output($_POST['email'] ?? '');
    $phone = sanitize_output($_POST['phone'] ?? '');

    // Normalize phone number to consistent format (e.g., 09XXXXXXXXX)
    $normalized_phone = normalize_phone_ph($phone); 

    if (empty($first_name) || empty($last_name) || !validate_email($email) || !validate_phone_ph($normalized_phone)) {
        $error = "Please provide valid First Name, Last Name, Email, and Philippine Phone Number.";
    } else {
        try {
            // Using 'email' and 'contact' columns. 'contact' is confirmed from schema.
            $stmt = $pdo->prepare("UPDATE tblinfo SET first_name = ?, last_name = ?, email = ?, contact = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $email, $normalized_phone, $user_id]);
            $success = "Profile information updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// 2. Handle PASSWORD CHANGE (CRITICAL SECURITY FIX APPLIED)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $password_validation = validate_password_strength($new_password);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (!$password_validation['valid']) {
        $error = "New password is too weak. " . implode(', ', $password_validation['errors']);
    } else {
        try {
            // 1. Fetch the HASHED password from the database
            $stmt = $pdo->prepare("SELECT password FROM tbluser WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            // 2. SECURITY FIX: Use verify_password() to check current password against the hash
            if ($user && verify_password($current_password, $user['password'])) {
                // 3. Hash the new password before storing it
                $hashed_new_password = hash_password($new_password);

                $stmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_new_password, $user_id]);
                $success = "Password changed successfully!";
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (Exception $e) {
            $error = "Error updating password: " . $e->getMessage();
        }
    }
}

// --- Get Admin Info (Fetch latest data after possible update) ---
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.first_name, i.last_name, i.email, i.contact AS phone, u.user_name AS username
        FROM tblinfo i 
        JOIN tbluser u ON u.user_id = i.user_id
        WHERE i.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $admin_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // If phone is available, try to format it for display (optional, but nice UX)
    $raw_phone = $admin_info['phone'] ?? '';
    if (validate_phone_ph($raw_phone)) {
        // Formats 09XXXXXXXXX to 09XX-XXX-XXXX for display
        $admin_info['phone_display'] = substr($raw_phone, 0, 4) . '-' . substr($raw_phone, 4, 3) . '-' . substr($raw_phone, 7);
    } else {
        $admin_info['phone_display'] = 'N/A';
    }

} catch (Exception $e) {
    // This will catch the error if either the 'email' or 'user_name' columns are still missing.
    die("Database Error: " . $e->getMessage()); 
}

// Set default values for forms if profile data is missing
$admin_info = $admin_info ?: [
    'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'username' => ''
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Settings - AppointmentEase</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }
    .active-tab { border-color: #8B5CF6; color: #8B5CF6; } /* purple-600 */
    .inactive-tab { border-color: transparent; color: #6B7280; } /* gray-500 */
    .inactive-tab:hover { color: #374151; border-color: #D1D5DB; } /* gray-700 / gray-300 */
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
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
                <p class="font-bold">Error</p>
                <p><?= e($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
                <p class="font-bold">Success</p>
                <p><?= e($success) ?></p>
            </div>
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
                
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="first_name" id="first_name" required value="<?= e($admin_info['first_name']) ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="last_name" id="last_name" required value="<?= e($admin_info['last_name']) ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" id="email" required value="<?= e($admin_info['email']) ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number (09XXXXXXXXX)</label>
                            <input type="tel" name="phone" id="phone" required value="<?= e($admin_info['phone']) ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username (Read-only)</label>
                            <p class="mt-1 block w-full text-gray-500 border border-gray-200 bg-gray-50 rounded-lg p-3">
                                <?= e($admin_info['username']) ?>
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
                
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">

                    <div class="space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500" autocomplete="current-password">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" id="new_password" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500" autocomplete="new-password">
                            <p class="text-xs text-gray-500 mt-1">
                                **Password Requirements:** Must be at least 8 characters long, include an uppercase letter, a lowercase letter, and a number.
                            </p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-purple-500 focus:border-purple-500" autocomplete="new-password">
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

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Simple Tab Switching Logic
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-button');
        const contents = document.querySelectorAll('.tab-content');

        const activeClass = 'border-purple-600 text-purple-600 font-semibold';
        const inactiveClass = 'border-transparent text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300';

        function showTab(tabId) {
            tabs.forEach(tab => {
                if (tab.getAttribute('data-tab-id') === tabId) {
                    tab.className = 'tab-button inline-flex items-center gap-2 py-4 px-1 border-b-2 text-sm transition duration-200 ' + activeClass;
                } else {
                    tab.className = 'tab-button inline-flex items-center gap-2 py-4 px-1 border-b-2 text-sm transition duration-200 ' + inactiveClass;
                }
            });

            contents.forEach(content => {
                if (content.id === tabId) {
                    content.classList.remove('hidden');
                } else {
                    content.classList.add('hidden');
                }
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                const tabId = e.currentTarget.getAttribute('data-tab-id');
                showTab(tabId);
            });
        });

        // Auto-switch tab based on alerts
        const errorAlert = document.querySelector('.bg-red-100');
        const successAlert = document.querySelector('.bg-green-100');
        let initialTab = 'profile';

        if (errorAlert || successAlert) {
            const messageText = (errorAlert || successAlert).innerText.toLowerCase();
            if (messageText.includes('password') || messageText.includes('weak')) {
                 initialTab = 'security';
            }
        }
        
        // Initial tab display
        showTab(initialTab);
    });
</script>
</body>
</html>