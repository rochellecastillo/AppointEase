<?php
// edit_patient.php - Update Patient Profile
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

// Enforce Admin Access
session_require_auth(['admin']);

// 1. Get Patient ID
$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    header('Location: users_list.php');
    exit;
}

// 2. Handle Form Submission
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect inputs
        $fname = trim($_POST['first_name']);
        $mname = trim($_POST['middle_name']);
        $lname = trim($_POST['last_name']);
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $gender = $_POST['gender'];
        $bdate = $_POST['bdate'];

        // Handle Image Upload
        $image_sql = "";
        $params = [$fname, $mname, $lname, $contact, $email, $address, $gender, $bdate];

        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "pat_" . $patient_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($file_ext, $allowed)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_sql = ", image = ?";
                    $params[] = $new_filename;
                } else {
                    throw new Exception("Failed to upload image.");
                }
            } else {
                throw new Exception("Invalid file format.");
            }
        }

        // Update Query
        $sql = "UPDATE tblinfo SET 
                first_name = ?, middle_name = ?, last_name = ?, 
                contact = ?, email = ?, address = ?, gender = ?, bdate = ? 
                $image_sql 
                WHERE user_id = ?";
        
        $params[] = $patient_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $msg = "Patient profile updated successfully.";
            $msg_type = "success";
        } else {
            $msg = "Failed to update database.";
            $msg_type = "error";
        }

    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "error";
    }
}

// 3. Fetch Existing Data
try {
    $stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Patient not found.");
    }
    
    $stmtUser = $pdo->prepare("SELECT status FROM tbluser WHERE user_id = ?");
    $stmtUser->execute([$patient_id]);
    $userStatus = $stmtUser->fetchColumn();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 overflow-auto">
            <div class="p-8 max-w-5xl mx-auto">
                
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <a href="users_list.php" class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 mb-2 transition">
                            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to List
                        </a>
                        <h1 class="text-3xl font-bold text-gray-900">Edit Patient Profile</h1>
                        <p class="text-gray-500">Update information for <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-sm font-bold <?= $userStatus == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $userStatus == 1 ? 'Active Account' : 'Inactive Account' ?>
                        </span>
                    </div>
                </div>

                <?php if ($msg): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-2 <?= $msg_type == 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                        <i data-lucide="<?= $msg_type == 'success' ? 'check-circle' : 'alert-circle' ?>" width="20"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="space-y-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                            <div class="relative inline-block group">
                                <?php 
                                    $imgSrc = !empty($patient['image']) ? 'uploads/' . e($patient['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($patient['first_name'] . '+' . $patient['last_name']);
                                ?>
                                <img src="<?= $imgSrc ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover mx-auto border-4 border-purple-50 shadow-sm mb-4">
                                
                                <label for="upload_image" class="absolute bottom-2 right-2 bg-white p-2 rounded-full shadow-md cursor-pointer hover:text-purple-600 transition border border-gray-200">
                                    <i data-lucide="camera" width="16"></i>
                                    <input type="file" name="image" id="upload_image" class="hidden" accept="image/*">
                                </label>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900"><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></h2>
                            <p class="text-xs text-gray-400 mt-2">ID: <?= e($patient['user_id']) ?></p>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Personal Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">First Name</label>
                                    <input type="text" name="first_name" value="<?= e($patient['first_name']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Middle Name</label>
                                    <input type="text" name="middle_name" value="<?= e($patient['middle_name']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Last Name</label>
                                    <input type="text" name="last_name" value="<?= e($patient['last_name']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" name="bdate" value="<?= e($patient['bdate']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Gender</label>
                                    <select name="gender" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                        <option value="Male" <?= $patient['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $patient['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= $patient['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100 mt-8">Contact Details</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Contact Number</label>
                                    <input type="text" name="contact" value="<?= e($patient['contact']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" name="email" value="<?= e($patient['email']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Home Address</label>
                                <textarea name="address" rows="2" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition"><?= e($patient['address']) ?></textarea>
                            </div>

                            <div class="flex justify-end gap-4 pt-4 border-t border-gray-100">
                                <a href="users_list.php" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">Cancel</a>
                                <button type="submit" class="px-6 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 shadow-lg shadow-purple-200 transition transform active:scale-95">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </main>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>