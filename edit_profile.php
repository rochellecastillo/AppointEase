<?php
// edit_profile.php - Edit patient profile
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_type']) !== 'user') {
    header('Location: login.php');
    exit;
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user info
try {
    $stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $info = $stmt->fetch();
    
    if (!$info) {
        $error = "Profile not found";
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $bdate = $_POST['bdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    $image = $info['image'] ?? '';
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'patient_' . time() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if (!empty($info['image']) && file_exists('uploads/' . $info['image'])) {
                    unlink('uploads/' . $info['image']);
                }
                $image = $new_filename;
            }
        }
    }
    
    if (empty($first_name) || empty($last_name) || empty($bdate) || empty($gender)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE tblinfo SET 
                                   first_name = ?, last_name = ?, middle_name = ?, 
                                   bdate = ?, gender = ?, contact = ?, address = ?, image = ?
                                   WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $middle_name, $bdate, $gender, $contact, $address, $image, $user_id]);
            
            $success = "Profile updated successfully!";
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $info = $stmt->fetch();
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Edit Profile</h1>
                    <p class="text-gray-600">Update your personal information</p>
                </div>
                <a href="client_home.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                    ← Back to Dashboard
                </a>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow p-6">
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Profile Image -->
                    <div class="mb-6 text-center">
                        <?php 
                        $image_path = !empty($info['image']) ? 'uploads/' . $info['image'] : 'https://via.placeholder.com/150';
                        ?>
                        <img id="profilePreview" src="<?= e($image_path) ?>" alt="Profile" 
                             class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-purple-100">
                        <label class="cursor-pointer bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm inline-block">
                            Change Photo
                            <input type="file" name="image" accept="image/*" class="hidden" onchange="previewImage(this)">
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">First Name *</label>
                            <input type="text" name="first_name" value="<?= e($info['first_name'] ?? '') ?>" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Last Name *</label>
                            <input type="text" name="last_name" value="<?= e($info['last_name'] ?? '') ?>" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Middle Name</label>
                        <input type="text" name="middle_name" value="<?= e($info['middle_name'] ?? '') ?>" 
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Birth Date *</label>
                            <input type="date" name="bdate" value="<?= e($info['bdate'] ?? '') ?>" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Gender *</label>
                            <select name="gender" required 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="male" <?= ($info['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($info['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Contact Number</label>
                        <input type="text" name="contact" value="<?= e($info['contact'] ?? '') ?>" 
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                               placeholder="09XXXXXXXXX">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Address</label>
                        <textarea name="address" rows="3" 
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?= e($info['address'] ?? '') ?></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold">
                            Save Changes
                        </button>
                        <a href="client_home.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>