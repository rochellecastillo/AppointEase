<?php
// notifications.php - View and manage notifications
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Fetch recent appointments for notifications
$stmt = $pdo->prepare("SELECT a.id, a.booking_date, a.status,
                              d.first_name AS dfirst, d.last_name AS dlast
                       FROM tblappointment a
                       LEFT JOIN tblinfo d ON d.user_id = a.doctor
                       WHERE a.user_id = ?
                       ORDER BY a.booking_date DESC
                       LIMIT 20");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate notifications from appointments
$notifications = [];
$today = date('Y-m-d');

foreach ($appointments as $apt) {
    $doctor = trim(($apt['dlast'] ?? '') . ', ' . ($apt['dfirst'] ?? ''));
    $date = $apt['booking_date'];
    $status = (int)$apt['status'];
    
    // Upcoming appointment reminders
    if ($date >= $today && $status == 1) {
        $daysUntil = (strtotime($date) - strtotime($today)) / (60 * 60 * 24);
        if ($daysUntil <= 7) {
            $notifications[] = [
                'type' => 'reminder',
                'icon' => 'bell',
                'color' => 'blue',
                'title' => 'Upcoming Appointment',
                'message' => "You have an appointment with Dr. {$doctor} on " . date('M d, Y', strtotime($date)),
                'time' => 'In ' . ceil($daysUntil) . ' days',
                'read' => false
            ];
        }
    }
    
    // Confirmed appointments
    if ($status == 1 && strtotime($date) > strtotime('-7 days')) {
        $notifications[] = [
            'type' => 'success',
            'icon' => 'check-circle',
            'color' => 'green',
            'title' => 'Appointment Confirmed',
            'message' => "Your appointment with Dr. {$doctor} has been confirmed for " . date('M d, Y', strtotime($date)),
            'time' => date('M d', strtotime($date)),
            'read' => true
        ];
    }
    
    // Pending appointments
    if ($status == 2) {
        $notifications[] = [
            'type' => 'pending',
            'icon' => 'clock',
            'color' => 'yellow',
            'title' => 'Appointment Pending',
            'message' => "Your appointment request with Dr. {$doctor} is awaiting confirmation",
            'time' => date('M d', strtotime($date)),
            'read' => false
        ];
    }
    
    // Cancelled appointments
    if ($status == 0 && strtotime($date) > strtotime('-7 days')) {
        $notifications[] = [
            'type' => 'cancelled',
            'icon' => 'x-circle',
            'color' => 'red',
            'title' => 'Appointment Cancelled',
            'message' => "Your appointment with Dr. {$doctor} on " . date('M d, Y', strtotime($date)) . " has been cancelled",
            'time' => date('M d', strtotime($date)),
            'read' => true
        ];
    }
}

// Add system notifications
$notifications[] = [
    'type' => 'info',
    'icon' => 'info',
    'color' => 'purple',
    'title' => 'Welcome to AppointmentEase',
    'message' => 'Thank you for using our online appointment system. Book your appointments easily!',
    'time' => '1 week ago',
    'read' => true
];

$unreadCount = count(array_filter($notifications, fn($n) => !$n['read']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Notifications</h1>
                    <p class="text-gray-600 mt-1">
                        <?= $unreadCount > 0 ? "You have {$unreadCount} unread notification" . ($unreadCount > 1 ? 's' : '') : 'All caught up!' ?>
                    </p>
                </div>
                <div class="flex gap-4">
                    <?php if ($unreadCount > 0): ?>
                        <button onclick="markAllRead()" class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-6 rounded-lg transition">
                            Mark All Read
                        </button>
                    <?php endif; ?>
                    <a href="client_home.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-6 rounded-lg transition">
                        <i data-lucide="arrow-left" class="inline" width="18" height="18"></i>
                        Back
                    </a>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="bg-white rounded-lg shadow">
                <?php if (empty($notifications)): ?>
                    <div class="p-12 text-center">
                        <i data-lucide="bell-off" class="mx-auto text-gray-400 mb-4" width="64" height="64"></i>
                        <p class="text-gray-500 text-lg">No notifications yet</p>
                        <p class="text-gray-400 text-sm mt-2">We'll notify you about important updates</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($notifications as $index => $notif): 
                            $colorClasses = [
                                'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-200'],
                                'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'border' => 'border-green-200'],
                                'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'border' => 'border-yellow-200'],
                                'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'border' => 'border-red-200'],
                                'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'border' => 'border-purple-200']
                            ];
                            $colors = $colorClasses[$notif['color']] ?? $colorClasses['blue'];
                        ?>
                            <div class="p-6 hover:bg-gray-50 transition <?= !$notif['read'] ? 'bg-blue-50/30' : '' ?>">
                                <div class="flex gap-4">
                                    <div class="<?= $colors['bg'] ?> p-3 rounded-full h-fit">
                                        <i data-lucide="<?= e($notif['icon']) ?>" class="<?= $colors['text'] ?>" width="24" height="24"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-start justify-between mb-2">
                                            <h3 class="font-bold text-gray-800"><?= e($notif['title']) ?></h3>
                                            <?php if (!$notif['read']): ?>
                                                <span class="ml-2 w-2 h-2 bg-blue-600 rounded-full"></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-gray-600 text-sm mb-3"><?= e($notif['message']) ?></p>
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs text-gray-500 flex items-center gap-2">
                                                <i data-lucide="clock" width="14" height="14"></i>
                                                <?= e($notif['time']) ?>
                                            </p>
                                            <?php if (!$notif['read']): ?>
                                                <button onclick="markRead(<?= $index ?>)" class="text-xs text-purple-600 hover:text-purple-700 font-semibold">
                                                    Mark as read
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Settings -->
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Notification Settings</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-800">Appointment Reminders</p>
                            <p class="text-sm text-gray-600">Get notified about upcoming appointments</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-800">Confirmation Notifications</p>
                            <p class="text-sm text-gray-600">Get notified when appointments are confirmed</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-800">System Updates</p>
                            <p class="text-sm text-gray-600">Get notified about system maintenance and updates</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.replace();

        function markRead(index) {
            // In a real application, this would make an AJAX call to update the database
            alert('Notification marked as read');
            location.reload();
        }

        function markAllRead() {
            // In a real application, this would make an AJAX call to update the database
            alert('All notifications marked as read');
            location.reload();
        }
    </script>
</body>
</html>