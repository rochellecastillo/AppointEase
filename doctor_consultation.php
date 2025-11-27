<?php
// doctor_consultation.php - Consultation Interface
ob_start();
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$user_id = session_get_user_id();

$appt_id = $_GET['id'] ?? '';
if (!$appt_id) {
    header('Location: doctor_appointments.php');
    exit;
}

// --- 1. FETCH APPOINTMENT & PATIENT DATA ---
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               i.first_name, i.last_name, i.bdate, i.gender, i.contact, i.address, i.image,
               hp.blood_type, hp.height, hp.weight, hp.allergies, hp.chronic_conditions, hp.current_medications
        FROM tblappointment a
        JOIN tblinfo i ON a.user_id = i.user_id
        LEFT JOIN tbl_health_profile hp ON a.user_id = hp.user_id
        WHERE a.id = ? AND a.doctor = ?
    ");
    $stmt->execute([$appt_id, $user_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        die("Appointment not found or unauthorized.");
    }

    // Calculate Age
    $age = date_diff(date_create($appt['bdate']), date_create('today'))->y;

    // Get Past Medical Records for Context
    $histStmt = $pdo->prepare("
        SELECT mr.*, a.booking_date 
        FROM tbl_medical_records mr
        JOIN tblappointment a ON mr.appointment_id = a.id
        WHERE a.user_id = ? AND a.id != ?
        ORDER BY a.booking_date DESC LIMIT 5
    ");
    $histStmt->execute([$appt['user_id'], $appt_id]);
    $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// --- 2. HANDLE FORM SUBMISSION (SAVE RECORD) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_consultation'])) {
    $diagnosis = $_POST['diagnosis'];
    $prescription = $_POST['prescription'];
    $notes = $_POST['notes'];
    
    try {
        $pdo->beginTransaction();

        // A. Save Medical Record
        $stmt = $pdo->prepare("INSERT INTO tbl_medical_records (appointment_id, diagnosis, prescription, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$appt_id, $diagnosis, $prescription, $notes]);

        // B. Mark Appointment as Completed
        // UPDATED: Set status to 3 (Completed)
        $updateStmt = $pdo->prepare("UPDATE tblappointment SET status = 3 WHERE id = ?");
        $updateStmt->execute([$appt_id]);

        $pdo->commit();
        $message = "Consultation completed successfully!";
        
        // Redirect to patient records page
        header("Location: doctor_records.php?patient_id=" . $appt['user_id'] . "&saved=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error saving record: " . $e->getMessage();
    }
}

// Helper for Avatar
$avatar = !empty($appt['image']) ? 'uploads/' . e($appt['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($appt['first_name'] . '+' . $appt['last_name']) . '&background=random';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        /* Custom scrollbar for history panel */
        .history-scroll::-webkit-scrollbar { width: 4px; }
        .history-scroll::-webkit-scrollbar-track { background: transparent; }
        .history-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
    </style>
</head>
<body class="text-slate-800 h-screen flex flex-col">

    <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0 z-20">
        <div class="flex items-center gap-4">
            <a href="doctor_appointments.php" class="p-2 hover:bg-slate-100 rounded-lg transition text-slate-500">
                <i data-lucide="arrow-left" width="20"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    Medical Consultation
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs uppercase font-bold tracking-wider">In Progress</span>
                </h1>
                <p class="text-xs text-slate-500">Appt ID: #<?= e($appt_id) ?> &bull; <?= date('M d, Y', strtotime($appt['booking_date'])) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-slate-700">Dr. <?= e($doc['last_name'] ?? 'Doctor') ?></p>
                <p class="text-xs text-slate-500">Attending Physician</p>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <aside class="w-1/3 max-w-md bg-white border-r border-slate-200 overflow-y-auto flex flex-col history-scroll hidden lg:flex">
            <div class="p-6 border-b border-slate-100 text-center bg-slate-50/50">
                <img src="<?= $avatar ?>" alt="Patient" class="w-24 h-24 rounded-full mx-auto mb-3 border-4 border-white shadow-sm object-cover">
                <h2 class="text-xl font-bold text-slate-800"><?= e($appt['first_name'] . ' ' . $appt['last_name']) ?></h2>
                <p class="text-sm text-slate-500 mb-4"><?= $age ?> Years &bull; <?= e($appt['gender']) ?></p>
                
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <p class="text-[10px] uppercase font-bold text-slate-400">Blood</p>
                        <p class="font-bold text-slate-700"><?= e($appt['blood_type'] ?: '--') ?></p>
                    </div>
                    <div class="p-2 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <p class="text-[10px] uppercase font-bold text-slate-400">Height</p>
                        <p class="font-bold text-slate-700"><?= e($appt['height'] ? $appt['height'].'cm' : '--') ?></p>
                    </div>
                    <div class="p-2 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <p class="text-[10px] uppercase font-bold text-slate-400">Weight</p>
                        <p class="font-bold text-slate-700"><?= e($appt['weight'] ? $appt['weight'].'kg' : '--') ?></p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Medical Alerts</h3>
                
                <div class="space-y-3">
                    <div class="p-3 bg-red-50 rounded-lg border border-red-100">
                        <p class="text-xs font-bold text-red-700 mb-1 flex items-center gap-1"><i data-lucide="alert-triangle" width="12"></i> Allergies</p>
                        <p class="text-sm text-slate-700"><?= e($appt['allergies'] ?: 'None reported') ?></p>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg border border-orange-100">
                        <p class="text-xs font-bold text-orange-700 mb-1 flex items-center gap-1"><i data-lucide="activity" width="12"></i> Chronic Conditions</p>
                        <p class="text-sm text-slate-700"><?= e($appt['chronic_conditions'] ?: 'None reported') ?></p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                        <p class="text-xs font-bold text-blue-700 mb-1 flex items-center gap-1"><i data-lucide="pill" width="12"></i> Current Meds</p>
                        <p class="text-sm text-slate-700"><?= e($appt['current_medications'] ?: 'None listed') ?></p>
                    </div>
                </div>
            </div>

            <div class="p-6 flex-1">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Visit History</h3>
                <?php if(empty($history)): ?>
                    <p class="text-sm text-slate-400 italic text-center py-4">No previous records found.</p>
                <?php else: ?>
                    <div class="space-y-4 relative before:absolute before:top-2 before:left-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        <?php foreach($history as $h): ?>
                        <div class="pl-6 relative">
                            <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full bg-white border-2 border-slate-300"></div>
                            <p class="text-xs font-bold text-slate-500 mb-1"><?= date('M d, Y', strtotime($h['booking_date'])) ?></p>
                            <div class="p-3 bg-white border border-slate-200 rounded-lg shadow-sm">
                                <p class="text-sm font-bold text-slate-800 mb-1"><?= e($h['diagnosis']) ?></p>
                                <p class="text-xs text-slate-500 line-clamp-2"><?= e($h['notes']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <main class="flex-1 bg-slate-50 overflow-y-auto p-6 md:p-8">
            <div class="max-w-3xl mx-auto">
                
                <div class="lg:hidden bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex items-center gap-4">
                    <img src="<?= $avatar ?>" class="w-12 h-12 rounded-full object-cover">
                    <div>
                        <h2 class="font-bold text-slate-800"><?= e($appt['first_name'] . ' ' . $appt['last_name']) ?></h2>
                        <p class="text-sm text-slate-500">View patient profile & history</p>
                    </div>
                    <button class="ml-auto p-2 text-blue-600 bg-blue-50 rounded-lg" onclick="alert('In a real app, this opens a modal with the sidebar content.')"><i data-lucide="info" width="20"></i></button>
                </div>

                <form method="POST" class="space-y-6">
                    
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <i data-lucide="stethoscope" class="text-purple-600 w-4 h-4"></i> Diagnosis / Chief Complaint
                        </label>
                        <input type="text" name="diagnosis" required placeholder="e.g. Acute Viral Pharyngitis" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 focus:outline-none transition font-medium text-lg">
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="text-blue-600 w-4 h-4"></i> Clinical Notes & Observations
                        </label>
                        <textarea name="notes" rows="6" placeholder="Enter symptoms, vitals observed, examination details..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition"></textarea>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <i data-lucide="pill" class="text-green-600 w-4 h-4"></i> Prescription (Rx)
                        </label>
                        <div class="relative">
                            <textarea name="prescription" rows="4" placeholder="- Amoxicillin 500mg, 1 tab every 8 hours for 7 days&#10;- Paracetamol 500mg, 1 tab every 4 hours as needed" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 focus:outline-none transition font-mono text-sm"></textarea>
                            <div class="absolute top-3 right-3 text-xs text-slate-400 bg-white px-2 py-1 rounded border border-slate-200">Markdown Supported</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <button type="submit" name="save_consultation" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-green-200 transition flex items-center justify-center gap-2 transform active:scale-[0.98]">
                            <i data-lucide="save" width="20"></i> Save Record & Complete
                        </button>
                    </div>

                </form>

            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>