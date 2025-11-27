<?php
// appointment_details.php - Detailed Appointment View
// ----------------------------------------------------------------
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// 1. Enforce Authentication
session_require_auth(['user']);
$user_id = session_get_user_id();

// 2. Validate Input
$apt_id = $_GET['id'] ?? 0;
if (empty($apt_id)) {
    // REDIRECT TO DASHBOARD
    header('Location: client_home.php');
    exit;
}

// 3. Handle Cancellation Action (POST)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    // Verify ownership before cancelling
    $check = $pdo->prepare("SELECT id FROM tblappointment WHERE id = ? AND user_id = ?");
    $check->execute([$_POST['cancel_id'], $user_id]);
    
    if ($check->fetch()) {
        $update = $pdo->prepare("UPDATE tblappointment SET status = 0 WHERE id = ?");
        if ($update->execute([$_POST['cancel_id']])) {
            header("Location: appointment_details.php?id=$apt_id&msg=cancelled");
            exit;
        }
    } else {
        $msg = "error_access";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled') {
    $msg = "success_cancelled";
}

// 4. Fetch Appointment Data
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               d.first_name AS doc_first, d.last_name AS doc_last, d.specialization, 
               d.contact AS doc_contact, d.address AS doc_address, d.image AS doc_image, d.email AS doc_email,
               p.first_name AS pat_first, p.last_name AS pat_last, p.contact AS pat_contact
        FROM tblappointment a
        LEFT JOIN tblinfo d ON d.user_id = a.doctor
        LEFT JOIN tblinfo p ON p.user_id = a.user_id
        WHERE a.id = ? AND a.user_id = ?
    ");
    $stmt->execute([$apt_id, $user_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        // REDIRECT TO DASHBOARD
        header('Location: client_home.php');
        exit;
    }
    
    // Get doctor's schedule for that day
    $day_of_week = date('w', strtotime($appointment['booking_date']));
    $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND day = ?");
    $stmt->execute([$appointment['doctor'], $day_of_week]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// 5. Process Data for View
$doctor_name = 'Dr. ' . e($appointment['doc_first']) . ' ' . e($appointment['doc_last']);
$patient_name = e($appointment['pat_first']) . ' ' . e($appointment['pat_last']);

// Image Handling
$image_path = !empty($appointment['doc_image']) ? 'uploads/' . e($appointment['doc_image']) : null;

// Days Mapping
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Status Logic (0=Cancelled, 1=Confirmed, 2=Pending)
$status_code = (int)$appointment['status'];
switch ($status_code) {
    case 1: 
        $status_label = 'Confirmed'; 
        $status_classes = 'bg-emerald-100 text-emerald-800 border-emerald-200'; 
        $icon = 'check-circle';
        break;
    case 0: 
        $status_label = 'Cancelled'; 
        $status_classes = 'bg-red-100 text-red-800 border-red-200'; 
        $icon = 'x-circle';
        break;
    default: 
        $status_label = 'Pending'; 
        $status_classes = 'bg-amber-100 text-amber-800 border-amber-200'; 
        $icon = 'clock';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - AppointEase</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
            .shadow-sm, .shadow-lg { box-shadow: none !important; }
            .border { border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <div class="no-print">
            <?php include 'includes/client_sidebar.php'; ?>
        </div>

        <main class="flex-1 overflow-auto relative w-full">
            
            <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20 no-print">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="p-6 max-w-5xl mx-auto">
                
                <div class="mb-6 flex items-center justify-between no-print">
                    <a href="client_home.php" class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Dashboard
                    </a>
                    <div class="text-sm text-gray-400 font-mono">ID: #<?= str_pad($appointment['id'], 5, '0', STR_PAD_LEFT) ?></div>
                </div>

                <?php if ($msg === 'success_cancelled'): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-xl flex items-center gap-3 no-print">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <div>
                            <span class="font-bold">Appointment Cancelled.</span> The doctor has been notified.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/30">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Appointment Details</h1>
                            <p class="text-gray-500 text-sm mt-1">View and manage your scheduled visit.</p>
                        </div>
                        
                        <div class="flex items-center gap-2 px-4 py-2 rounded-full border text-sm font-bold <?= $status_classes ?>">
                            <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                            <?= strtoupper($status_label) ?>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <div class="lg:col-span-2 space-y-8">
                            
                            <div>
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Medical Professional</h3>
                                <div class="flex items-start gap-5">
                                    <?php if($image_path): ?>
                                        <img src="<?= $image_path ?>" class="w-20 h-20 rounded-2xl object-cover border border-gray-100 shadow-sm">
                                    <?php else: ?>
                                        <div class="w-20 h-20 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                                            <i data-lucide="stethoscope" class="w-8 h-8"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900"><?= $doctor_name ?></h2>
                                        <?php if(!empty($appointment['specialization'])): ?>
                                            <span class="inline-block mt-1 px-2.5 py-0.5 bg-blue-50 text-blue-700 rounded-md text-xs font-semibold">
                                                <?= e($appointment['specialization']) ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3 space-y-1">
                                            <?php if(!empty($appointment['doc_contact'])): ?>
                                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i> <?= e($appointment['doc_contact']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if(!empty($appointment['doc_email'])): ?>
                                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i> <?= e($appointment['doc_email']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($schedule): ?>
                                <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                                            <i data-lucide="clock" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-blue-900 text-sm">Office Hours (<?= $days[$day_of_week] ?>)</h4>
                                            <p class="text-blue-700 text-sm mt-1">
                                                The doctor is available from 
                                                <span class="font-semibold"><?= date('h:i A', strtotime($schedule['time'])) ?></span> to 
                                                <span class="font-semibold"><?= date('h:i A', strtotime($schedule['time2'])) ?></span>.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="space-y-6">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Visit Details</h3>
                            
                            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 space-y-5">
                                
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white rounded-lg text-gray-600 shadow-sm"><i data-lucide="calendar" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium">Date</p>
                                        <p class="font-bold text-gray-800"><?= date('F d, Y', strtotime($appointment['booking_date'])) ?></p>
                                        <p class="text-xs text-gray-400"><?= date('l', strtotime($appointment['booking_date'])) ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 border-t border-gray-200 pt-4">
                                    <div class="p-2 bg-white rounded-lg text-gray-600 shadow-sm"><i data-lucide="clock" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium">Time</p>
                                        <p class="font-bold text-gray-800">
                                            <?= !empty($appointment['booking_time']) ? date('h:i A', strtotime($appointment['booking_time'])) : 'TBD' ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if(!empty($appointment['doc_address'])): ?>
                                <div class="flex items-center gap-3 border-t border-gray-200 pt-4">
                                    <div class="p-2 bg-white rounded-lg text-gray-600 shadow-sm"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium">Clinic Location</p>
                                        <p class="font-bold text-gray-800 text-sm leading-snug"><?= e($appointment['doc_address']) ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3 justify-end no-print">
                        
                        <button onclick="window.print()" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="w-4 h-4"></i> Print Details
                        </button>

                        <?php if ($status_code !== 0 && strtotime($appointment['booking_date']) >= strtotime(date('Y-m-d'))): ?>
                            
                            <a href="reschedule.php?id=<?= $apt_id ?>" class="px-5 py-2.5 bg-white border border-purple-200 text-purple-700 font-semibold rounded-xl hover:bg-purple-50 transition flex items-center justify-center gap-2">
                                <i data-lucide="calendar-clock" class="w-4 h-4"></i> Reschedule
                            </a>
                            
                            <button onclick="confirmCancel()" class="px-5 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 shadow-lg shadow-red-200 transition flex items-center justify-center gap-2">
                                <i data-lucide="x" class="w-4 h-4"></i> Cancel Appointment
                            </button>
                            
                        <?php endif; ?>
                    </div>

                </div>

                <div class="mt-6 bg-amber-50 border border-amber-100 rounded-2xl p-6 flex items-start gap-4 no-print">
                    <div class="text-amber-600 mt-1"><i data-lucide="alert-circle" class="w-5 h-5"></i></div>
                    <div>
                        <h3 class="font-bold text-amber-900 mb-1">Important Reminders</h3>
                        <ul class="space-y-1 text-sm text-amber-800 list-disc list-inside">
                            <li>Please arrive 15 minutes before your scheduled time.</li>
                            <li>Bring a valid ID and any previous medical records.</li>
                            <li>Cancellations must be made at least 24 hours in advance.</li>
                        </ul>
                    </div>
                </div>

                <div class="hidden print-only mt-8 text-center text-gray-500 text-sm">
                    <p>This document serves as proof of appointment.</p>
                    <p>Generated on <?= date('F d, Y h:i A') ?> by AppointEase</p>
                </div>

            </div>
        </main>
    </div>

    <form id="cancelForm" method="POST" style="display:none;">
        <input type="hidden" name="cancel_id" value="<?= $apt_id ?>">
    </form>

    <script>
        lucide.createIcons();
        
        function confirmCancel() {
            if(confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
                document.getElementById('cancelForm').submit();
            }
        }

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>