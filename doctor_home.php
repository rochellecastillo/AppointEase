<?php
// doctor_home.php - Modernized Doctor Dashboard
// ---------------------------------------------------------
require_once 'session_handler.php'; 
require_once 'security_helper.php'; 
require_once 'db.php'; 

session_require_auth(['doctor']);


$my_user_id = session_get_user_id();

// Fetch Data
$stmt = $pdo->prepare("SELECT i.*, u.user_name FROM tblinfo i JOIN tbluser u ON u.user_id = i.user_id WHERE i.user_id = ? LIMIT 1");
$stmt->execute([$my_user_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

$today = date('Y-m-d');

// Stats Counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0");
$stmt->execute([$my_user_id, $today]);
$count_today = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tblappointment WHERE doctor = ? AND booking_date > ? AND status != 0");
$stmt->execute([$my_user_id, $today]);
$count_upcoming = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM tblappointment WHERE doctor = ?");
$stmt->execute([$my_user_id]);
$count_patients = $stmt->fetchColumn();

// Today's Schedule
$stmt = $pdo->prepare("
    SELECT a.id, a.booking_date, a.booking_time, a.status, 
           i.first_name, i.last_name, i.contact, i.gender
    FROM tblappointment a
    LEFT JOIN tblinfo i ON i.user_id = a.user_id
    WHERE a.doctor = ? AND a.booking_date = ? AND a.status != 0
    ORDER BY a.booking_time ASC
");
$stmt->execute([$my_user_id, $today]);
$today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming List
$stmt = $pdo->prepare("
    SELECT a.id, a.booking_date, a.booking_time, a.status, 
           i.first_name, i.last_name
    FROM tblappointment a
    LEFT JOIN tblinfo i ON i.user_id = a.user_id
    WHERE a.doctor = ? AND a.booking_date > ? AND a.status != 0
    ORDER BY a.booking_date ASC, a.booking_time ASC
    LIMIT 5
");
$stmt->execute([$my_user_id, $today]);
$upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Doctor Dashboard - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/doctor_sidebar.php'; ?>

        <main class="flex-1 overflow-auto relative w-full">
            
            <div class="md:hidden bg-white p-4 border-b flex justify-between items-center sticky top-0 z-30">
                <span class="font-bold text-lg text-slate-800">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                    <i data-lucide="menu" width="20"></i>
                </button>
            </div>

            <div class="p-6 md:p-8 max-w-7xl mx-auto space-y-8">
                
                <header class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-green-50 rounded-full -mr-16 -mt-16 opacity-50"></div>
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Welcome back, Dr. <?= e($doc['last_name']) ?>!</h1>
                            <p class="text-slate-500">You have <strong class="text-green-600"><?= $count_today ?> appointments</strong> scheduled for today.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="doctor_schedule_manage.php" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 font-medium rounded-xl hover:bg-slate-50 transition shadow-sm flex items-center gap-2">
                                <i data-lucide="calendar-clock" width="18"></i> Manage Schedule
                            </a>
                            <a href="doctor_records.php" class="px-4 py-2 bg-green-600 text-white font-medium rounded-xl hover:bg-green-700 transition shadow-lg shadow-green-200 flex items-center gap-2">
                                <i data-lucide="plus" width="18"></i> Create Record
                            </a>
                        </div>
                    </div>
                </header>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4 transition hover:shadow-md">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="calendar" width="24"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Today's Visits</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $count_today ?></p>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4 transition hover:shadow-md">
                        <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="calendar-days" width="24"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Upcoming</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $count_upcoming ?></p>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4 transition hover:shadow-md">
                        <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="users" width="24"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Total Patients</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $count_patients ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                                <i data-lucide="list-todo" class="text-green-600"></i> Today's Schedule
                            </h3>
                            <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?= date('F d, Y') ?></span>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden min-h-[300px]">
                            <?php if(empty($today_appointments)): ?>
                                <div class="p-12 text-center h-full flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="coffee" class="text-slate-400" width="32"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-700">No appointments today</h4>
                                    <p class="text-slate-500 text-sm">Enjoy your free time, Doctor!</p>
                                </div>
                            <?php else: ?>
                                <div class="divide-y divide-slate-100">
                                    <?php foreach($today_appointments as $apt): 
                                        // UPDATED: Added status 3 logic here
                                        $statusColor = match((int)$apt['status']) {
                                            1 => 'bg-green-100 text-green-700',
                                            2 => 'bg-yellow-100 text-yellow-700',
                                            3 => 'bg-blue-100 text-blue-700', // Completed
                                            0 => 'bg-red-100 text-red-700',
                                            default => 'bg-slate-100 text-slate-600'
                                        };
                                        $statusText = match((int)$apt['status']) {
                                            1 => 'Confirmed', 
                                            2 => 'Pending', 
                                            3 => 'Completed', // Completed
                                            0 => 'Cancelled', 
                                            default => 'Unknown'
                                        };
                                    ?>
                                    <div class="p-5 hover:bg-slate-50 transition flex items-start gap-4">
                                        <div class="w-20 flex-shrink-0 text-center pt-1">
                                            <p class="font-bold text-slate-800 text-lg"><?= date('h:i', strtotime($apt['booking_time'])) ?></p>
                                            <p class="text-xs text-slate-400 font-medium uppercase"><?= date('A', strtotime($apt['booking_time'])) ?></p>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start mb-1">
                                                <h4 class="font-bold text-slate-800 text-lg"><?= e($apt['first_name'] . ' ' . $apt['last_name']) ?></h4>
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $statusColor ?>"><?= $statusText ?></span>
                                            </div>
                                            <p class="text-sm text-slate-500 mb-3 flex items-center gap-4">
                                                <span class="flex items-center gap-1"><i data-lucide="phone" width="14"></i> <?= e($apt['contact']) ?></span>
                                                <span class="flex items-center gap-1"><i data-lucide="user" width="14"></i> <?= e($apt['gender'] ?? 'Patient') ?></span>
                                            </p>
                                            <div class="flex gap-3">
                                                <a href="doctor_records.php?patient_id=<?= $apt['id'] ?>" class="text-xs font-bold text-green-600 hover:text-green-700 bg-green-50 hover:bg-green-100 px-3 py-1.5 rounded-lg transition">
                                                    View History
                                                </a>
                                                <?php if(in_array($apt['status'], [1, 2])): ?>
                                                <a href="doctor_consultation.php?id=<?= $apt['id'] ?>" class="text-xs font-bold text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-lg transition shadow-sm shadow-green-200">
                                                    Start Consultation
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="lg:col-span-1 space-y-8">
                        
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-1 overflow-hidden">
                             <?php include 'calendar_widget.php'; ?> 
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i data-lucide="calendar-arrow-up" class="text-blue-600"></i> Next Up
                            </h3>
                            <div class="space-y-3">
                                <?php if(empty($upcoming_appointments)): ?>
                                    <div class="bg-white p-6 rounded-xl border border-slate-200 text-center">
                                        <p class="text-sm text-slate-400 italic">No future appointments yet.</p>
                                    </div>
                                <?php else: foreach($upcoming_appointments as $up): ?>
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition flex items-center gap-3">
                                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex flex-col items-center justify-center text-blue-700 flex-shrink-0 border border-blue-100">
                                            <span class="text-xs font-bold uppercase"><?= date('M', strtotime($up['booking_date'])) ?></span>
                                            <span class="text-lg font-bold leading-none"><?= date('d', strtotime($up['booking_date'])) ?></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-slate-800 truncate"><?= e($up['first_name'] . ' ' . $up['last_name']) ?></p>
                                            <p class="text-xs text-slate-500 font-medium">
                                                <?= date('D', strtotime($up['booking_date'])) ?> • <?= date('h:i A', strtotime($up['booking_time'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar'); // Assuming your included sidebar has this ID
        
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                // Toggle logic depends on your sidebar implementation
                // If your sidebar uses 'hidden' class for mobile:
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-full');
            });
        }
    </script>
</body>
</html>