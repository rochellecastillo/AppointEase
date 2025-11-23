<?php
// doctor_settings.php - Doctor Profile & Security
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

session_require_auth(['doctor']);
$user_id = session_get_user_id();

$message = '';
$msg_type = '';

// --- 1. HANDLE AVATAR UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            if ($file['size'] <= 2 * 1024 * 1024) { // 2MB
                $new_name = "doc_" . $user_id . "_" . time() . "." . $ext;
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                    $stmt = $pdo->prepare("UPDATE tblinfo SET image = ? WHERE user_id = ?");
                    $stmt->execute([$new_name, $user_id]);
                    $message = "Profile photo updated!";
                    $msg_type = "success";
                } else {
                    $message = "Upload failed.";
                    $msg_type = "error";
                }
            } else {
                $message = "File too large (Max 2MB).";
                $msg_type = "error";
            }
        } else {
            $message = "Invalid file format.";
            $msg_type = "error";
        }
    }
}

// --- 2. HANDLE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $sql = "UPDATE tblinfo SET 
                first_name = ?, 
                last_name = ?, 
                specialization = ?,
                contact = ?, 
                address = ?, 
                bdate = ?, 
                gender = ? 
                WHERE user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['specialization'], // Unique to doctors
            $_POST['contact'],
            $_POST['address'],
            $_POST['bdate'],
            $_POST['gender'],
            $user_id
        ]);

        $message = "Profile updated successfully.";
        $msg_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "error";
    }
}

// --- 3. HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $message = "New passwords do not match.";
        $msg_type = "error";
    } elseif (strlen($new) < 8) {
        $message = "Password too short (min 8 chars).";
        $msg_type = "error";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM tbluser WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?")->execute([$hash, $user_id]);
            $message = "Password changed successfully.";
            $msg_type = "success";
        } else {
            $message = "Incorrect current password.";
            $msg_type = "error";
        }
    }
}

// --- 4. FETCH DATA ---
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
$stmt->execute([$user_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Specializations List
$specs = $pdo->query("SELECT specialization FROM tblspecialization ORDER BY specialization")->fetchAll(PDO::FETCH_COLUMN);

$avatar = !empty($info['image']) ? 'uploads/' . e($info['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($info['first_name'] . '+' . $info['last_name']) . '&background=16a34a&color=fff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/doctor_sidebar.php'; ?>

        <main class="flex-1 overflow-auto w-full">
            <!-- Mobile Header -->
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
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-2 <?= $msg_type == 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left: Avatar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 text-center sticky top-6 shadow-sm">
                            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                <div class="relative inline-block mb-4 group">
                                    <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-slate-50 shadow-inner mx-auto">
                                        <img src="<?= $avatar ?>" alt="Profile" class="w-full h-full object-cover">
                                    </div>
                                    <label for="avatarInput" class="absolute bottom-0 right-0 bg-green-600 text-white p-2 rounded-full cursor-pointer hover:bg-green-700 transition shadow-lg border-2 border-white">
                                        <i data-lucide="camera" class="w-4 h-4"></i>
                                    </label>
                                    <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                                </div>
                                <h2 class="text-lg font-bold text-slate-800">Dr. <?= e($info['last_name']) ?></h2>
                                <p class="text-sm text-green-600 font-medium"><?= e($info['specialization']) ?></p>
                                <p class="text-xs text-slate-400 mt-1">ID: <?= e($user_id) ?></p>
                            </form>
                        </div>
                    </div>

                    <!-- Right: Forms -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- 1. Professional Profile -->
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-slate-100">
                                <i data-lucide="user-cog" class="text-green-600 w-5 h-5"></i>
                                <h3 class="text-lg font-bold text-slate-800">Professional Profile</h3>
                            </div>
                            
                            <form method="POST">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                                        <input type="text" name="first_name" value="<?= e($info['first_name']) ?>" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                                        <input type="text" name="last_name" value="<?= e($info['last_name']) ?>" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Specialization</label>
                                    <select name="specialization" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 bg-white">
                                        <option value="">Select...</option>
                                        <?php foreach($specs as $s): ?>
                                            <option value="<?= e($s) ?>" <?= ($info['specialization'] == $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone Number</label>
                                        <input type="text" name="contact" value="<?= e($info['contact']) ?>" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Gender</label>
                                        <select name="gender" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 bg-white">
                                            <option value="Male" <?= ($info['gender']=='Male')?'selected':'' ?>>Male</option>
                                            <option value="Female" <?= ($info['gender']=='Female')?'selected':'' ?>>Female</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Clinic Address / Office</label>
                                    <textarea name="address" rows="2" class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition"><?= e($info['address']) ?></textarea>
                                </div>

                                <!-- Hidden bdate handling if needed, or displayed -->
                                <input type="hidden" name="bdate" value="<?= e($info['bdate']) ?>">

                                <button type="submit" name="update_profile" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-md shadow-green-100">
                                    Save Changes
                                </button>
                            </form>
                        </div>

                        <!-- 2. Security -->
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-slate-100">
                                <i data-lucide="lock" class="text-red-500 w-5 h-5"></i>
                                <h3 class="text-lg font-bold text-slate-800">Security</h3>
                            </div>

                            <form method="POST">
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Current Password</label>
                                        <input type="password" name="current_password" required class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-red-500 transition">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">New Password</label>
                                            <input type="password" name="new_password" required class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-red-500 transition">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Confirm New</label>
                                            <input type="password" name="confirm_password" required class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:border-red-500 transition">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="px-6 py-3 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">
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
        
        // Mobile Sidebar
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-full'); 
            });
        }
    </script>
</body>
</html>