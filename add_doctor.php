<?php
// add_doctor.php - Step 1: Data Collection & OTP Generation
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';
require_once 'iprog_sms.php'; // Include SMS helper

// Enforce Admin Access
session_require_auth(['admin']);

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Collect & Sanitize Inputs
        $fname = trim($_POST['first_name'] ?? '');
        $mname = trim($_POST['middle_name'] ?? ''); 
        $lname = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $contact = trim($_POST['contact'] ?? '');
        $spec = trim($_POST['specialization'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $bdate = $_POST['bdate'] ?? '';
        $address = trim($_POST['address'] ?? '');

        // FIX: Define contact_norm immediately here so it is available for OTP later
        $contact_norm = normalize_phone_ph($contact);

        // 2. Validation
        if (empty($fname) || empty($lname) || empty($username) || empty($email) || empty($password) || empty($spec) || empty($contact)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Check if Username already exists
        $stmt = $pdo->prepare("SELECT user_id FROM tbluser WHERE user_name = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username is already taken.");
        }

        // Check if Contact already exists (Using the normalized variable)
        $stmt = $pdo->prepare("SELECT id FROM tblinfo WHERE contact = ?");
        $stmt->execute([$contact_norm]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Contact number already registered.");
        }

        // 3. Handle Image Upload
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed)) throw new Exception("Invalid file format (JPG, PNG, GIF only).");
            
            $new_filename = "doc_" . time() . "." . $file_ext;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_filename)) {
                $image_filename = $new_filename;
            } else {
                throw new Exception("Failed to upload image.");
            }
        }

        // 4. Store Session
        // We use $contact_norm here, which is now definitely defined
        $_SESSION['otp_action'] = 'add_doctor';
        $_SESSION['otp_payload'] = [
            'first_name' => $fname,
            'middle_name' => $mname,
            'last_name' => $lname,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'contact' => $contact_norm, // Ensure this uses the normalized variable
            'specialization' => $spec,
            'gender' => $gender,
            'bdate' => $bdate,
            'address' => $address,
            'image' => $image_filename,
            'otp_expires' => time() + (5 * 60)
        ];

        // 5. Send OTP
        // Pass the normalized string, not null
        $otp_res = iprog_send_otp($contact_norm);
        
        if ($otp_res['success']) {
            header("Location: verify_otp.php");
            exit();
        } else {
            if($image_filename && file_exists("uploads/$image_filename")) unlink("uploads/$image_filename");
            throw new Exception("Failed to send OTP. Check number or network.");
        }

    } catch (Exception $e) {
        $msg = $e->getMessage();
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Doctor - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .error-border { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
        .error-text { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; display: block; }
    </style>
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
                    <p class="text-gray-500">Step 1: Enter Details & Verify Phone</p>
                </div>

                <?php if ($msg): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            Swal.fire({
                                icon: '<?= $msg_type ?>',
                                title: '<?= $msg_type === "success" ? "Success" : "Error" ?>',
                                text: '<?= htmlspecialchars($msg) ?>',
                                confirmButtonColor: '#ef4444'
                            });
                        });
                    </script>
                <?php endif; ?>

                <form id="addDoctorForm" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8" novalidate>
                    
                    <div class="space-y-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                            <div class="relative inline-block group mb-4">
                                <div class="w-32 h-32 rounded-full bg-gray-100 border-4 border-dashed border-gray-300 flex items-center justify-center text-gray-400 overflow-hidden relative">
                                    <img id="preview" class="w-full h-full object-cover hidden">
                                    <span id="placeholder"><i data-lucide="image" width="32"></i></span>
                                </div>
                                <label for="upload_image" class="absolute bottom-0 right-0 bg-purple-600 text-white p-2 rounded-full shadow-md cursor-pointer hover:bg-purple-700 transition">
                                    <i data-lucide="camera" width="16"></i>
                                    <input type="file" name="image" id="upload_image" class="hidden" accept="image/png, image/jpeg, image/jpg, image/gif" onchange="previewImage(this)">
                                </label>
                            </div>
                            <p class="text-sm text-gray-500">Profile Picture (Optional)</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="lock" class="w-4 h-4 text-purple-600"></i> Login Credentials
                            </h3>
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Username <span class="text-red-500">*</span></label>
                                    <input type="text" name="username" id="username" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition text-sm validation-field">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" id="password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition text-sm validation-field">
                                    <span class="error-msg"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Professional Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" id="first_name" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" id="last_name" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                            </div>

                            <div class="mb-6 form-group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Specialization <span class="text-red-500">*</span></label>
                                <select name="specialization" id="specialization" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <option value="">Select Specialization</option>
                                    <?php
                                    $specs = ['General Practitioner', 'Cardiologist', 'Dermatologist', 'Pediatrician', 'Neurologist', 'Orthopedic', 'Psychiatrist', 'Surgeon', 'Dentist', 'Ophthalmologist'];
                                    foreach ($specs as $s) echo "<option value='$s'>$s</option>";
                                    ?>
                                </select>
                                <span class="error-msg"></span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Date of Birth <span class="text-red-500">*</span></label>
                                    <input type="date" name="bdate" id="bdate" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Gender <span class="text-red-500">*</span></label>
                                    <select name="gender" id="gender" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    <span class="error-msg"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Contact Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="contact" id="contact" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <p class="text-xs text-blue-600 mt-1">An OTP will be sent to this number.</p>
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" id="email" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                            </div>

                            <div class="mb-8 form-group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Clinic Address / Room <span class="text-red-500">*</span></label>
                                <textarea name="address" id="address" rows="2" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field"></textarea>
                                <span class="error-msg"></span>
                            </div>

                            <div class="flex justify-end gap-4 pt-4 border-t border-gray-100">
                                <button type="reset" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">Reset</button>
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition transform active:scale-95">
                                    Send OTP & Register
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

        // 1. Image Preview
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const placeholder = document.getElementById('placeholder');
            const file = input.files[0];
            
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Please use JPG, PNG, or GIF.', confirmButtonColor: '#ef4444' });
                    input.value = '';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Max size is 5MB.', confirmButtonColor: '#ef4444' });
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        // 2. JS Validation
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('addDoctorForm');
            const fields = document.querySelectorAll('.validation-field');

            const patterns = {
                first_name: /^[a-zA-Z\s.-]{2,50}$/,
                middle_name: /^[a-zA-Z\s.-]*$/, // Optional
                last_name: /^[a-zA-Z\s.-]{2,50}$/,
                username: /^[a-zA-Z0-9_]{4,20}$/,
                contact: /^(09|\+639)\d{9}$/,
                email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                address: /.+/,
                specialization: /.+/,
                bdate: /.+/,
                gender: /.+/,
                password: /^.{8,}$/ // Min 8 chars
            };

            const messages = {
                first_name: "Letters only, min 2 chars.",
                middle_name: "Letters only.",
                last_name: "Letters only, min 2 chars.",
                username: "Alphanumeric, 4-20 chars.",
                contact: "Invalid PH Number (e.g. 09123456789).",
                email: "Invalid email format.",
                address: "Required.",
                specialization: "Required.",
                bdate: "Required.",
                gender: "Required.",
                password: "Min 8 characters."
            };

            const showError = (input, msg) => {
                input.classList.add('error-border');
                const group = input.closest('.form-group');
                if(group) {
                    let span = group.querySelector('.error-msg');
                    if(!span) { span = document.createElement('span'); span.className = 'error-msg error-text'; group.appendChild(span); }
                    span.textContent = msg;
                }
                return false;
            };

            const clearError = (input) => {
                input.classList.remove('error-border');
                const group = input.closest('.form-group');
                if(group) {
                    const span = group.querySelector('.error-msg');
                    if(span) span.textContent = '';
                }
                return true;
            };

            const validate = (input) => {
                const name = input.id;
                const val = input.value.trim();
                
                if (input.hasAttribute('required') && val === '') return showError(input, "Required.");
                if (val !== '' && patterns[name] && !patterns[name].test(val)) return showError(input, messages[name]);
                
                // Age Check
                if (name === 'bdate' && val) {
                    const age = new Date().getFullYear() - new Date(val).getFullYear();
                    if (age < 20) return showError(input, "Doctor must be 20+ years old.");
                }

                return clearError(input);
            };

            fields.forEach(f => {
                f.addEventListener('input', () => validate(f));
                f.addEventListener('blur', () => validate(f));
            });

            form.addEventListener('submit', (e) => {
                let valid = true;
                fields.forEach(f => { if(!validate(f)) valid = false; });

                if (!valid) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Validation Error', text: 'Please correct the highlighted errors.', confirmButtonColor: '#ef4444' });
                }
            });
        });
    </script>
</body>
</html>