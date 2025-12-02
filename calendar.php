<?php
// calendar.php - Redesigned Full calendar interface with FullCalendar.js
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require 'db.php';

$my_user_id = $_SESSION['user_id'];
$my_user_type = strtolower($_SESSION['user_type']);
$csrf_token = $_SESSION['csrf_token'];

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// fetch list of doctors for admin filter (non-blocking if DB empty)
$doctors = [];
if ($my_user_type === 'admin') {
    $stmt = $pdo->prepare("SELECT user_id, CONCAT(first_name, ' ', last_name) as name FROM tblinfo WHERE specialization != '' OR user_id LIKE 'U%' ORDER BY first_name");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get current user's info
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ? LIMIT 1");
$stmt->execute([$my_user_id]);
$my_info = $stmt->fetch(PDO::FETCH_ASSOC);

$back_url = $my_user_type === 'doctor' ? 'doctor_home.php' :
            ($my_user_type === 'admin' ? 'admin_home.php' : 'client_home.php');

$page_title = $my_user_type === 'doctor' ? 'My Schedule Calendar' :
              ($my_user_type === 'admin' ? 'Hospital Calendar' : 'My Appointments Calendar');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($page_title) ?> - AppointmentEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .fc-toolbar-title { font-size:1.25rem !important; font-weight:700 !important; }
        .fc-button { background-color:#6b46c1 !important; border-color:#6b46c1 !important; color:white !important; text-transform:none !important; font-weight:500 !important; padding:0.45rem 0.9rem !important; border-radius:0.5rem !important; }
        .fc-button:hover { background-color:#55359a !important; }
        .fc-daygrid-day-number { font-weight:600; }
        .card { background: white; border-radius: 0.75rem; box-shadow: 0 6px 18px rgba(15,23,42,0.06); }
        .compact-legend { display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center; }
        .legend-item { display:flex; gap:0.5rem; align-items:center; font-size:0.85rem; }
        .filter-pill { cursor:pointer; padding:0.35rem 0.6rem; border-radius:999px; font-weight:600; font-size:0.8rem; }
        @media (min-width: 1024px) {
            #calendar { min-height: 720px; }
        }
        /* subtle scrollbar for long lists */
        .scrollable { max-height: 360px; overflow-y: auto; padding-right: 6px; }
        .status-dot { width:12px; height:12px; border-radius:4px; display:inline-block; margin-right:0.5rem; }
        .btn-outline { border:1px solid #e6e6f0; background:white; padding:0.45rem 0.8rem; border-radius:0.5rem; font-weight:600; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="min-h-screen p-4 md:p-8">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Header -->
            <div class="lg:col-span-12">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="<?= e($back_url) ?>" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg transition" aria-label="Back to dashboard">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            <span class="hidden sm:inline text-sm font-medium">Back</span>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?= e($page_title) ?></h1>
                            <p class="text-sm text-gray-600">View, filter, and manage appointments and schedules.</p>
                        </div>
                    </div>

                    <div class="flex gap-2 items-center">
                        <div class="hidden sm:flex items-center gap-2 bg-white p-2 rounded-lg shadow-sm">
                            <div class="compact-legend">
                                <div class="legend-item"><span class="status-dot" style="background:#10b981"></span><span class="text-xs">Confirmed</span></div>
                                <div class="legend-item"><span class="status-dot" style="background:#f59e0b"></span><span class="text-xs">Pending</span></div>
                                <div class="legend-item"><span class="status-dot" style="background:#6b7280"></span><span class="text-xs">Cancelled</span></div>
                                <?php if ($my_user_type === 'doctor'): ?>
                                    <div class="legend-item"><span class="status-dot" style="background:#ef4444"></span><span class="text-xs">Unavailable</span></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button id="exportBtnTop" class="btn-outline flex items-center gap-2 text-sm">
                            <i data-lucide="download" class="w-4 h-4"></i> Export iCal
                        </button>

                        <button id="todayBtn" class="btn-outline flex items-center gap-2 text-sm hidden md:inline-flex">
                            <i data-lucide="clock" class="w-4 h-4"></i> Today
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar Column -->
            <div class="lg:col-span-8">
                <div class="card p-4 lg:p-6">
                    <div id="calendar"></div>
                </div>
            </div>

            <!-- Right Control Panel -->
            <aside class="lg:col-span-4 space-y-6">
                <!-- Filters card -->
                <div class="card p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Filters</h3>
                        <button id="resetFilters" class="text-xs text-gray-500 hover:text-gray-700">Reset</button>
                    </div>

                    <div class="mt-3 space-y-3">
                        <?php if ($my_user_type === 'admin'): ?>
                            <label class="block text-xs font-semibold text-gray-600">Doctor</label>
                            <select id="filterDoctor" class="w-full p-2 mt-1 border rounded-md text-sm">
                                <option value="">All doctors</option>
                                <?php foreach ($doctors as $d): ?>
                                    <option value="<?= e($d['user_id']) ?>"><?= e($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <label class="block text-xs font-semibold text-gray-600">Status</label>
                        <div class="flex gap-2 mt-2">
                            <button class="filter-pill bg-green-50 text-green-700" data-status="Confirmed">Confirmed</button>
                            <button class="filter-pill bg-yellow-50 text-yellow-700" data-status="Pending">Pending</button>
                            <button class="filter-pill bg-gray-50 text-gray-700" data-status="Cancelled">Cancelled</button>
                            <button class="filter-pill bg-sky-50 text-sky-700" data-status="Completed">Completed</button>
                        </div>

                        <div class="mt-3 text-xs text-gray-500">
                            Tip: Click any event on the calendar to view details. Use filters to limit visible events.
                        </div>
                    </div>
                </div>

                <!-- Upcoming events -->
                <div class="card p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Upcoming</h3>
                        <a href="calendar.php" class="text-sm text-blue-600 hover:underline">View All</a>
                    </div>

                    <div id="sidebarUpcoming" class="mt-3 space-y-2 scrollable">
                        <p class="text-xs text-gray-500 text-center py-6">Loading upcoming events...</p>
                    </div>
                </div>

                <!-- Doctor Schedule / Info -->
                <?php if ($my_user_type === 'doctor'): ?>
                <div id="doctorSchedulePanel" class="card p-4">
                    <h3 class="font-semibold text-gray-800">Weekly Schedule</h3>
                    <div id="scheduleInfo" class="mt-3 text-sm text-gray-600">Loading schedule information...</div>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <!-- Modal (keeps your previous structure) -->
    <div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div id="modalContainer" class="bg-white rounded-xl shadow-2xl max-w-2xl w-full transform transition-all scale-95 opacity-0">
            <div class="p-6 border-b flex items-center justify-between">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-800">Details</h2>
                <button id="modalCloseBtn" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js" defer></script>

    <script src="js/calendar.js" defer></script>
</body>
</html>