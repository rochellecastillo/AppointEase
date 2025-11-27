<?php
// admin_medical_records.php - Manage Patient Health Profiles
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Admin Access
session_require_auth(['admin']);

// --- 1. HANDLE FORM SUBMISSION (UPDATE PROFILE) ---
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'])) {
    $p_id = $_POST['patient_id'];
    
    try {
        // Check if profile exists for this user
        $check = $pdo->prepare("SELECT id FROM tbl_health_profile WHERE user_id = ?");
        $check->execute([$p_id]);

        if ($check->rowCount() > 0) {
            // Update existing profile
            $sql = "UPDATE tbl_health_profile SET 
                    blood_type=?, height=?, weight=?, allergies=?, 
                    chronic_conditions=?, current_medications=?, past_surgeries=?, 
                    family_history=?, emergency_contact_name=?, emergency_contact_phone=? 
                    WHERE user_id=?";
        } else {
            // Create new profile
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
            $p_id
        ]);
        
        header("Location: admin_medical_records.php?msg=success");
        exit;

    } catch (Exception $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// --- 2. FETCH PATIENTS & PROFILES ---
$search = $_GET['search'] ?? '';

// FIXED: Changed 'i.avatar' to 'i.image' in the SELECT list
$sql = "SELECT u.user_id, i.first_name, i.last_name, i.contact, i.email, i.image,
               hp.blood_type, hp.height, hp.weight, hp.allergies, hp.chronic_conditions, 
               hp.current_medications, hp.past_surgeries, hp.family_history, 
               hp.emergency_contact_name, hp.emergency_contact_phone, hp.updated_at
        FROM tbluser u
        JOIN tblinfo i ON u.user_id = i.user_id
        LEFT JOIN tbl_health_profile hp ON u.user_id = hp.user_id
        WHERE u.user_type = 'user'"; 

$params = [];

if ($search) {
    $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ? OR u.user_id LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY i.last_name ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Profiles - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 overflow-auto relative">
            <div class="bg-white border-b border-gray-200 p-4 flex justify-between items-center sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button id="mobileMenuBtn" class="md:hidden p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
                    <h1 class="font-bold text-xl text-gray-800">Patient Health Profiles</h1>
                </div>
            </div>

            <div class="p-6 max-w-7xl mx-auto">
                
                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                    <div class="mb-6 p-4 rounded-xl flex items-center gap-3 bg-green-50 text-green-700 border border-green-200">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span class="font-medium">Patient health profile updated successfully.</span>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6">
                    <form method="GET" class="flex gap-4">
                        <div class="flex-1 relative">
                            <i data-lucide="search" class="absolute left-3 top-3 text-gray-400 w-5 h-5"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Search by Patient Name or ID..." 
                                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
                        </div>
                        <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition">Search</button>
                        <?php if($search): ?>
                            <a href="admin_medical_records.php" class="px-4 py-2.5 text-gray-500 font-medium hover:text-gray-700 transition">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4">Patient Name</th>
                                    <th class="px-6 py-4">Contact</th>
                                    <th class="px-6 py-4">Blood Type</th>
                                    <th class="px-6 py-4">Last Updated</th>
                                    <th class="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php if(empty($patients)): ?>
                                    <tr><td colspan="5" class="p-8 text-center text-gray-500">No patients found.</td></tr>
                                <?php else: ?>
                                    <?php foreach($patients as $p): 
                                        $fullName = htmlspecialchars($p['first_name'] . ' ' . $p['last_name']);
                                        $hasProfile = !empty($p['updated_at']);
                                        // Prepare JSON for Modal
                                        $jsonData = htmlspecialchars(json_encode([
                                            'id' => $p['user_id'],
                                            'name' => $fullName,
                                            'blood_type' => $p['blood_type'] ?? '',
                                            'height' => $p['height'] ?? '',
                                            'weight' => $p['weight'] ?? '',
                                            'allergies' => $p['allergies'] ?? '',
                                            'chronic' => $p['chronic_conditions'] ?? '',
                                            'meds' => $p['current_medications'] ?? '',
                                            'surgery' => $p['past_surgeries'] ?? '',
                                            'family' => $p['family_history'] ?? '',
                                            'ec_name' => $p['emergency_contact_name'] ?? '',
                                            'ec_phone' => $p['emergency_contact_phone'] ?? ''
                                        ]), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900"><?= $fullName ?></div>
                                            <div class="text-xs text-gray-400">ID: <?= htmlspecialchars($p['user_id']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?= htmlspecialchars($p['contact'] ?: 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if(!empty($p['blood_type'])): ?>
                                                <span class="px-2 py-1 bg-red-50 text-red-700 rounded-lg text-xs font-bold border border-red-100">
                                                    <?= htmlspecialchars($p['blood_type']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            <?= $hasProfile ? date('M d, Y', strtotime($p['updated_at'])) : 'Not set' ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button onclick='openModal(<?= $jsonData ?>)' 
                                                    class="px-4 py-2 bg-purple-50 text-purple-700 text-xs font-bold rounded-lg hover:bg-purple-100 border border-purple-200 transition flex items-center gap-2 ml-auto">
                                                <i data-lucide="edit-2" class="w-3 h-3"></i> Manage Profile
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div id="profileModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-3xl">
                    
                    <form method="POST">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center sticky top-0 z-10">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Manage Health Profile</h3>
                                <p class="text-xs text-gray-500">Editing for: <span id="modalPatientName" class="font-bold text-purple-600"></span></p>
                            </div>
                            <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                                <i data-lucide="x" class="w-6 h-6"></i>
                            </button>
                        </div>

                        <div class="p-6 max-h-[75vh] overflow-y-auto">
                            <input type="hidden" name="patient_id" id="modalPatientId">

                            <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2">
                                <i data-lucide="activity" class="w-4 h-4"></i> Vital Statistics
                            </h4>
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Blood Type</label>
                                    <select name="blood_type" id="mBlood" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none">
                                        <option value="">--</option>
                                        <?php foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bt) echo "<option value='$bt'>$bt</option>"; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Height (cm)</label>
                                    <input type="number" name="height" id="mHeight" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Weight (kg)</label>
                                    <input type="number" name="weight" id="mWeight" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none">
                                </div>
                            </div>

                            <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2">
                                <i data-lucide="phone" class="w-4 h-4"></i> Emergency Contact
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Contact Name</label>
                                    <input type="text" name="ec_name" id="mEcName" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="ec_phone" id="mEcPhone" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none">
                                </div>
                            </div>

                            <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2">
                                <i data-lucide="clipboard-list" class="w-4 h-4"></i> Medical Details
                            </h4>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Allergies</label>
                                        <textarea name="allergies" id="mAllergies" rows="2" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Chronic Conditions</label>
                                        <textarea name="chronic_conditions" id="mChronic" rows="2" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Current Medications</label>
                                    <textarea name="current_medications" id="mMeds" rows="2" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none"></textarea>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Past Surgeries</label>
                                        <textarea name="past_surgeries" id="mSurgeries" rows="2" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Family History</label>
                                        <textarea name="family_history" id="mFamily" rows="2" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-purple-500 focus:outline-none"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100">
                            <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 shadow-lg shadow-purple-200 transition">Save Profile</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const modal = document.getElementById('profileModal');

        function openModal(data) {
            // Basic Info
            document.getElementById('modalPatientId').value = data.id;
            document.getElementById('modalPatientName').textContent = data.name;

            // Vitals
            document.getElementById('mBlood').value = data.blood_type || '';
            document.getElementById('mHeight').value = data.height || '';
            document.getElementById('mWeight').value = data.weight || '';

            // Emergency
            document.getElementById('mEcName').value = data.ec_name || '';
            document.getElementById('mEcPhone').value = data.ec_phone || '';

            // Medical
            document.getElementById('mAllergies').value = data.allergies || '';
            document.getElementById('mChronic').value = data.chronic || '';
            document.getElementById('mMeds').value = data.meds || '';
            document.getElementById('mSurgeries').value = data.surgery || '';
            document.getElementById('mFamily').value = data.family || '';

            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        // Mobile Sidebar Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        if(mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
            });
        }
    </script>
</body>
</html>