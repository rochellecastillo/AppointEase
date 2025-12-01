<?php
include __DIR__ . '/controllers/edit_doctor_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .error-border { border-color: #ef4444 !important; }
        .error-text { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
    </style>
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
                        <p class="text-gray-500">Update information for Dr. <?= htmlspecialchars($doctor['last_name']) ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-sm font-bold <?= $userStatus == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $userStatus == 1 ? 'Active Account' : 'Inactive Account' ?>
                        </span>
                    </div>
                </div>

                <?php if ($msg): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: '<?= $msg_type ?>',
                                title: '<?= $msg_type === "success" ? "Success" : "Error" ?>',
                                text: '<?= htmlspecialchars($msg) ?>',
                                confirmButtonColor: '#9333ea'
                            });
                        });
                    </script>
                <?php endif; ?>

                <form id="editDoctorForm" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8" novalidate>
                    
                    <div class="space-y-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                            <div class="relative inline-block group">
                                <?php 
                                    $imgSrc = !empty($doctor['image']) ? 'uploads/' . htmlspecialchars($doctor['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($doctor['first_name'] . '+' . $doctor['last_name']);
                                ?>
                                <img id="preview_img" src="<?= $imgSrc ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover mx-auto border-4 border-purple-50 shadow-sm mb-4 transition-all duration-300">
                                
                                <label for="upload_image" class="absolute bottom-2 right-2 bg-white p-2 rounded-full shadow-md cursor-pointer hover:text-purple-600 transition border border-gray-200">
                                    <i data-lucide="camera" width="16"></i>
                                    <input type="file" name="image" id="upload_image" class="hidden" accept="image/png, image/jpeg, image/jpg, image/gif" onchange="previewImage(this)">
                                </label>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></h2>
                            <p class="text-purple-600 font-medium"><?= htmlspecialchars($doctor['specialization'] ?: 'General Practitioner') ?></p>
                            <p class="text-xs text-gray-400 mt-2">ID: <?= htmlspecialchars($doctor['user_id']) ?></p>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Personal Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($doctor['first_name']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name" value="<?= htmlspecialchars($doctor['middle_name'] ?? '') ?>" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($doctor['last_name']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Date of Birth <span class="text-red-500">*</span></label>
                                    <input type="date" name="bdate" id="bdate" value="<?= htmlspecialchars($doctor['bdate']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Gender <span class="text-red-500">*</span></label>
                                    <select name="gender" id="gender" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= $doctor['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $doctor['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= $doctor['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                    <span class="error-msg"></span>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100 mt-8">Professional Details</h3>

                            <div class="mb-6 form-group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Specialization <span class="text-red-500">*</span></label>
                                <select name="specialization" id="specialization" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <option value="">Select Specialization</option>
                                    <?php
                                    $specs = ['General Practitioner', 'Cardiologist', 'Dermatologist', 'Pediatrician', 'Neurologist', 'Orthopedic', 'Psychiatrist', 'Surgeon', 'Dentist', 'Ophthalmologist'];
                                    foreach ($specs as $s) {
                                        $selected = ($doctor['specialization'] == $s) ? 'selected' : '';
                                        echo "<option value='$s' $selected>$s</option>";
                                    }
                                    ?>
                                    <?php if (!in_array($doctor['specialization'], $specs) && !empty($doctor['specialization'])): ?>
                                        <option value="<?= htmlspecialchars($doctor['specialization']) ?>" selected><?= htmlspecialchars($doctor['specialization']) ?></option>
                                    <?php endif; ?>
                                </select>
                                <span class="error-msg"></span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="form-group">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Contact Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="contact" id="contact" value="<?= htmlspecialchars($doctor['contact']) ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field">
                                    <span class="error-msg"></span>
                                </div>
                            </div>

                            <div class="mb-8 form-group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Clinic Address / Room <span class="text-red-500">*</span></label>
                                <textarea name="address" id="address" rows="2" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-purple-500 outline-none transition validation-field"><?= htmlspecialchars($doctor['address']) ?></textarea>
                                <span class="error-msg"></span>
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

        function previewImage(input) {
            const preview = document.getElementById('preview_img');
            const file = input.files[0];
            
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Please select a valid image file (JPG, PNG, GIF).', confirmButtonColor: '#9333ea' });
                    input.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Image size should not exceed 5MB.', confirmButtonColor: '#9333ea' });
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) { preview.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('editDoctorForm');
            const fields = document.querySelectorAll('.validation-field');

            const patterns = {
                first_name: /^[a-zA-Z\s.-]{2,50}$/, 
                middle_name: /^[a-zA-Z\s.-]*$/, 
                last_name: /^[a-zA-Z\s.-]{2,50}$/,
                contact: /^(09|\+639)\d{9}$/,
                address: /.+/,
                specialization: /.+/
            };

            const errorMessages = {
                first_name: "Please enter a valid name (letters only).",
                middle_name: "Please enter a valid middle name (letters only).",
                last_name: "Please enter a valid last name.",
                contact: "Enter valid PH number (e.g., 09123456789).",
                address: "Address is required.",
                bdate: "Date of birth is required.",
                gender: "Please select a gender.",
                specialization: "Please select a specialization."
            };

            const showError = (input, message) => {
                const group = input.closest('.form-group');
                const errorSpan = group.querySelector('.error-msg');
                input.classList.add('error-border', 'bg-red-50');
                if(errorSpan) { errorSpan.textContent = message; errorSpan.classList.add('error-text'); }
                return false;
            };

            const clearError = (input) => {
                const group = input.closest('.form-group');
                const errorSpan = group.querySelector('.error-msg');
                input.classList.remove('error-border','bg-red-50');
                if(errorSpan) { errorSpan.textContent = ''; }
                return true;
            };

            const validateField = (input) => {
                const name = input.name;
                const value = input.value.trim();
                if (value === '' && name !== 'middle_name') return showError(input, errorMessages[name] || "This field is required.");
                if (patterns[name] && value !== '' && !patterns[name].test(value)) return showError(input, errorMessages[name]);
                if (name === 'bdate') {
                    const dob = new Date(value), today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    if(today.getMonth()-dob.getMonth()<0||(today.getMonth()-dob.getMonth()===0 && today.getDate()<dob.getDate())) age--;
                    if(age < 20) return showError(input, "Doctor must be at least 20 years old.");
                }
                return clearError(input);
            };

            fields.forEach(input => input.addEventListener('input', () => validateField(input)));

            form.addEventListener('submit', e => {
                let valid = true;
                fields.forEach(input => { if(!validateField(input)) valid=false; });
                const genderField = document.getElementById('gender');
                if(genderField.value === '') { showError(genderField,errorMessages['gender']); valid=false; } else clearError(genderField);
                if(!valid) { e.preventDefault(); Swal.fire({icon:'error', title:'Fix Errors', text:'Please fix all errors before submitting.', confirmButtonColor:'#9333ea'}); }
            });
        });
    </script>
</body>
</html>