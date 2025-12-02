<?php
// calendar_appointment_details.php
// Universal details view for doctor + admin + patient

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

// Helper for safe output
if (!function_exists('e')) {
    function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
}

// Allow ALL authenticated roles
session_require_auth(['user', 'doctor', 'admin']);
$user_id   = session_get_user_id();
$user_type = strtolower($_SESSION['user_type']);

// Validate input
$apt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($apt_id <= 0) {
    die("Invalid appointment ID.");
}

/* -----------------------------------------
   Construct Role-Based SQL Filter
------------------------------------------*/
$sql = "
    SELECT a.*,
           d.first_name AS doc_first, d.last_name AS doc_last, d.specialization,
           d.contact AS doc_contact, d.address AS doc_address, d.email AS doc_email, d.image AS doc_image,
           p.first_name AS pat_first, p.last_name AS pat_last, p.contact AS pat_contact, p.address AS pat_address
    FROM tblappointment a
    LEFT JOIN tblinfo d ON d.user_id = a.doctor
    LEFT JOIN tblinfo p ON p.user_id = a.user_id
    WHERE a.id = ?
";

$params = [$apt_id];

// Security Filters
if ($user_type === 'user') {
    $sql .= " AND a.user_id = ?";
    $params[] = $user_id;
} elseif ($user_type === 'doctor') {
    $sql .= " AND a.doctor = ?";
    $params[] = $user_id;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    // Nice error UI
    die('<div style="padding:50px; text-align:center; font-family:sans-serif; color:#666;">
            <h2>Access Denied</h2>
            <p>You are not authorized to view this appointment or it does not exist.</p>
            <a href="javascript:history.back()">Go Back</a>
         </div>');
}

/* -----------------------------------------
   Data Formatting
------------------------------------------*/
$status = (int)$appointment['status'];
// Using explicit classes for Tailwind JIT compatibility
switch ($status) {
    case 1: 
        $status_label = "Confirmed"; 
        $status_badge = "bg-green-100 text-green-700 border-green-200"; 
        $icon = "check-circle";
        break;
    case 2: 
        $status_label = "Pending";   
        $status_badge = "bg-yellow-50 text-yellow-700 border-yellow-200"; 
        $icon = "clock";
        break;
    case 3: 
        $status_label = "Completed"; 
        $status_badge = "bg-blue-50 text-blue-700 border-blue-200"; 
        $icon = "check-circle-2";
        break;
    default: 
        $status_label = "Cancelled"; 
        $status_badge = "bg-red-50 text-red-700 border-red-200"; 
        $icon = "x-circle";
        break;
}

$doctor_name  = "Dr. " . $appointment['doc_first'] . " " . $appointment['doc_last'];
$patient_name = $appointment['pat_first'] . " " . $appointment['pat_last'];

// Date Formatting
$date_pretty = date('l, F j, Y', strtotime($appointment['booking_date']));
$time_pretty = date('g:i A', strtotime($appointment['booking_time']));

// Avatar Logic
$doc_img = !empty($appointment['doc_image']) ? 'uploads/' . $appointment['doc_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($doctor_name) . '&background=random';

// Determine Back Link based on role
$back_link = 'calendar.php'; // Default
if (isset($_GET['ref']) && $_GET['ref'] === 'calendar') $back_link = 'calendar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="max-w-3xl w-full bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">

        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white flex justify-between items-start">
            <div>
                <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Appointment ID: #<?= e($apt_id) ?></p>
                <h1 class="text-3xl font-bold flex items-center gap-2">
                    <?= $time_pretty ?>
                </h1>
                <p class="text-blue-100 mt-1 flex items-center gap-2">
                    <i data-lucide="calendar" width="16"></i> <?= $date_pretty ?>
                </p>
            </div>
            
            <div class="flex flex-col items-end gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide flex items-center gap-1 bg-white/20 backdrop-blur-sm text-white border border-white/30">
                    <i data-lucide="<?= $icon ?>" width="14"></i> <?= $status_label ?>
                </span>
            </div>
        </div>

        <div class="p-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                        <i data-lucide="stethoscope" class="text-blue-600 w-5 h-5"></i>
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide">Medical Professional</h2>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <img src="<?= $doc_img ?>" alt="Doctor" class="w-16 h-16 rounded-full object-cover border-2 border-blue-100 shadow-sm">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900"><?= e($doctor_name) ?></h3>
                            <p class="text-sm text-blue-600 font-medium"><?= e($appointment['specialization'] ?: 'General Practitioner') ?></p>
                        </div>
                    </div>

                    <div class="space-y-3 mt-4">
                        <div class="flex items-start gap-3 text-sm text-gray-600">
                            <i data-lucide="phone" class="w-4 h-4 mt-0.5 text-gray-400"></i>
                            <span><?= e($appointment['doc_contact']) ?></span>
                        </div>
                        <div class="flex items-start gap-3 text-sm text-gray-600">
                            <i data-lucide="mail" class="w-4 h-4 mt-0.5 text-gray-400"></i>
                            <span class="break-all"><?= e($appointment['doc_email']) ?></span>
                        </div>
                        <div class="flex items-start gap-3 text-sm text-gray-600">
                            <i data-lucide="map-pin" class="w-4 h-4 mt-0.5 text-gray-400"></i>
                            <span><?= e($appointment['doc_address']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                        <i data-lucide="user" class="text-purple-600 w-5 h-5"></i>
                        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide">Patient Details</h2>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-bold text-gray-900"><?= e($patient_name) ?></h3>
                        <p class="text-sm text-gray-500">Patient</p>
                    </div>

                    <div class="space-y-3 mt-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="flex items-start gap-3 text-sm text-gray-700">
                            <i data-lucide="phone" class="w-4 h-4 mt-0.5 text-gray-400"></i>
                            <span><?= e($appointment['pat_contact']) ?></span>
                        </div>
                        <div class="flex items-start gap-3 text-sm text-gray-700">
                            <i data-lucide="home" class="w-4 h-4 mt-0.5 text-gray-400"></i>
                            <span><?= e($appointment['pat_address']) ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-10 pt-6 border-t border-gray-100 flex justify-end gap-3">
                <a href="<?= $back_link ?>" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
                    <i data-lucide="arrow-left" width="16"></i> Back
                </a>
                <button onclick="window.print()" class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition shadow-md flex items-center gap-2">
                    <i data-lucide="printer" width="16"></i> Print Details
                </button>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>

</body>
</html>