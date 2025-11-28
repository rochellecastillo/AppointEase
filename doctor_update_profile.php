<?php
// doctor_update_profile.php - Update Patient Health Profile
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    header('Location: patients.php');
    exit;
}

$msg = '';
$msg_type = '';

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect Inputs
        $blood_type = $_POST['blood_type'];
        $height = !empty($_POST['height']) ? $_POST['height'] : null;
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $allergies = trim($_POST['allergies']);
        $conditions = trim($_POST['chronic_conditions']);
        $meds = trim($_POST['current_medications']);
        $surgeries = trim($_POST['past_surgeries']);
        $history = trim($_POST['family_history']);
        $emer_name = trim($_POST['emergency_contact_name']);
        $emer_phone = trim($_POST['emergency_contact_phone']);

        // Check if profile exists
        $checkStmt = $pdo->prepare("SELECT id FROM tbl_health_profile WHERE user_id = ?");
        $checkStmt->execute([$patient_id]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            // UPDATE existing record
            $sql = "UPDATE tbl_health_profile SET 
                    blood_type = ?, height = ?, weight = ?, allergies = ?, 
                    chronic_conditions = ?, current_medications = ?, past_surgeries = ?, 
                    family_history = ?, emergency_contact_name = ?, emergency_contact_phone = ?
                    WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$blood_type, $height, $weight, $allergies, $conditions, $meds, $surgeries, $history, $emer_name, $emer_phone, $patient_id]);
        } else {
            // INSERT new record
            $sql = "INSERT INTO tbl_health_profile 
                    (user_id, blood_type, height, weight, allergies, chronic_conditions, current_medications, past_surgeries, family_history, emergency_contact_name, emergency_contact_phone)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patient_id, $blood_type, $height, $weight, $allergies, $conditions, $meds, $surgeries, $history, $emer_name, $emer_phone]);
        }

        log_security_event('update_health_profile', ['patient_id' => $patient_id, 'doctor_id' => session_get_user_id()]);
        
        $msg = "Health profile updated successfully!";
        $msg_type = "success";

    } catch (Exception $e) {
        $msg = "Error updating profile: " . $e->getMessage();
        $msg_type = "error";
    }
}

// 2. Fetch Current Data to Pre-fill Form
try {
    // Get Basic Info
    $stmtUser = $pdo->prepare("SELECT first_name, last_name, image FROM tblinfo WHERE user_id = ?");
    $stmtUser->execute([$patient_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Get Health Profile
    $stmtHealth = $pdo->prepare("SELECT * FROM tbl_health_profile WHERE user_id = ?");
    $stmtHealth->execute([$patient_id]);
    $hp = $stmtHealth->fetch(PDO::FETCH_ASSOC) ?: []; // Empty array if no record

} catch (Exception $e) {
    die("Database Error");
}

$avatar = !empty($user['image']) ? 'uploads/' . e($user['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/doctor_sidebar.php'; ?>

        <main class="flex-1 overflow-auto w-full p-6 md:p-8">
            <div class="max-w-4xl mx-auto">
                
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <a href="doctor_view.php?patient_id=<?= $patient_id ?>" class="inline-flex items-center text-sm text-slate-500 hover:text-blue-600 mb-2 transition">
                            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Cancel & Return
                        </a>
                        <h1 class="text-2xl font-bold text-slate-900">Edit Health Profile</h1>
                        <p class="text-slate-500">Updating record for: <span class="font-semibold text-blue-600"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span></p>
                    </div>
                    <img src="<?= $avatar ?>" class="w-12 h-12 rounded-full border shadow-sm">
                </div>

                <?php if ($msg): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-2 <?= $msg_type == 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                        <i data-lucide="<?= $msg_type == 'success' ? 'check-circle' : 'alert-circle' ?>" width="20"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="activity" class="text-blue-500" width="18"></i> Vital Statistics
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Blood Type</label>
                                <select name="blood_type" class="w-full p-2 border border-slate-300 rounded-lg focus:border-blue-500 outline-none">
                                    <option value="">Unknown</option>
                                    <?php 
                                    $types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                    foreach($types as $t) {
                                        $sel = (isset($hp['blood_type']) && $hp['blood_type'] == $t) ? 'selected' : '';
                                        echo "<option value='$t' $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Height (cm)</label>
                                <input type="number" step="0.01" name="height" value="<?= e($hp['height'] ?? '') ?>" class="w-full p-2 border border-slate-300 rounded-lg focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight" value="<?= e($hp['weight'] ?? '') ?>" class="w-full p-2 border border-slate-300 rounded-lg focus:border-blue-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="text-purple-500" width="18"></i> Medical History
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Allergies</label>
                                <textarea name="allergies" rows="3" class="w-full p-2 border border-slate-300 rounded-lg focus:border-purple-500 outline-none" placeholder="List any known allergies..."><?= e($hp['allergies'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Chronic Conditions</label>
                                <textarea name="chronic_conditions" rows="3" class="w-full p-2 border border-slate-300 rounded-lg focus:border-purple-500 outline-none" placeholder="Diabetes, Hypertension, etc..."><?= e($hp['chronic_conditions'] ?? '') ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Current Medications</label>
                                <textarea name="current_medications" rows="2" class="w-full p-2 border border-slate-300 rounded-lg focus:border-purple-500 outline-none"><?= e($hp['current_medications'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Past Surgeries</label>
                                <textarea name="past_surgeries" rows="3" class="w-full p-2 border border-slate-300 rounded-lg focus:border-purple-500 outline-none"><?= e($hp['past_surgeries'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Family History</label>
                                <textarea name="family_history" rows="3" class="w-full p-2 border border-slate-300 rounded-lg focus:border-purple-500 outline-none"><?= e($hp['family_history'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="phone" class="text-red-500" width="18"></i> Emergency Contact
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Contact Name</label>
                                <input type="text" name="emergency_contact_name" value="<?= e($hp['emergency_contact_name'] ?? '') ?>" class="w-full p-2 border border-slate-300 rounded-lg focus:border-red-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Contact Phone</label>
                                <input type="text" name="emergency_contact_phone" value="<?= e($hp['emergency_contact_phone'] ?? '') ?>" class="w-full p-2 border border-slate-300 rounded-lg focus:border-red-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <a href="doctor_view.php?patient_id=<?= $patient_id ?>" class="px-6 py-2 border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">Cancel</a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Save Changes</button>
                    </div>

                </form>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>