<?php
// client_settings.php - Account & Profile Management
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

$message = '';
$msg_type = '';

// --- 1. HANDLE PROFILE PICTURE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            if ($file['size'] <= 2 * 1024 * 1024) { // 2MB Limit
                $new_name = "avatar_" . $user_id . "_" . time() . "." . $ext;
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                    // Update DB
                    $stmt = $pdo->prepare("UPDATE tblinfo SET image = ? WHERE user_id = ?");
                    $stmt->execute([$new_name, $user_id]);
                    $message = "Profile picture updated!";
                    $msg_type = "success";
                } else {
                    $message = "Failed to move uploaded file.";
                    $msg_type = "error";
                }
            } else {
                $message = "File size must be less than 2MB.";
                $msg_type = "error";
            }
        } else {
            $message = "Invalid file type. Only JPG, PNG, and WEBP allowed.";
            $msg_type = "error";
        }
    }
}

// --- 2. HANDLE PERSONAL INFO UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $sql = "UPDATE tblinfo SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                contact = ?, 
                address = ?, 
                bdate = ?, 
                gender = ? 
                WHERE user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['contact'],
            $_POST['address'],
            $_POST['bdate'],
            $_POST['gender'],
            $user_id
        ]);

        $message = "Personal information updated successfully.";
        $msg_type = "success";
    } catch (Exception $e) {
        $message = "Error updating profile: " . $e->getMessage();
        $msg_type = "error";
    }
}

// --- 3. HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match.";
        $msg_type = "error";
    } elseif (strlen($new_pass) < 8) {
        $message = "Password must be at least 8 characters.";
        $msg_type = "error";
    } else {
        // Verify old password
        $stmt = $pdo->prepare("SELECT password FROM tbluser WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_pass, $user['password'])) {
            // Hash new password using secure helper or native function
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $update = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
            $update->execute([$new_hash, $user_id]);
            
            $message = "Password changed successfully.";
            $msg_type = "success";
        } else {
            $message = "Incorrect current password.";
            $msg_type = "error";
        }
    }
}

// --- 4. FETCH CURRENT DATA ---
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
$stmt->execute([$user_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// Default Avatar Logic
$avatar = !empty($info['image']) ? 'uploads/' . e($info['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($info['first_name'] . '+' . $info['last_name']) . '&background=7c3aed&color=fff';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
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
                    <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $msg_type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
                        <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center sticky top-6">
                            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                <div class="relative inline-block mb-4 group">
                                    <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-purple-50 shadow-inner mx-auto">
                                        <img src="<?= $avatar ?>" alt="Profile" class="w-full h-full object-cover">
                                    </div>
                                    <label for="avatarInput" class="absolute bottom-0 right-0 bg-purple-600 text-white p-2 rounded-full cursor-pointer hover:bg-purple-700 transition shadow-lg border-2 border-white">
                                        <i data-lucide="camera" class="w-4 h-4"></i>
                                    </label>
                                    <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
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
                            
                            <form method="POST">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">First Name</label>
                                        <input type="text" name="first_name" value="<?= e($info['first_name']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Middle Name</label>
                                        <input type="text" name="middle_name" value="<?= e($info['middle_name']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Last Name</label>
                                        <input type="text" name="last_name" value="<?= e($info['last_name']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date of Birth</label>
                                        <input type="date" name="bdate" value="<?= e($info['bdate']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gender</label>
                                        <select name="gender" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                                            <option value="Male" <?= strtolower($info['gender']) == 'male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= strtolower($info['gender']) == 'female' ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= strtolower($info['gender']) == 'other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Contact Number</label>
                                        <input type="text" name="contact" value="<?= e($info['contact']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Address</label>
                                    <textarea name="address" rows="2" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition"><?= e($info['address']) ?></textarea>
                                </div>

                                <button type="submit" name="update_profile" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition shadow-md">
                                    Save Changes
                                </button>
                            </form>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-gray-100">
                                <i data-lucide="lock" class="text-red-500 w-5 h-5"></i>
                                <h3 class="text-lg font-bold text-gray-800">Security</h3>
                            </div>

                            <form method="POST">
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Password</label>
                                        <input type="password" name="current_password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-red-500 transition">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New Password</label>
                                            <input type="password" name="new_password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-red-500 transition">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirm New Password</label>
                                            <input type="password" name="confirm_password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-red-500 transition">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">
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
        lucide.createIcons();
        
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>