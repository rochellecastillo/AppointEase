<?php
// add_doctor.php - Add new doctor (Updated for new database)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_type']) !== 'admin') {
    header('Location: login.php');
    exit;
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$error = '';
$success = '';

// Fetch specializations
try {
    $stmt = $pdo->query("SELECT * FROM tblspecialization ORDER BY specialization");
    $specializations = $stmt->fetchAll();
} catch (Exception $e) {
    $specializations = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $specialization = $_POST['specialization'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $bdate = $_POST['bdate'] ?? '';
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'doctor_' . time() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = $new_filename;
            }
        }
    }
    
    if (empty($first_name) || empty($last_name) || empty($gender) || empty($bdate) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Generate user_id (U + date + counter)
            $stmt = $pdo->query("SELECT COUNT(*) FROM tbluser WHERE DATE(NOW()) = DATE(NOW())");
            $count = $stmt->fetchColumn() + 1;
            $user_id = 'U' . date('ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            
            // Check if username exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbluser WHERE user_name = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Username already exists");
            }
            
            // Insert into tblinfo
            $stmt = $pdo->prepare("INSERT INTO tblinfo (user_id, first_name, last_name, middle_name, specialization, bdate, gender, contact, address, image) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $first_name, $last_name, $middle_name, $specialization, $bdate, $gender, $contact, $address, $image]);
            
            // Insert into tbluser
            $stmt = $pdo->prepare("INSERT INTO tbluser (user_id, user_name, password, user_type, status) 
                                   VALUES (?, ?, ?, 'doctor', 1)");
            $stmt->execute([$user_id, $username, $password]);
            
            $pdo->commit();
            $success = "Doctor added successfully! User ID: $user_id";
            
        } catch (Exception $e) {
            $pdo->rollBack();
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
    <title>Add Doctor - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Add New Doctor</h1>
                    <p class="text-gray-600">Register a new doctor to the system</p>
                </div>
                <a href="doctors_info_report.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    ← Back to Doctors List
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">First Name *</label>
                            <input type="text" name="first_name" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Last Name *</label>
                            <input type="text" name="last_name" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Middle Name</label>
                        <input type="text" name="middle_name" 
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Specialization</label>
                        <select name="specialization" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Specialization</option>
                            <?php foreach ($specializations as $spec): ?>
                                <option value="<?= e($spec['specialization']) ?>"><?= e($spec['specialization']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Gender *</label>
                            <select name="gender" required 
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Birth Date *</label>
                            <input type="date" name="bdate" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Contact Number</label>
                        <input type="text" name="contact" 
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="09XXXXXXXXX">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Address</label>
                        <textarea name="address" rows="3" 
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Profile Image</label>
                        <input type="file" name="image" accept="image/*"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Optional: Upload a profile picture (JPG, PNG, GIF)</p>
                    </div>

                    <hr class="my-6">

                    <h3 class="text-lg font-bold text-gray-800 mb-4">Login Credentials</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Username *</label>
                            <input type="text" name="username" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Password *</label>
                            <input type="password" name="password" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                            Add Doctor
                        </button>
                        <a href="admin_home.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>