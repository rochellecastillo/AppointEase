<?php
include __DIR__ . '/controllers/admin_home_data.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - AppointmentEase</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }
    .icon { display: inline-flex; vertical-align: middle; }
    .stat-card { transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
    .gradient-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">
  <div class="flex h-screen overflow-hidden">
    
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="flex-1 overflow-auto relative">
      <div class="h-64 gradient-header absolute top-0 left-0 w-full z-0"></div>

      <div class="p-8 relative z-10">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 text-white">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="sparkles" class="icon" width="18" height="18"></i>
                    <span class="text-purple-200 text-sm font-medium">Admin Dashboard</span>
                </div>
                <h1 class="text-3xl font-bold">Welcome back, <?= htmlspecialchars($adminName) ?>!</h1>
                <p class="text-purple-100 opacity-90">Here is the latest hospital activity.</p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center gap-3 bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl border border-white/20">
                <div class="text-right">
                    <p class="text-xs text-purple-200">Current Time</p>
                    <p class="font-bold font-mono" id="currentTime">--:--</p>
                </div>
                <i data-lucide="clock" width="24" height="24"></i>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="stat-card bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                <div class="relative">
                    <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center mb-3">
                        <i data-lucide="loader-2" width="20"></i>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Pending</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= (int)$pending_count ?></h3>
                    <a href="appointment_list_report.php?status=2" class="absolute top-0 right-0 p-4 opacity-0 group-hover:opacity-100 transition-opacity text-orange-400">
                        <i data-lucide="arrow-right" width="20"></i>
                    </a>
                </div>
            </div>

            <div class="stat-card bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="relative">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-3">
                        <i data-lucide="calendar-clock" width="20"></i>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Today</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= (int)$today_count ?></h3>
                </div>
            </div>

            <div class="stat-card bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="relative">
                    <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-3">
                        <i data-lucide="stethoscope" width="20"></i>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Doctors</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= (int)$total_doctors ?></h3>
                </div>
            </div>

            <div class="stat-card bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="relative">
                    <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-3">
                        <i data-lucide="users" width="20"></i>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Patients</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= (int)$total_patients ?></h3>
                </div>
            </div>

            <div class="stat-card bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="relative">
                    <div class="w-10 h-10 bg-pink-100 text-pink-600 rounded-xl flex items-center justify-center mb-3">
                        <i data-lucide="file-heart" width="20"></i>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Profiles</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= (int)$total_profiles ?></h3>
                </div>
            </div>
        </div>

        <section class="mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="zap" class="text-purple-600" width="20"></i> Quick Actions
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <a href="add_doctor.php" class="bg-white border border-gray-200 p-4 rounded-xl flex flex-col items-center text-center hover:border-blue-500 hover:shadow-md transition group">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-2 group-hover:bg-blue-600 group-hover:text-white transition"><i data-lucide="user-plus" width="20"></i></div>
                    <span class="font-semibold text-sm">Add Doctor</span>
                </a>
                <a href="add_patient.php" class="bg-white border border-gray-200 p-4 rounded-xl flex flex-col items-center text-center hover:border-green-500 hover:shadow-md transition group">
                    <div class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center mb-2 group-hover:bg-green-600 group-hover:text-white transition"><i data-lucide="heart-pulse" width="20"></i></div>
                    <span class="font-semibold text-sm">Add Patient</span>
                </a>
                <a href="schedule_manage.php" class="bg-white border border-gray-200 p-4 rounded-xl flex flex-col items-center text-center hover:border-purple-500 hover:shadow-md transition group">
                    <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mb-2 group-hover:bg-purple-600 group-hover:text-white transition"><i data-lucide="calendar-clock" width="20"></i></div>
                    <span class="font-semibold text-sm">Schedules</span>
                </a>
                <a href="admin_medical_records.php" class="bg-white border border-gray-200 p-4 rounded-xl flex flex-col items-center text-center hover:border-pink-500 hover:shadow-md transition group">
                    <div class="w-10 h-10 bg-pink-50 text-pink-600 rounded-full flex items-center justify-center mb-2 group-hover:bg-pink-600 group-hover:text-white transition"><i data-lucide="clipboard-list" width="20"></i></div>
                    <span class="font-semibold text-sm">Health Profiles</span>
                </a>
                <a href="reports.php" class="bg-white border border-gray-200 p-4 rounded-xl flex flex-col items-center text-center hover:border-orange-500 hover:shadow-md transition group">
                    <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center mb-2 group-hover:bg-orange-600 group-hover:text-white transition"><i data-lucide="bar-chart-3" width="20"></i></div>
                    <span class="font-semibold text-sm">Reports</span>
                </a>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="clock" class="text-purple-600" width="20"></i> Recent Appointments
                        </h2>
                        <a href="appointment_list_report.php" class="text-sm text-purple-600 hover:underline font-medium">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 font-medium">Patient</th>
                                    <th class="px-6 py-4 font-medium">Doctor</th>
                                    <th class="px-6 py-4 font-medium">Date & Time</th>
                                    <th class="px-6 py-4 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($recentAppointments)): ?>
                                    <tr><td colspan="4" class="p-8 text-center text-gray-500">No recent appointments found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentAppointments as $apt):
                                        $patient = trim(($apt['plast'] ?? '') . ', ' . ($apt['pfirst'] ?? ''));
                                        $doctor = trim(($apt['dlast'] ?? '') . ', ' . ($apt['dfirst'] ?? ''));
                                        $status = (int)$apt['status'];
                                        
                                        $badge = match($status) {
                                            1 => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700"><i data-lucide="check-circle" width="12"></i> Confirmed</span>',
                                            2 => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700"><i data-lucide="clock" width="12"></i> Pending</span>',
                                            3 => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700"><i data-lucide="check-square" width="12"></i> Completed</span>',
                                            0 => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700"><i data-lucide="x-circle" width="12"></i> Cancelled</span>',
                                            default => '<span class="text-gray-500 text-xs">Unknown</span>'
                                        };
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($patient ?: 'Unknown') ?></div>
                                            <div class="text-xs text-gray-400">#<?= htmlspecialchars($apt['id']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">Dr. <?= htmlspecialchars($doctor ?: 'TBD') ?></td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?= htmlspecialchars(date('M d, Y', strtotime($apt['booking_date'] ?? ''))) ?>
                                            <span class="text-xs text-gray-400 ml-1"><?= !empty($apt['booking_time']) ? htmlspecialchars(date('h:i A', strtotime($apt['booking_time']))) : '' ?></span>
                                        </td>
                                        <td class="px-6 py-4"><?= $badge ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i data-lucide="activity" class="text-blue-500" width="20"></i> Recent Activity
                    </h3>
                    <div class="space-y-4">
                        <?php if (empty($activities)): ?>
                            <p class="text-sm text-gray-400 italic">No recent activities logged.</p>
                        <?php else: ?>
                            <?php foreach ($activities as $act): ?>
                                <div class="flex gap-3">
                                    <div class="mt-1.5 w-2 h-2 rounded-full bg-purple-400 flex-shrink-0"></div>
                                    <div>
                                        <p class="text-sm text-gray-700 leading-snug">
                                            <span class="font-semibold"><?= htmlspecialchars($act['action_type']) ?></span>
                                            <span class="text-gray-500"> - <?= htmlspecialchars($act['details'] ?? '') ?></span>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <?= htmlspecialchars(date('M d • h:i A', strtotime($act['created_at'] ?? ''))) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="activity_log.php" class="block mt-6 text-center text-xs font-bold text-blue-600 hover:text-blue-700 uppercase tracking-wide">View Full Log</a>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                    <?php include 'calendar_widget.php'; ?>
                </div>

            </div>
        </div>

      </div>
    </main>
  </div>

  <script>
    lucide.createIcons();
    function updateTime() {
        const now = new Date();
        document.getElementById('currentTime').innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    setInterval(updateTime, 1000);
    updateTime();
  </script>
</body>
</html>