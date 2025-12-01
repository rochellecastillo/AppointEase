<?php
include __DIR__ . '/controllers/reports_data.php';
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
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= e($status_stats['total_count'] ?? 0) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="check-circle-2" class="text-green-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Confirmed</h3>
                </div>
                <p class="text-3xl font-bold text-green-600 mt-2"><?= e($status_stats['confirmed_count'] ?? 0) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="check-square" class="text-blue-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Completed</h3>
                </div>
                <p class="text-3xl font-bold text-blue-600 mt-2"><?= e($status_stats['completed_count'] ?? 0) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="stethoscope" class="text-indigo-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Total Doctors</h3>
                </div>
                <p class="text-3xl font-bold text-indigo-600 mt-2"><?= e($user_stats['doctor'] ?? 0) ?></p>
            </div>
             <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <i data-lucide="users" class="text-purple-500 mb-2" width="24"></i>
                    <h3 class="text-gray-500 text-xs font-semibold uppercase">Total Patients</h3>
                </div>
                <p class="text-3xl font-bold text-purple-600 mt-2"><?= e($user_stats['user'] ?? 0) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 relative h-96">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Appointment Trend</h2>
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
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 relative h-96">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Appointment Status Breakdown</h2>
                <div class="h-[calc(100%-3rem)] flex items-center justify-center">
                    <canvas id="statusChart" class="max-h-full max-w-full"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 relative h-96">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Patient Demographics (Age Group)</h2>
                <div class="h-[calc(100%-3rem)] flex items-center justify-center">
                    <canvas id="ageChart" class="max-h-full max-w-full"></canvas>
                </div>
            </div>

        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 lg:col-span-3">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-800">Specialization Performance</h2>
                <p class="text-sm text-gray-500">Appointments distributed by the doctor's field (in the filtered period).</p>
            </div>
            <div class="p-6 overflow-x-auto">
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

        <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i data-lucide="download" width="20"></i> Export Reports</h2>
            <div class="flex flex-wrap gap-3">
                <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition">
                    Print / Save as PDF
                </button>
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
    const ageStats = <?= json_encode($age_stats) ?>; // NEW DATA
    
    // 1. Monthly Chart (Line)
    new Chart(document.getElementById('monthlyChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
                label: 'Appointments',
                data: monthlyData.map(d => d.count),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4, fill: true, pointRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });

    // 2. Top Doctors (Bar)
    new Chart(document.getElementById('doctorsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: doctorsData.map(d => (d.last_name || 'N/A') + ', ' + (d.first_name || 'N/A')),
            datasets: [{
                label: 'Appointments',
                data: doctorsData.map(d => d.count),
                backgroundColor: 'rgba(124, 58, 237, 0.8)',
                borderColor: 'rgb(124, 58, 237)', borderWidth: 1
            }]
        },
        options: { responsive: true, indexAxis: 'y', maintainAspectRatio: false, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
    
    // 3. Status (Doughnut)
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Confirmed', 'Pending', 'Completed', 'Cancelled'],
            datasets: [{
                data: [
                    parseInt(statusStats.confirmed_count),
                    parseInt(statusStats.pending_count),
                    parseInt(statusStats.completed_count),
                    parseInt(statusStats.cancelled_count)
                ],
                backgroundColor: [
                    '#10B981', // Green
                    '#F59E0B', // Orange
                    '#3B82F6', // Blue
                    '#EF4444'  // Red
                ],
                hoverOffset: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
    });

    // 4. Age Demographics (Pie) - NEW
    new Chart(document.getElementById('ageChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: ['Child (<18)', 'Adult (18-59)', 'Senior (60+)'],
            datasets: [{
                data: [
                    parseInt(ageStats.Child),
                    parseInt(ageStats.Adult),
                    parseInt(ageStats.Senior)
                ],
                backgroundColor: [
                    '#38bdf8', // Sky Blue (Child)
                    '#818cf8', // Indigo (Adult)
                    '#f472b6'  // Pink (Senior)
                ],
                hoverOffset: 4
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true, 
            plugins: { 
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? Math.round((value / total) * 100) + '%' : '0%';
                            return `${label}: ${value} (${percentage})`;
                        }
                    }
                }
            } 
        }
    });
  </script>
</body>
</html>