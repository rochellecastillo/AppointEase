<?php
// health_history.php - Patient Personal Health Profile
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// --- 1. HANDLE FORM SUBMISSION (UPDATE PROFILE) ---
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_history'])) {
    try {
        // Check if profile exists
        $check = $pdo->prepare("SELECT id FROM tbl_health_profile WHERE user_id = ?");
        $check->execute([$user_id]);
        
        if ($check->rowCount() > 0) {
            // Update
            $sql = "UPDATE tbl_health_profile SET 
                    blood_type=?, height=?, weight=?, allergies=?, 
                    chronic_conditions=?, current_medications=?, past_surgeries=?, 
                    family_history=?, emergency_contact_name=?, emergency_contact_phone=? 
                    WHERE user_id=?";
        } else {
            // Insert
            $sql = "INSERT INTO tbl_health_profile 
                    (blood_type, height, weight, allergies, chronic_conditions, 
                     current_medications, past_surgeries, family_history, 
                     emergency_contact_name, emergency_contact_phone, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['blood_type'], $_POST['height'], $_POST['weight'], 
            $_POST['allergies'], $_POST['chronic_conditions'], 
            $_POST['current_medications'], $_POST['past_surgeries'], 
            $_POST['family_history'], $_POST['ec_name'], $_POST['ec_phone'], 
            $user_id
        ]);

        $message = "Health profile updated successfully!";
        $msg_type = 'success';
    } catch (Exception $e) {
        $message = "Error: " . e($e->getMessage());
        $msg_type = 'error';
    }
}

// --- 2. FETCH DATA ---
$profile = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM tbl_health_profile WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) { /* Ignore */ }

// Helper to safely get value
function val($key, $data) { return e($data[$key] ?? ''); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health History - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
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
                
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Health History</h1>
                        <p class="text-gray-500">Manage your personal health profile and emergency contacts.</p>
                    </div>
                    <button onclick="openEditModal()" class="flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white font-medium rounded-xl hover:bg-purple-700 transition shadow-md shadow-purple-200">
                        <i data-lucide="edit-2" class="w-4 h-4"></i> Update Profile
                    </button>
                </div>

                <?php if($message): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-2 <?= $msg_type == 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-white/20 rounded-lg"><i data-lucide="activity" class="w-5 h-5"></i></div>
                            <h3 class="font-bold text-lg">Vital Stats</h3>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                <p class="text-xs text-blue-100 uppercase mb-1">Blood Type</p>
                                <p class="text-2xl font-bold"><?= val('blood_type', $profile) ?: '-' ?></p>
                            </div>
                            <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                <p class="text-xs text-blue-100 uppercase mb-1">Height</p>
                                <p class="text-xl font-bold"><?= val('height', $profile) ?: '-' ?> <span class="text-xs font-normal">cm</span></p>
                            </div>
                            <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                <p class="text-xs text-blue-100 uppercase mb-1">Weight</p>
                                <p class="text-xl font-bold"><?= val('weight', $profile) ?: '-' ?> <span class="text-xs font-normal">kg</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-red-100 text-red-600 rounded-lg"><i data-lucide="phone-call" class="w-5 h-5"></i></div>
                            <h3 class="font-bold text-gray-800">Emergency Contact</h3>
                        </div>
                        <?php if(val('emergency_contact_name', $profile)): ?>
                            <div class="flex items-center justify-between bg-red-50 p-4 rounded-xl border border-red-100">
                                <div>
                                    <p class="text-sm text-gray-500">Name</p>
                                    <p class="font-bold text-gray-800 text-lg"><?= val('emergency_contact_name', $profile) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <a href="tel:<?= val('emergency_contact_phone', $profile) ?>" class="font-bold text-red-600 text-lg hover:underline">
                                        <?= val('emergency_contact_phone', $profile) ?>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400 italic text-sm">No emergency contact set.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-3">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="alert-circle" class="text-orange-500 w-5 h-5"></i> Medical Alerts
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-4 bg-orange-50 rounded-xl border border-orange-100">
                                <span class="text-xs font-bold text-orange-600 uppercase tracking-wide block mb-2">Allergies</span>
                                <p class="text-gray-700 leading-relaxed">
                                    <?= nl2br(val('allergies', $profile)) ?: '<span class="text-gray-400 italic">None reported</span>' ?>
                                </p>
                            </div>
                            <div class="p-4 bg-purple-50 rounded-xl border border-purple-100">
                                <span class="text-xs font-bold text-purple-600 uppercase tracking-wide block mb-2">Chronic Conditions</span>
                                <p class="text-gray-700 leading-relaxed">
                                    <?= nl2br(val('chronic_conditions', $profile)) ?: '<span class="text-gray-400 italic">None reported</span>' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <i data-lucide="pill" class="text-blue-500 w-5 h-5"></i> Current Medications
                                </h3>
                                <div class="prose prose-sm text-gray-600">
                                    <?= nl2br(val('current_medications', $profile)) ?: '<p class="italic text-gray-400">No medications listed.</p>' ?>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <i data-lucide="scissors" class="text-gray-500 w-5 h-5"></i> Past Surgeries / Operations
                                </h3>
                                <div class="prose prose-sm text-gray-600">
                                    <?= nl2br(val('past_surgeries', $profile)) ?: '<p class="italic text-gray-400">No surgeries listed.</p>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-3">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="users" class="text-green-500 w-5 h-5"></i> Family Medical History
                        </h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?= nl2br(val('family_history', $profile)) ?: '<span class="text-gray-400 italic">No family history recorded.</span>' ?>
                        </p>
                    </div>

                </div>

                <div class="mt-8 text-center text-xs text-gray-400">
                    Last updated: <?= val('updated_at', $profile) ? date('F d, Y h:i A', strtotime($profile['updated_at'])) : 'Never' ?>
                </div>

            </div>
        </main>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
        
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                
                <form method="POST" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900">Edit Health Profile</h3>
                        <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>

                    <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                        
                        <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Vital Statistics</h4>
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Blood Type</label>
                                <select name="blood_type" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none">
                                    <option value="">--</option>
                                    <?php foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bt): ?>
                                        <option value="<?= $bt ?>" <?= val('blood_type', $profile) == $bt ? 'selected' : '' ?>><?= $bt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Height (cm)</label>
                                <input type="number" name="height" value="<?= val('height', $profile) ?>" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                                <input type="number" name="weight" value="<?= val('weight', $profile) ?>" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none">
                            </div>
                        </div>

                        <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Emergency Contact</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                <input type="text" name="ec_name" value="<?= val('emergency_contact_name', $profile) ?>" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" name="ec_phone" value="<?= val('emergency_contact_phone', $profile) ?>" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Allergies</label>
                                <textarea name="allergies" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"><?= val('allergies', $profile) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chronic Conditions</label>
                                <textarea name="chronic_conditions" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"><?= val('chronic_conditions', $profile) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Medications</label>
                                <textarea name="current_medications" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"><?= val('current_medications', $profile) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Past Surgeries</label>
                                <textarea name="past_surgeries" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"><?= val('past_surgeries', $profile) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Family History</label>
                                <textarea name="family_history" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"><?= val('family_history', $profile) ?></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">Cancel</button>
                        <button type="submit" name="update_history" class="px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 shadow-sm">Save Changes</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        const modal = document.getElementById('editModal');

        function openEditModal() {
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>