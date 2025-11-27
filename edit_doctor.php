<?php
// edit_doctor.php - Update Doctor Profile
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Admin Access
session_require_auth(['admin']);

// 1. Get Doctor ID
$doctor_id = $_GET['id'] ?? null;
if (!$doctor_id) {
    header('Location: doctors_info_report.php');
    exit;
}

// 2. Handle Form Submission
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect inputs
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $spec = trim($_POST['specialization']);
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $gender = $_POST['gender'];
        $bdate = $_POST['bdate'];

        // Handle Image Upload
        $image_sql = "";
        $params = [$fname, $lname, $spec, $contact, $email, $address, $gender, $bdate];

        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            // Ensure directory exists
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "doc_" . $doctor_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            // Allow certain file formats
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($file_ext, $allowed)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_sql = ", image = ?";
                    $params[] = $new_filename; // Add filename to params
                } else {
                    throw new Exception("Failed to upload image.");
                }
            } else {
                throw new Exception("Invalid file format. Only JPG, PNG & GIF allowed.");
            }
        }

        // Update Query
        $sql = "UPDATE tblinfo SET 
                first_name = ?, last_name = ?, specialization = ?, 
                contact = ?, email = ?, address = ?, gender = ?, bdate = ? 
                $image_sql 
                WHERE user_id = ?";
        
        // Add ID to params
        $params[] = $doctor_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $msg = "Doctor profile updated successfully.";
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
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        die("Doctor not found.");
    }
    
    // Fetch status from tbluser
    $stmtUser = $pdo->prepare("SELECT status FROM tbluser WHERE user_id = ?");
    $stmtUser->execute([$doctor_id]);
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
    <title>Edit Doctor - AppointEase</title>
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
                        <a href="doctors_info_report.php" class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 mb-2 transition">
                            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to List
                        </a>
                        <h1 class="text-3xl font-bold text-gray-900">Edit Doctor Profile</h1>
                        <p class="text-gray-500">Update information for Dr. <?= e($doctor['last_name']) ?></p>
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
                                    $imgSrc = !empty($doctor['image']) ? 'uploads/' . e($doctor['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($doctor['first_name'] . '+' . $doctor['last_name']);
                                ?>
                                <img src="<?= $imgSrc ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover mx-auto border-4 border-purple-50 shadow-sm mb-4">
                                
                                <label for="upload_image" class="absolute bottom-2 right-2 bg-white p-2 rounded-full shadow-md cursor-pointer hover:text-purple-600 transition border border-gray-200">
                                    <i data-lucide="camera" width="16"></i>
                                    <input type="file" name="image" id="upload_image" class="hidden" accept="image/*">
                                </label>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Dr. <?= e($doctor['first_name'] . ' ' . $doctor['last_name']) ?></h2>
                            <p class="text-purple-600 font-medium"><?= e($doctor['specialization'] ?: 'General Practitioner') ?></p>
                            <p class="text-xs text-gray-400 mt-2">ID: <?= e($doctor['user_id']) ?></p>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Personal Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">First Name</label>
                                    <input type="text" name="first_name" value="<?= e($doctor['first_name']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Last Name</label>
                                    <input type="text" name="last_name" value="<?= e($doctor['last_name']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" name="bdate" value="<?= e($doctor['bdate']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Gender</label>
                                    <select name="gender" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                        <option value="Male" <?= $doctor['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $doctor['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= $doctor['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100 mt-8">Professional Details</h3>

                            <div class="mb-6">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Specialization</label>
                                <select name="specialization" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                    <?php
                                    $specs = ['General Practitioner', 'Cardiologist', 'Dermatologist', 'Pediatrician', 'Neurologist', 'Orthopedic', 'Psychiatrist', 'Surgeon', 'Dentist', 'Ophthalmologist'];
                                    foreach ($specs as $s) {
                                        $selected = ($doctor['specialization'] == $s) ? 'selected' : '';
                                        echo "<option value='$s' $selected>$s</option>";
                                    }
                                    ?>
                                    <option value="<?= e($doctor['specialization']) ?>" selected>Other (<?= e($doctor['specialization']) ?>)</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Contact Number</label>
                                    <input type="text" name="contact" value="<?= e($doctor['contact']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" name="email" value="<?= e($doctor['email']) ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Clinic Address / Room</label>
                                <textarea name="address" rows="2" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition"><?= e($doctor['address']) ?></textarea>
                            </div>

                            <div class="flex justify-end gap-4 pt-4 border-t border-gray-100">
                                <a href="doctors_info_report.php" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">Cancel</a>
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