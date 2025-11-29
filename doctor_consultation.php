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

    $age = date_diff(date_create($appt['bdate']), date_create('today'))->y;

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

// --- 2. HANDLE FORM SUBMISSION ---
// FIX: Check for hidden input 'save_consultation' OR button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_consultation']) || isset($_POST['btn_save']))) {
    $diagnosis = trim($_POST['diagnosis']);
    $prescription = trim($_POST['prescription']);
    $notes = trim($_POST['notes']);
    
    if(empty($diagnosis) || empty($notes)) {
        $error_msg = "Diagnosis and Clinical Notes are required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert Record
            $stmt = $pdo->prepare("INSERT INTO tbl_medical_records (appointment_id, diagnosis, prescription, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$appt_id, $diagnosis, $prescription, $notes]);

            // Update Appointment Status
            $updateStmt = $pdo->prepare("UPDATE tblappointment SET status = 3 WHERE id = ?");
            $updateStmt->execute([$appt_id]);

            $pdo->commit();
            
            // Set success flag for JS redirection
            $success_redirect = "doctor_records.php?patient_id=" . $appt['user_id'] . "&saved=1";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

$avatar = !empty($appt['image']) ? 'uploads/' . htmlspecialchars($appt['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($appt['first_name'] . '+' . $appt['last_name']) . '&background=random';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .history-scroll::-webkit-scrollbar { width: 4px; }
        .history-scroll::-webkit-scrollbar-track { background: transparent; }
        .history-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
        .error-border { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
    </style>
</head>
<body class="text-slate-800 h-screen flex flex-col">

    <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0 z-20">
        <div class="flex items-center gap-4">
            <a href="#" onclick="confirmExit()" class="p-2 hover:bg-slate-100 rounded-lg transition text-slate-500">
                <i data-lucide="arrow-left" width="20"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    Medical Consultation
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs uppercase font-bold tracking-wider">In Progress</span>
                </h1>
                <p class="text-xs text-slate-500">Appt ID: #<?= htmlspecialchars($appt_id) ?> &bull; <?= date('M d, Y', strtotime($appt['booking_date'])) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-slate-700">Dr. <?= htmlspecialchars($doc['last_name'] ?? 'Doctor') ?></p>
                <p class="text-xs text-slate-500">Attending Physician</p>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <aside class="w-1/3 max-w-md bg-white border-r border-slate-200 overflow-y-auto flex flex-col history-scroll hidden lg:flex">
            <div class="p-6 border-b border-slate-100 text-center bg-slate-50/50">
                <img src="<?= $avatar ?>" alt="Patient" class="w-24 h-24 rounded-full mx-auto mb-3 border-4 border-white shadow-sm object-cover">
                <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']) ?></h2>
                <p class="text-sm text-slate-500 mb-4"><?= $age ?> Years &bull; <?= htmlspecialchars($appt['gender']) ?></p>
                
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <p class="text-[10px] uppercase font-bold text-slate-400">Blood</p>
                        <p class="font-bold text-slate-700"><?= htmlspecialchars($appt['blood_type'] ?: '--') ?></p>
                    </div>
                    <div class="p-2 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <p class="text-[10px] uppercase font-bold text-slate-400">Height</p>
                        <p class="font-bold text-slate-700"><?= htmlspecialchars($appt['height'] ? $appt['height'].'cm' : '--') ?></p>
                    </div>
                    <div class="p-2 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <p class="text-[10px] uppercase font-bold text-slate-400">Weight</p>
                        <p class="font-bold text-slate-700"><?= htmlspecialchars($appt['weight'] ? $appt['weight'].'kg' : '--') ?></p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Medical Alerts</h3>
                <div class="space-y-3">
                    <div class="p-3 bg-red-50 rounded-lg border border-red-100">
                        <p class="text-xs font-bold text-red-700 mb-1 flex items-center gap-1"><i data-lucide="alert-triangle" width="12"></i> Allergies</p>
                        <p class="text-sm text-slate-700"><?= htmlspecialchars($appt['allergies'] ?: 'None reported') ?></p>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg border border-orange-100">
                        <p class="text-xs font-bold text-orange-700 mb-1 flex items-center gap-1"><i data-lucide="activity" width="12"></i> Chronic Conditions</p>
                        <p class="text-sm text-slate-700"><?= htmlspecialchars($appt['chronic_conditions'] ?: 'None reported') ?></p>
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
                                <p class="text-sm font-bold text-slate-800 mb-1"><?= htmlspecialchars($h['diagnosis']) ?></p>
                                <p class="text-xs text-slate-500 line-clamp-2"><?= htmlspecialchars($h['notes']) ?></p>
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
                        <h2 class="font-bold text-slate-800"><?= htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']) ?></h2>
                        <p class="text-sm text-slate-500">View patient profile & history</p>
                    </div>
                </div>

                <form method="POST" id="consultationForm" class="space-y-6" novalidate>
                    <input type="hidden" name="save_consultation" value="1">
                    
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <i data-lucide="stethoscope" class="text-purple-600 w-4 h-4"></i> Diagnosis / Chief Complaint <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="diagnosis" id="diagnosis" required placeholder="e.g. Acute Viral Pharyngitis" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 focus:outline-none transition font-medium text-lg validation-field">
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="text-blue-600 w-4 h-4"></i> Clinical Notes & Observations <span class="text-red-500">*</span>
                        </label>
                        <textarea name="notes" id="notes" rows="6" required placeholder="Enter symptoms, vitals observed, examination details..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition validation-field"></textarea>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <i data-lucide="pill" class="text-green-600 w-4 h-4"></i> Prescription (Rx)
                        </label>
                        <div class="relative">
                            <textarea name="prescription" rows="4" placeholder="- Amoxicillin 500mg, 1 tab every 8 hours for 7 days&#10;- Paracetamol 500mg, 1 tab every 4 hours as needed" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 focus:outline-none transition font-mono text-sm"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <button type="submit" name="btn_save" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-green-200 transition flex items-center justify-center gap-2 transform active:scale-[0.98]">
                            <i data-lucide="save" width="20"></i> Save Record & Complete
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" onclick="confirmExit()" class="inline-flex items-center text-slate-500 hover:text-slate-700 font-medium transition">
                            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Cancel & Go Back
                        </button>
                    </div>

                </form>

            </div>
        </main>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // 1. PHP ALERTS
        <?php if (isset($error_msg)): ?>
            Swal.fire({ icon: 'error', title: 'Error', text: '<?= addslashes($error_msg) ?>' });
        <?php endif; ?>

        <?php if (isset($success_redirect)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Completed!',
                text: 'Consultation record saved successfully.',
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                window.location.href = '<?= $success_redirect ?>';
            });
        <?php endif; ?>

        // 2. FORM VALIDATION & CONFIRMATION
        const form = document.getElementById('consultationForm');
        const inputs = document.querySelectorAll('.validation-field');
        let isDirty = false; 

        inputs.forEach(input => {
            input.addEventListener('input', () => {
                isDirty = true;
                input.classList.remove('error-border');
            });
        });

        form.addEventListener('submit', (e) => {
            let isValid = true;
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error-border');
                }
            });

            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please fill in the Diagnosis and Clinical Notes.',
                    confirmButtonColor: '#ef4444'
                });
            } else {
                // Disable button to prevent double clicks
                // The hidden input 'save_consultation' ensures PHP still processes it
                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div> Saving...';
            }
        });

        // 3. EXIT CONFIRMATION
        function confirmExit() {
            if (isDirty) {
                Swal.fire({
                    title: 'Discard Changes?',
                    text: "You have unsaved notes. Are you sure you want to leave?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, leave',
                    cancelButtonText: 'Stay'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'doctor_appointments.php';
                    }
                });
            } else {
                window.location.href = 'doctor_appointments.php';
            }
        }
    </script>
</body>
</html>