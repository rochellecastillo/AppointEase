<?php
// reports.php - Comprehensive Reports & Analytics with Date Filtering
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

// Require admin authentication
session_require_auth(['admin']);

// --- Date Filter Setup ---
$today = date('Y-m-d');
$default_start_date = date('Y-m-d', strtotime('-30 days'));

$start_date = $_GET['start_date'] ?? $default_start_date;
$end_date = $_GET['end_date'] ?? $today;

// Base WHERE clause and parameters for appointment queries
$where_clause = "WHERE a.booking_date >= :start_date AND a.booking_date <= :end_date";
$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

// NOTE: The function e() is now loaded via require_once 'security_helper.php';

// --- FETCH ALL REPORTS ---
try {
    // 1. Appointment Status Breakdown
    // Assuming status: 1=Confirmed/Completed, 0=Pending, 2=Cancelled (or other)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS confirmed_count,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status != 0 AND status != 1 THEN 1 ELSE 0 END) AS cancelled_count,
            COUNT(*) as total_count
        FROM tblappointment a
        {$where_clause}
    ");
    $stmt->execute($params);
    $status_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Appointments per month (over the filtered period)
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COUNT(*) as count 
        FROM tblappointment a
        {$where_clause}
        GROUP BY month 
        ORDER BY month ASC
    ");
    $stmt->execute($params);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Top doctors by appointments
    $stmt = $pdo->prepare("
        SELECT a.doctor, i.first_name, i.last_name, i.specialization, COUNT(*) as count
        FROM tblappointment a
        LEFT JOIN tblinfo i ON i.user_id = a.doctor
        {$where_clause}
        GROUP BY a.doctor
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $top_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Appointments by Specialization
    $stmt = $pdo->prepare("
        SELECT i.specialization, COUNT(a.id) as count
        FROM tblappointment a
        LEFT JOIN tblinfo i ON i.user_id = a.doctor
        {$where_clause}
        GROUP BY i.specialization
        ORDER BY count DESC
    ");
    $stmt->execute($params);
    $specialization_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Overall User statistics (Not date filtered, as it's overall system data)
    $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM tbluser GROUP BY user_type");
    $user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reports & Analytics - AppointmentEase</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">
  <div class="flex h-screen overflow-hidden">
    
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="flex-1 overflow-auto">
      <div class="p-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
            <p class="text-gray-500 mt-1">Key performance indicators and detailed system statistics.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-8">
            <form method="GET" class="flex flex-col md:flex-row items-center gap-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="calendar-range" class="text-gray-500" width="20"></i>
                    <span class="font-medium text-gray-700">Filter Period:</span>
                </div>
                
                <div class="flex-1 w-full md:w-auto flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label for="start_date" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="<?= e($start_date) ?>" 
                               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                    </div>
                    <div class="flex-1">
                        <label for="end_date" class="block text-xs font-semibold text-gray-500 uppercase mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="<?= e($end_date) ?>" 
                               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition">
                    </div>
                </div>

                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition">
                    <i data-lucide="bar-chart-3" width="18" class="inline mr-1"></i> Apply Filter
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="list-checks" class="text-blue-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Total Appts (Filtered)</h3>
                </div>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    <?= e($status_stats['total_count'] ?? 0) ?>
                </p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="check-circle-2" class="text-green-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Confirmed</h3>
                </div>
                <p class="text-3xl font-bold text-green-600 mt-2">
                    <?= e($status_stats['confirmed_count'] ?? 0) ?>
                </p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="clock-3" class="text-yellow-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Pending</h3>
                </div>
                <p class="text-3xl font-bold text-yellow-600 mt-2">
                    <?= e($status_stats['pending_count'] ?? 0) ?>
                </p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="stethoscope" class="text-indigo-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Total Doctors</h3>
                </div>
                <p class="text-3xl font-bold text-indigo-600 mt-2">
                    <?= e($user_stats['doctor'] ?? 0) ?>
                </p>
            </div>
             <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="users" class="text-purple-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Total Patients</h3>
                </div>
                <p class="text-3xl font-bold text-purple-600 mt-2">
                    <?= e($user_stats['user'] ?? 0) ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 relative h-96">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Appointment Trend (<?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?>)</h2>
                <div class="h-[calc(100%-3rem)]">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 relative h-96">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Top Doctors by Appointments</h2>
                <div class="h-[calc(100%-3rem)]">
                    <canvas id="doctorsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-1 relative h-96">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Appointment Status Breakdown</h2>
                <div class="h-[calc(100%-3rem)] flex items-center justify-center">
                    <canvas id="statusChart" class="max-h-full max-w-full"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 lg:col-span-2">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-bold text-gray-800">Specialization Performance</h2>
                    <p class="text-sm text-gray-500">Appointments distributed by the doctor's field (in the filtered period).</p>
                </div>
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Appointments</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if(empty($specialization_stats)): ?>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center text-gray-500">No appointments in this period.</td>
                            </tr>
                            <?php endif; ?>
                            <?php foreach ($specialization_stats as $spec): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                    <span class="inline-block w-2 h-2 rounded-full bg-purple-400 mr-2"></span>
                                    <?= e($spec['specialization'] ?: 'Unspecified') ?>
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-purple-600">
                                    <?= e($spec['count']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i data-lucide="download" width="20"></i> Export Reports</h2>
            <div class="flex flex-wrap gap-3">
                <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition">
                    Print / Save as PDF
                </button>
                <a href="generate_report.php?type=excel&start=<?= e($start_date) ?>&end=<?= e($end_date) ?>" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition">
                    Export Filtered Data to Excel (Requires `generate_report.php` logic)
                </a>
            </div>
        </div>

      </div>
    </main>
  </div>

  <script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // --- CHART DATA PREP ---
    const monthlyData = <?= json_encode($monthly_stats) ?>;
    const doctorsData = <?= json_encode($top_doctors) ?>;
    const statusStats = <?= json_encode($status_stats) ?>;
    
    // Monthly Appointments Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
                label: 'Appointments',
                data: monthlyData.map(d => d.count),
                borderColor: 'rgb(99, 102, 241)', // indigo-500
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            // Crucial setting: allow chart to use the height of the container
            maintainAspectRatio: false, 
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: { display: false },
                title: { display: false }
            }
        }
    });

    // Top Doctors Chart
    const doctorsCtx = document.getElementById('doctorsChart').getContext('2d');
    new Chart(doctorsCtx, {
        type: 'bar',
        data: {
            labels: doctorsData.map(d => (d.last_name || 'N/A') + ', ' + (d.first_name || 'N/A')),
            datasets: [{
                label: 'Appointments',
                data: doctorsData.map(d => d.count),
                backgroundColor: 'rgba(124, 58, 237, 0.8)', // purple-600
                borderColor: 'rgb(124, 58, 237)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y', // Makes it a horizontal bar chart
            // Crucial setting: allow chart to use the height of the container
            maintainAspectRatio: false, 
            scales: {
                x: { beginAtZero: true }
            },
            plugins: {
                legend: { display: false },
                title: { display: false }
            }
        }
    });
    
    // Appointment Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Confirmed/Completed', 'Pending', 'Cancelled/Other'],
            datasets: [{
                data: [
                    parseInt(statusStats.confirmed_count),
                    parseInt(statusStats.pending_count),
                    parseInt(statusStats.cancelled_count)
                ],
                backgroundColor: [
                    '#10B981', // Emerald-500 (Confirmed)
                    '#F59E0B', // Amber-500 (Pending)
                    '#EF4444', // Red-500 (Cancelled)
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            // This is maintained, but the fixed container height limits its size.
            maintainAspectRatio: true, 
            plugins: {
                legend: { position: 'bottom' },
                title: { display: false }
            }
        }
    });
  </script>
</body>
</html>