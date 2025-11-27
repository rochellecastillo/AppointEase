<?php
// add_doctor.php - Register New Medical Staff
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Admin Access
session_require_auth(['admin']);

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Collect & Sanitize Inputs
        $fname = trim($_POST['first_name']);
        $mname = trim($_POST['middle_name']); 
        $lname = trim($_POST['last_name']);
        
        // Login Credentials
        $username = trim($_POST['username']); // Added Username
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Contact Info
        $contact = trim($_POST['contact']);
        $spec = trim($_POST['specialization']);
        $gender = $_POST['gender'];
        $bdate = $_POST['bdate'];
        $address = trim($_POST['address']);

        // 2. Validation
        if (empty($fname) || empty($lname) || empty($username) || empty($email) || empty($password) || empty($spec)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Check if Username already exists in tbluser
        $stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE user_name = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username is already taken.");
        }

        // Check if Contact already exists in tblinfo
        $stmt = $pdo->prepare("SELECT id FROM tblinfo WHERE contact = ?");
        $stmt->execute([$contact]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("This contact number is already registered.");
        }

        // 3. Generate Unique User ID
        $user_id = 'DOC-' . time() . '-' . rand(10, 99);

        // 4. Handle Image Upload
        $image_filename = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        if (!empty($_FILES['image']['name'])) {

            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_ext, $allowed)) {
                throw new Exception("Invalid file format. Only JPG, PNG & GIF allowed.");
            }

            $new_filename = "doc_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_filename;

            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                throw new Exception("Failed to upload image.");
            }

            $image_filename = $new_filename;
        }
    }
        // 5. Insert Data (Using Transaction for Safety)
        $pdo->beginTransaction();

        // A. Insert into tblinfo (Personal Details)
        $sqlInfo = "INSERT INTO tblinfo (user_id, first_name, middle_name, last_name, specialization, contact, address, gender, bdate, email, image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInfo = $pdo->prepare($sqlInfo);
        $stmtInfo->execute([$user_id, $fname, $mname, $lname, $spec, $contact, $address, $gender, $bdate, $email, $image_filename]);

        // B. Insert into tbluser (Login Credentials)
        // Uses $username for user_name column
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sqlUser = "INSERT INTO tbluser (user_id, user_name, password, user_type, status) VALUES (?, ?, ?, 'doctor', 1)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$user_id, $username, $hashed_password]);

        $pdo->commit();

        $msg = "Doctor registered successfully! ID: " . $user_id;
        $msg_type = "success";
        
        $_POST = []; // Clear form

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor - AppointEase</title>
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
                
                <div class="mb-8">
                    <a href="doctors_info_report.php" class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 mb-2 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to List
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">Register New Doctor</h1>
                    <p class="text-gray-500">Create a new account for medical staff.</p>
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
                            <div class="relative inline-block group mb-4">
                                <div class="w-32 h-32 rounded-full bg-gray-100 border-4 border-dashed border-gray-300 flex items-center justify-center text-gray-400 overflow-hidden relative">
                                    <img id="preview" class="w-full h-full object-cover hidden">
                                    <span id="placeholder"><i data-lucide="image" width="32"></i></span>
                                </div>
                                <label for="upload_image" class="absolute bottom-0 right-0 bg-purple-600 text-white p-2 rounded-full shadow-md cursor-pointer hover:bg-purple-700 transition">
                                    <i data-lucide="camera" width="16"></i>
                                    <input type="file" name="image" id="upload_image" class="hidden" accept="image/*" onchange="previewImage(this)">
                                </label>
                            </div>
                            <p class="text-sm text-gray-500">Upload Profile Picture</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="lock" class="w-4 h-4 text-purple-600"></i> Login Credentials
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Username</label>
                                    <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Password</label>
                                    <input type="password" name="password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Professional Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">First Name</label>
                                    <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Middle Name</label>
                                    <input type="text" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Last Name</label>
                                    <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Specialization</label>
                                <select name="specialization" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                    <option value="" disabled selected>Select Specialization</option>
                                    <?php
                                    $specs = ['General Practitioner', 'Cardiologist', 'Dermatologist', 'Pediatrician', 'Neurologist', 'Orthopedic', 'Psychiatrist', 'Surgeon', 'Dentist', 'Ophthalmologist'];
                                    foreach ($specs as $s) echo "<option value='$s'>$s</option>";
                                    ?>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" name="bdate" required value="<?= htmlspecialchars($_POST['bdate'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Gender</label>
                                    <select name="gender" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Contact Number</label>
                                    <input type="text" name="contact" required value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition">
                                </div>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Clinic Address / Room</label>
                                <textarea name="address" rows="2" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                            </div>

                            <div class="flex justify-end gap-4 pt-4 border-t border-gray-100">
                                <button type="reset" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">Reset</button>
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition transform active:scale-95">
                                    Register Doctor
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

        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('preview').classList.remove('hidden');
                    document.getElementById('placeholder').classList.add('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>