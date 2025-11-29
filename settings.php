<?php
// settings.php - System settings page
require_once 'session_handler.php';
require_once 'security_helper.php'; 
require_once 'db.php';

// Require admin authentication
session_require_auth(['admin']);

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// --- Handle Form Submissions ---

// 1. Handle PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = htmlspecialchars(trim($_POST['first_name'] ?? ''));
    $last_name = htmlspecialchars(trim($_POST['last_name'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');

    // Normalize phone number
    $normalized_phone = normalize_phone_ph($phone); 

    if (empty($first_name) || empty($last_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !validate_phone_ph($normalized_phone)) {
        $error = "Please provide valid First Name, Last Name, Email, and Philippine Phone Number.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE tblinfo SET first_name = ?, last_name = ?, email = ?, contact = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $email, $normalized_phone, $user_id]);
            $success = "Profile information updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// 2. Handle PASSWORD CHANGE
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
            $stmt = $pdo->prepare("SELECT password FROM tbluser WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

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

// --- Get Admin Info ---
try {
    $stmt = $pdo->prepare("
        SELECT i.first_name, i.last_name, i.email, i.contact AS phone, u.user_name AS username
        FROM tblinfo i 
        JOIN tbluser u ON u.user_id = i.user_id
        WHERE i.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $admin_info = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage()); 
}

$admin_info = $admin_info ?: ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'username' => ''];
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
                        
                        <div class="md:col-span-2 form-group">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" id="email" required value="<?= htmlspecialchars($admin_info['email']) ?>"
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

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // -------------------- TAB LOGIC --------------------
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
                content.id === tabId ? content.classList.remove('hidden') : content.classList.add('hidden');
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => showTab(e.currentTarget.getAttribute('data-tab-id')));
        });

        // Auto-switch tab if error related to password
        const hasError = "<?= $error ?>";
        if (hasError && (hasError.includes('password') || hasError.includes('weak'))) {
            showTab('security');
        } else {
            showTab('profile');
        }

        // -------------------- VALIDATION LOGIC --------------------
        
        // Helper: Show/Clear Error
        const showError = (input, msg) => {
            input.classList.add('error-border');
            const group = input.closest('.form-group');
            if(group) {
                const span = group.querySelector('.error-msg');
                if(span) { span.textContent = msg; span.classList.add('error-text'); }
            }
            return false;
        };

        const clearError = (input) => {
            input.classList.remove('error-border');
            const group = input.closest('.form-group');
            if(group) {
                const span = group.querySelector('.error-msg');
                if(span) { span.textContent = ''; }
            }
            return true;
        };

        // Validators
        const validateProfile = () => {
            let valid = true;
            
            const fname = document.getElementById('first_name');
            if(!/^[a-zA-Z\s.-]+$/.test(fname.value.trim())) valid = showError(fname, "Letters only."); else clearError(fname);

            const lname = document.getElementById('last_name');
            if(!/^[a-zA-Z\s.-]+$/.test(lname.value.trim())) valid = showError(lname, "Letters only."); else clearError(lname);

            const phone = document.getElementById('phone');
            if(!/^(09|\+639)\d{9}$/.test(phone.value.trim())) valid = showError(phone, "Invalid PH format."); else clearError(phone);

            const email = document.getElementById('email');
            if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) valid = showError(email, "Invalid email."); else clearError(email);

            return valid;
        };

        const validatePassword = () => {
            let valid = true;
            const current = document.getElementById('current_password');
            const newP = document.getElementById('new_password');
            const confP = document.getElementById('confirm_password');

            if(!current.value) valid = showError(current, "Required."); else clearError(current);

            // Strong Password Regex
            const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\W_]{8,}$/;
            if(!strongRegex.test(newP.value)) valid = showError(newP, "Too weak."); else clearError(newP);

            if(newP.value !== confP.value) valid = showError(confP, "Passwords do not match."); else clearError(confP);

            return valid;
        };

        // Attach Listeners
        document.getElementById('profileForm').addEventListener('submit', (e) => {
            if(!validateProfile()) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Check Inputs', text: 'Please correct the errors in the form.', confirmButtonColor: '#ef4444' });
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', (e) => {
            if(!validatePassword()) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Check Inputs', text: 'Please ensure passwords match and meet requirements.', confirmButtonColor: '#ef4444' });
            }
        });

        // Real-time feedback
        document.querySelectorAll('.validation-field').forEach(el => {
            el.addEventListener('input', (e) => clearError(e.target));
        });
    });
</script>
</body>
</html>