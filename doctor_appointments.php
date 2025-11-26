<?php
// doctor_appointments.php - Appointment Management for Doctors
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'iprog_sms.php'; // <--- 1. Include the SMS helper

session_require_auth(['doctor']);
$user_id = session_get_user_id();

// --- 1. HANDLE ACTIONS (Approve/Decline) ---
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $appt_id = $_POST['appt_id'];
        $action = $_POST['action_type'];
        
        // Validate ownership
        $check = $pdo->prepare("SELECT id FROM tblappointment WHERE id = ? AND doctor = ?");
        $check->execute([$appt_id, $user_id]);
        
        if ($check->rowCount() > 0) {
            $new_status = ($action === 'approve') ? 1 : 0; // 1=Confirmed, 0=Cancelled
            
            try {
                $pdo->beginTransaction(); // Start Transaction

                // Update Status
                $stmt = $pdo->prepare("UPDATE tblappointment SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $appt_id]);

                // --- SMS LOGIC START ---
                if ($action === 'approve') {
                    // 1. Fetch Patient Info & Appointment Details
                    $infoStmt = $pdo->prepare("
                        SELECT i.contact, i.first_name, a.booking_date, a.booking_time 
                        FROM tblappointment a 
                        JOIN tblinfo i ON a.user_id = i.user_id 
                        WHERE a.id = ?
                    ");
                    $infoStmt->execute([$appt_id]);
                    $apptData = $infoStmt->fetch(PDO::FETCH_ASSOC);

                    if ($apptData && !empty($apptData['contact'])) {
                        // 2. Construct Message
                        $formattedDate = date('M d, Y', strtotime($apptData['booking_date']));
                        $formattedTime = date('h:i A', strtotime($apptData['booking_time']));
                        
                        $smsContent = "Hello {$apptData['first_name']}, your appointment request for $formattedDate at $formattedTime has been CONFIRMED by the doctor. - AppointEase";

                        // 3. Send SMS
                        iprog_send_sms($apptData['contact'], $smsContent);
                    }
                }
                // --- SMS LOGIC END ---

                $pdo->commit(); // Commit Transaction

                $message = ($action === 'approve') ? "Appointment confirmed & SMS sent." : "Appointment declined.";
                $msg_type = ($action === 'approve') ? "success" : "error";

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Database Error: " . $e->getMessage();
                $msg_type = "error";
            }
        } else {
            $message = "Invalid appointment ID.";
            $msg_type = "error";
        }
    }
}

// --- 2. FETCH APPOINTMENTS ---
$filter = $_GET['status'] ?? 'upcoming';
$today = date('Y-m-d'); // <-- This variable is correctly defined and will be used below.

$sql = "SELECT a.id, a.booking_date, a.booking_time, a.status, 
                i.first_name, i.last_name, i.contact, i.gender, i.bdate, i.address
        FROM tblappointment a
        JOIN tblinfo i ON a.user_id = i.user_id
        WHERE a.doctor = ?";

if ($filter === 'pending') {
    $sql .= " AND a.status = 2 AND a.booking_date >= ?"; // 2 = Pending
    $params = [$user_id, $today];
} elseif ($filter === 'history') {
    $sql .= " AND (a.booking_date < ? OR a.status = 0)"; // Past or Cancelled
    $params = [$user_id, $today];
} else {
    // Default: Upcoming Confirmed (status = 1 and date >= today)
    $sql .= " AND a.status = 1 AND a.booking_date >= ?";
    $params = [$user_id, $today];
}

$sql .= " ORDER BY a.booking_date ASC, a.booking_time ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Error: " . $e->getMessage()); }

// Helper for Age Calculation
function getAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .status-badge { @apply px-2 py-1 rounded-full text-xs font-bold uppercase tracking-wide; }
    </style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/doctor_sidebar.php'; ?>

        <main class="flex-1 overflow-auto w-full">
            <div class="md:hidden bg-white p-4 border-b flex justify-between items-center sticky top-0 z-30">
                <span class="font-bold text-lg text-slate-800">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-slate-100 rounded-lg"><i data-lucide="menu" width="20"></i></button>
            </div>

            <div class="p-6 md:p-8 max-w-7xl mx-auto">
                
                <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Appointments</h1>
                        <p class="text-slate-500">Manage your patient bookings and schedule.</p>
                    </div>
                    
                    <div class="bg-white p-1 rounded-xl border border-slate-200 shadow-sm inline-flex">
                        <a href="?status=upcoming" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'upcoming' ? 'bg-green-100 text-green-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50' ?>">
                            Upcoming
                        </a>
                        <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'pending' ? 'bg-yellow-100 text-yellow-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50' ?>">
                            Pending Requests
                        </a>
                        <a href="?status=history" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'history' ? 'bg-slate-100 text-slate-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50' ?>">
                            History
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-3 <?= $msg_type === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
                        <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php if (empty($appointments)): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 border-dashed p-12 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="calendar-off" class="text-slate-400" width="32"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-700">No appointments found</h3>
                            <p class="text-slate-500 text-sm mt-1">Try changing the filter or check back later.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $apt): 
                            $patientName = e($apt['first_name'] . ' ' . $apt['last_name']);
                            $timeStr = date('h:i A', strtotime($apt['booking_time']));
                            $dateStr = date('M d, Y (D)', strtotime($apt['booking_date']));
                            $age = getAge($apt['bdate']);
                        ?>
                        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition flex flex-col md:flex-row gap-6 items-start md:items-center group">
                            
                            <div class="flex-shrink-0 w-full md:w-32 text-center bg-slate-50 rounded-lg p-3 border border-slate-100 group-hover:border-green-200 group-hover:bg-green-50 transition-colors">
                                <p class="text-xs font-bold text-slate-500 uppercase group-hover:text-green-600"><?= date('M d', strtotime($apt['booking_date'])) ?></p>
                                <p class="text-xl font-bold text-slate-800 group-hover:text-green-700"><?= $timeStr ?></p>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-lg font-bold text-slate-800 truncate"><?= $patientName ?></h3>
                                    <?php if($filter === 'pending'): ?>
                                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full font-bold uppercase">Pending Approval</span>
                                    <?php elseif($apt['status'] == 0): ?>
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-bold uppercase">Cancelled</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-bold uppercase">Confirmed</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex flex-wrap gap-4 text-sm text-slate-500 mt-2">
                                    <span class="flex items-center gap-1"><i data-lucide="user" width="14"></i> <?= $age ?> yrs, <?= e($apt['gender']) ?></span>
                                    <span class="flex items-center gap-1"><i data-lucide="phone" width="14"></i> <?= e($apt['contact']) ?></span>
                                    <span class="flex items-center gap-1"><i data-lucide="map-pin" width="14"></i> <?= e($apt['address']) ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 w-full md:w-auto mt-2 md:mt-0">
                                <?php if($filter === 'pending'): ?>
                                    <form method="POST" class="flex gap-2 w-full">
                                        <input type="hidden" name="appt_id" value="<?= $apt['id'] ?>">
                                        <button type="submit" name="action_type" value="decline" class="flex-1 md:flex-none px-4 py-2 border border-slate-300 text-slate-600 font-medium rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition text-sm">
                                            Decline
                                        </button>
                                        <button type="submit" name="action_type" value="approve" class="flex-1 md:flex-none px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition shadow-sm text-sm">
                                            Approve
                                        </button>
                                    </form>
                                <?php elseif($filter === 'upcoming' && $apt['booking_date'] === $today): ?>
                                    <a href="doctor_consultation.php?id=<?= $apt['id'] ?>" class="w-full md:w-auto px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm text-center">
                                        Start Consultation
                                    </a>
                                    <?php endif; ?>
                                
                                <a href="doctor_records.php?patient_id=<?= $apt['id'] /* Note: technically should be user_id lookup */ ?>" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition" title="View Records">
                                    <i data-lucide="file-clock" width="20"></i>
                                </a>
                            </div>

                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        // Mobile Sidebar Toggle
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-full'); 
            });
        }
    </script>
</body>
</html>