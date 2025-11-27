<?php
// client_appointments.php - Enhanced Appointment Management
// ---------------------------------------------------------
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Client Authentication
session_require_auth(['user']);

$user_id = session_get_user_id();

// Fetch all appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.booking_date, a.status,
               d.first_name AS doc_first, d.last_name AS doc_last, 
               d.specialization, d.contact AS doc_contact
        FROM tblappointment a
        LEFT JOIN tblinfo d ON d.user_id = a.doctor
        WHERE a.user_id = ?
        ORDER BY a.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . e($e->getMessage()));
}

// Categorize appointments
$upcoming = [];
$past = [];
$cancelled = [];
$today = date('Y-m-d');

foreach ($appointments as $apt) {
    $status = (int)$apt['status'];
    $date = $apt['booking_date'];
    
    // Logic:
    // 0 = Cancelled -> Cancelled Tab
    // 3 = Completed -> Past Tab (regardless of date)
    // Others (1, 2) -> If date >= today -> Upcoming, else -> Past
    
    if ($status === 0) {
        $cancelled[] = $apt;
    } elseif ($status === 3) {
        $past[] = $apt; // Completed always goes to history
    } elseif ($date >= $today) {
        $upcoming[] = $apt;
    } else {
        $past[] = $apt;
    }
}

// Helper to render status badges
function getStatusBadge($statusInt) {
    switch ($statusInt) {
        case 1: // Confirmed
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Confirmed</span>';
        case 2: // Pending
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Pending</span>';
        case 3: // Completed
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">Completed</span>';
        case 0: // Cancelled
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Cancelled</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Unknown</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - AppointEase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .tab-active { border-bottom: 2px solid #7c3aed; color: #7c3aed; font-weight: 600; }
        .tab-inactive { border-bottom: 2px solid transparent; color: #6b7280; }
        .tab-inactive:hover { color: #374151; border-color: #e5e7eb; }
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

            <div class="p-6 md:p-8 max-w-6xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">My Appointments</h1>
                        <p class="text-gray-500 mt-1">Manage your scheduled visits and history</p>
                    </div>
                    <a href="book_appointment.php" class="inline-flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-xl font-medium transition shadow-lg shadow-purple-200">
                        <i data-lucide="plus" width="18" height="18"></i>
                        Book New Visit
                    </a>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-6">
                    <div class="border-b border-gray-100 px-2">
                        <nav class="flex gap-4" aria-label="Tabs">
                            <button onclick="showTab('upcoming')" id="tab-upcoming" class="tab-btn tab-active px-4 py-4 text-sm transition-colors relative">
                                Upcoming
                                <span class="ml-2 bg-purple-100 text-purple-600 py-0.5 px-2 rounded-full text-xs"><?= count($upcoming) ?></span>
                            </button>
                            <button onclick="showTab('past')" id="tab-past" class="tab-btn tab-inactive px-4 py-4 text-sm transition-colors relative">
                                Past History
                                <span class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?= count($past) ?></span>
                            </button>
                            <button onclick="showTab('cancelled')" id="tab-cancelled" class="tab-btn tab-inactive px-4 py-4 text-sm transition-colors relative">
                                Cancelled
                                <span class="ml-2 bg-red-50 text-red-600 py-0.5 px-2 rounded-full text-xs"><?= count($cancelled) ?></span>
                            </button>
                        </nav>
                    </div>

                    <div class="p-6 bg-gray-50/50 rounded-b-2xl min-h-[400px]">
                        
                        <div id="content-upcoming" class="tab-content space-y-4">
                            <?php if (empty($upcoming)): ?>
                                <?php renderEmptyState('calendar-clock', 'No upcoming appointments', 'Time to schedule a checkup?'); ?>
                            <?php else: foreach ($upcoming as $apt): ?>
                                <?php renderAppointmentCard($apt, 'upcoming'); ?>
                            <?php endforeach; endif; ?>
                        </div>

                        <div id="content-past" class="tab-content hidden space-y-4">
                            <?php if (empty($past)): ?>
                                <?php renderEmptyState('history', 'No past history found', 'Your completed visits will appear here.'); ?>
                            <?php else: foreach ($past as $apt): ?>
                                <?php renderAppointmentCard($apt, 'past'); ?>
                            <?php endforeach; endif; ?>
                        </div>

                        <div id="content-cancelled" class="tab-content hidden space-y-4">
                            <?php if (empty($cancelled)): ?>
                                <?php renderEmptyState('check-circle-2', 'No cancelled appointments', 'Great! You have a perfect attendance record.'); ?>
                            <?php else: foreach ($cancelled as $apt): ?>
                                <?php renderAppointmentCard($apt, 'cancelled'); ?>
                            <?php endforeach; endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php
    // Helper Function to Render Cards
    function renderAppointmentCard($apt, $type) {
        $doctor_name = trim(($apt['doc_last'] ?? '') . ', ' . ($apt['doc_first'] ?? ''));
        $dateObj = new DateTime($apt['booking_date']);
        ?>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-all duration-200 group">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-5">
                
                <div class="flex-shrink-0 flex flex-col items-center justify-center w-16 h-16 bg-gray-50 rounded-xl border border-gray-200 group-hover:border-purple-200 group-hover:bg-purple-50 transition-colors">
                    <span class="text-xs font-bold text-gray-500 uppercase group-hover:text-purple-600"><?= $dateObj->format('M') ?></span>
                    <span class="text-xl font-bold text-gray-900 group-hover:text-purple-700"><?= $dateObj->format('d') ?></span>
                    <span class="text-[10px] text-gray-400 group-hover:text-purple-400"><?= $dateObj->format('D') ?></span>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-lg font-bold text-gray-900 truncate">Dr. <?= e($doctor_name) ?></h3>
                        <?= getStatusBadge((int)$apt['status']) ?>
                    </div>
                    <p class="text-sm text-gray-600 mb-2 flex items-center gap-2">
                        <i data-lucide="stethoscope" class="w-3 h-3"></i>
                        <?= e($apt['specialization'] ?: 'General Practitioner') ?>
                    </p>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-3 h-3"></i> <?= $dateObj->format('Y') ?></span>
                        <?php if($apt['doc_contact']): ?>
                            <span class="flex items-center gap-1.5"><i data-lucide="phone" class="w-3 h-3"></i> <?= e($apt['doc_contact']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto mt-4 md:mt-0">
                    
                    <a href="appointment_details.php?id=<?= $apt['id'] ?>" class="flex-1 md:flex-none inline-flex justify-center items-center w-10 h-10 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-purple-600 transition" title="View Details">
                        <i data-lucide="eye" width="20"></i>
                    </a>
                    
                    <?php if($type === 'upcoming' && (int)$apt['status'] !== 3): ?>
                        <a href="reschedule.php?id=<?= $apt['id'] ?>" class="flex-1 md:flex-none px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-center">
                            Reschedule
                        </a>
                        <a href="cancel_appointment.php?id=<?= $apt['id'] ?>" onclick="return confirm('Are you sure?')" class="flex-1 md:flex-none px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition text-center">
                            Cancel
                        </a>
                    <?php endif; ?>

                    <?php if((int)$apt['status'] === 3): ?>
                        <a href="medical_records.php?search=<?= urlencode($doctor_name) ?>" class="flex-1 md:flex-none px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-100 transition text-center">
                            View Record
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php
    }

    // Helper for Empty State
    function renderEmptyState($icon, $title, $subtitle) {
        ?>
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i data-lucide="<?= $icon ?>" class="text-gray-400" width="32" height="32"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900"><?= $title ?></h3>
            <p class="text-gray-500 max-w-sm mt-1"><?= $subtitle ?></p>
        </div>
        <?php
    }
    ?>

    <script>
        // Initialize Icons
        lucide.createIcons();

        // Tab Switching Logic
        function showTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            
            // Reset buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('tab-inactive');
            });

            // Activate selected
            document.getElementById('content-' + tabName).classList.remove('hidden');
            const activeBtn = document.getElementById('tab-' + tabName);
            activeBtn.classList.remove('tab-inactive');
            activeBtn.classList.add('tab-active');
        }

        // Mobile Sidebar Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        if(mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
            });
        }
    </script>
</body>
</html>