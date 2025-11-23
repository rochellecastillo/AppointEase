<?php
// client_home.php - Secure Patient Dashboard
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

session_require_auth(['user']); 

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    log_security_event('logout', ['user_id' => session_get_user_id()]);
    session_destroy_user();
    header('Location: login.php?logout=success');
    exit;
}

$user_id = session_get_user_id();
$user_name = session_get_username();

// Fetch Page Data
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = $user_name;
if ($info && !empty($info['first_name'])) {
    $display_name = $info['first_name'] . ' ' . $info['last_name'];
}
$currentUser = ['name' => $display_name];
$today = date('Y-m-d');

// Fetch Appointments
$stmt = $pdo->prepare("
    SELECT a.booking_date, a.id, a.status, d.first_name, d.last_name
    FROM tblappointment a
    LEFT JOIN tblinfo d ON d.user_id = a.doctor
    WHERE a.user_id = ?
    ORDER BY a.booking_date ASC
");
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$upcomingList = [];
$recentHistory = [];
$nextAppointment = null;

foreach ($rows as $r) {
  $date = $r['booking_date'];
  if (!$date) continue;
  
  $doctorName = trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? ''));
  $statusInt = (int)$r['status'];
  
  // UPDATED STATUS MAPPING
  if ($statusInt === 1) $status = 'Confirmed';
  elseif ($statusInt === 2) $status = 'Pending';
  elseif ($statusInt === 3) $status = 'Completed'; // Added Completed
  elseif ($statusInt === 0) $status = 'Cancelled';
  else $status = 'Unknown';

  // Filter Logic
  // Show "Upcoming" if date is today/future AND status is NOT Cancelled OR Completed
  if ($date >= $today && $statusInt !== 0 && $statusInt !== 3) {
    $item = [
      'doctor' => $doctorName ?: 'TBD',
      'date' => $date,
      'status' => $status, // Capitalized by variable assignment logic above
      'id' => $r['id']
    ];
    $upcomingList[] = $item;
    
    if (!$nextAppointment && ($status === 'Confirmed' || $status === 'Pending')) {
        $nextAppointment = $item;
    }
  } else {
    // History: Past dates, Cancelled, or Completed
    $recentHistory[] = [
      'doctor' => $doctorName ?: 'TBD',
      'date' => $date,
      'status' => $status
    ];
  }
}
$recentHistory = array_slice(array_reverse($recentHistory), 0, 3);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Health Dashboard - AppointEase</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    .card-hover { transition: all 0.3s ease; }
    .card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
  </style>
</head>
<body class="text-gray-800">
  <div class="flex h-screen overflow-hidden">
    <?php include 'includes/client_sidebar.php'; ?>
    <main class="flex-1 overflow-auto relative">
        <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20">
            <span class="font-bold text-lg text-purple-700">AppointEase</span>
            <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
        </div>
        <div class="p-6 md:p-8 max-w-7xl mx-auto space-y-8">
            
            <header class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-purple-700 to-indigo-600 text-white shadow-2xl shadow-purple-200">
                <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 rounded-full bg-white opacity-10 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 rounded-full bg-pink-500 opacity-20 blur-3xl"></div>
                <div class="relative z-10 p-8 md:p-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3 text-purple-200 bg-white/10 w-fit px-3 py-1 rounded-full text-sm backdrop-blur-md">
                            <i data-lucide="sparkles" width="14" height="14"></i>
                            <span>Welcome back, <?= e(explode(' ', $currentUser['name'])[0]) ?></span>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold mb-2">How are you feeling today?</h2>
                        <p class="text-purple-100 opacity-90">Manage your appointments and health records with ease.</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-2xl w-full md:w-auto min-w-[300px]">
                        <?php if ($nextAppointment): ?>
                            <p class="text-xs font-bold text-purple-200 uppercase tracking-wider mb-3">Next Appointment</p>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-white text-purple-700 flex flex-col items-center justify-center font-bold leading-none shadow-lg">
                                    <span class="text-xs uppercase"><?= date('M', strtotime($nextAppointment['date'])) ?></span>
                                    <span class="text-lg"><?= date('d', strtotime($nextAppointment['date'])) ?></span>
                                </div>
                                <div>
                                    <p class="font-bold text-lg">Dr. <?= e($nextAppointment['doctor']) ?></p>
                                    <div class="flex items-center gap-2 text-sm text-purple-100">
                                        <i data-lucide="clock" width="14" height="14"></i>
                                        <span>Confirmed</span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-xs font-bold text-purple-200 uppercase tracking-wider mb-2">No Upcoming Visits</p>
                            <a href="book_appointment.php" class="block w-full py-2 px-4 bg-white text-purple-700 font-bold rounded-xl text-center hover:bg-gray-50 transition shadow-lg">Book Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="book_appointment.php" class="card-hover bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center gap-3 group">
                    <div class="w-14 h-14 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors"><i data-lucide="plus" width="28" height="28"></i></div>
                    <span class="font-semibold text-gray-700">Book Visit</span>
                </a>
                <a href="find_doctors.php" class="card-hover bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center gap-3 group">
                    <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors"><i data-lucide="search" width="24" height="24"></i></div>
                    <span class="font-semibold text-gray-700">Find Doctor</span>
                </a>
                <a href="medical_records.php" class="card-hover bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center gap-3 group">
                    <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors"><i data-lucide="file-text" width="24" height="24"></i></div>
                    <span class="font-semibold text-gray-700">Records</span>
                </a>
                <a href="client_settings.php" class="card-hover bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center gap-3 group">
                    <div class="w-14 h-14 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors"><i data-lucide="user" width="24" height="24"></i></div>
                    <span class="font-semibold text-gray-700">Profile</span>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2"><i data-lucide="calendar-clock" class="text-purple-600"></i> Upcoming Appointments</h3>
                        <a href="client_appointments.php" class="text-sm font-semibold text-purple-600 hover:underline">View All</a>
                    </div>

                    <?php if (empty($upcomingList)): ?>
                        <div class="bg-white rounded-2xl p-10 text-center border border-gray-100 shadow-sm">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4"><i data-lucide="coffee" class="text-gray-400" width="24" height="24"></i></div>
                            <h4 class="font-bold text-gray-700">No upcoming visits</h4>
                            <p class="text-gray-500 text-sm mt-1">You are all caught up!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($upcomingList as $apt): 
                                $isConfirmed = $apt['status'] === 'Confirmed';
                                $badgeColor = $isConfirmed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                            ?>
                            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex flex-col items-center justify-center w-16 h-16 bg-gray-50 rounded-2xl border border-gray-100">
                                        <span class="text-xs font-bold text-gray-500 uppercase"><?= date('M', strtotime($apt['date'])) ?></span>
                                        <span class="text-xl font-bold text-gray-800"><?= date('d', strtotime($apt['date'])) ?></span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-lg">Dr. <?= e($apt['doctor']) ?></h4>
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeColor ?>">
                                                <?= e($apt['status']) ?>
                                            </span>
                                            <span>• General Checkup</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2 w-full sm:w-auto">
                                    <a href="appointment_details.php?id=<?= $apt['id'] ?>" class="w-full sm:w-auto px-6 py-2 bg-purple-50 text-purple-700 text-sm font-bold rounded-xl hover:bg-purple-100 transition border border-purple-100 flex items-center justify-center gap-2">
                                        View Details <i data-lucide="arrow-right" width="16"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($recentHistory)): ?>
                    <div class="pt-4">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Activity</h3>
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <?php foreach ($recentHistory as $index => $h): ?>
                                <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition <?= $index !== count($recentHistory)-1 ? 'border-b border-gray-100' : '' ?>">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500"><i data-lucide="check" width="16"></i></div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">Dr. <?= e($h['doctor']) ?></p>
                                            <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($h['date'])) ?></p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-400"><?= e($h['status']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <section class="lg:col-span-1 bg-white rounded-2xl shadow-lg border border-gray-100">
                    <?php include 'calendar_widget.php'; ?>
                </section>
            </div>
        </div>
    </main>
  </div>
  <script>
    lucide.createIcons();
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
  </script>
</body>
</html>