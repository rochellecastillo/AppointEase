<?php
// download_reports.php - Download medical reports and appointment history
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Fetch patient info
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
$stmt->execute([$user_id]);
$patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all appointments
$stmt = $pdo->prepare("SELECT a.id, a.booking_date, a.status,
                              d.first_name AS dfirst, d.last_name AS dlast
                       FROM tblappointment a
                       LEFT JOIN tblinfo d ON d.user_id = a.doctor
                       WHERE a.user_id = ?
                       ORDER BY a.booking_date DESC");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CSV download
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="appointment_history_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, ['Appointment ID', 'Date', 'Doctor', 'Status']);
    
    foreach ($appointments as $apt) {
        $doctor = trim(($apt['dlast'] ?? '') . ', ' . ($apt['dfirst'] ?? ''));
        $statusText = ((int)$apt['status'] === 1) ? 'Confirmed' : (((int)$apt['status'] === 2) ? 'Pending' : 'Cancelled');
        fputcsv($output, [$apt['id'], $apt['booking_date'], $doctor, $statusText]);
    }
    
    fclose($output);
    exit;
}

// Statistics
$totalAppointments = count($appointments);
$confirmedCount = count(array_filter($appointments, fn($a) => (int)$a['status'] === 1));
$pendingCount = count(array_filter($appointments, fn($a) => (int)$a['status'] === 2));
$cancelledCount = count(array_filter($appointments, fn($a) => (int)$a['status'] === 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Reports - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Download Reports</h1>
                    <p class="text-gray-600 mt-1">Export your medical records and appointment history</p>
                </div>
                <a href="client_home.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-6 rounded-lg transition">
                    <i data-lucide="arrow-left" class="inline" width="18" height="18"></i>
                    Back
                </a>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Records</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?= $totalAppointments ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i data-lucide="file-text" class="text-blue-600" width="24" height="24"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Confirmed</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?= $confirmedCount ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i data-lucide="check-circle" class="text-green-600" width="24" height="24"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pending</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?= $pendingCount ?></p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i data-lucide="clock" class="text-yellow-600" width="24" height="24"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Cancelled</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?= $cancelledCount ?></p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i data-lucide="x-circle" class="text-red-600" width="24" height="24"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <!-- Appointment History -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <div class="bg-blue-100 p-3 rounded-full w-fit mb-4">
                            <i data-lucide="calendar" class="text-blue-600" width="32" height="32"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Appointment History</h3>
                        <p class="text-gray-600 text-sm mb-4">Complete record of all your appointments</p>
                        <div class="space-y-2">
                            <a href="?download=csv" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="download" class="inline" width="18" height="18"></i>
                                Download CSV
                            </a>
                            <button onclick="downloadPDF('appointments')" class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-700 py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="file-text" class="inline" width="18" height="18"></i>
                                Download PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Medical Records -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <div class="bg-green-100 p-3 rounded-full w-fit mb-4">
                            <i data-lucide="heart-pulse" class="text-green-600" width="32" height="32"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Medical Records</h3>
                        <p class="text-gray-600 text-sm mb-4">Your complete medical history and records</p>
                        <div class="space-y-2">
                            <button onclick="downloadPDF('medical')" class="block w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="download" class="inline" width="18" height="18"></i>
                                Download PDF
                            </button>
                            <button class="block w-full bg-green-100 hover:bg-green-200 text-green-700 py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="share" class="inline" width="18" height="18"></i>
                                Share with Doctor
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Patient Profile -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <div class="bg-purple-100 p-3 rounded-full w-fit mb-4">
                            <i data-lucide="user" class="text-purple-600" width="32" height="32"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Patient Profile</h3>
                        <p class="text-gray-600 text-sm mb-4">Personal information and health profile</p>
                        <div class="space-y-2">
                            <button onclick="downloadPDF('profile')" class="block w-full bg-purple-600 hover:bg-purple-700 text-white py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="download" class="inline" width="18" height="18"></i>
                                Download PDF
                            </button>
                            <button onclick="printProfile()" class="block w-full bg-purple-100 hover:bg-purple-200 text-purple-700 py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="printer" class="inline" width="18" height="18"></i>
                                Print Profile
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lab Results -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <div class="bg-orange-100 p-3 rounded-full w-fit mb-4">
                            <i data-lucide="flask-conical" class="text-orange-600" width="32" height="32"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Lab Results</h3>
                        <p class="text-gray-600 text-sm mb-4">Laboratory test results and reports</p>
                        <div class="space-y-2">
                            <button class="block w-full bg-orange-600 hover:bg-orange-700 text-white py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="download" class="inline" width="18" height="18"></i>
                                Download All
                            </button>
                            <button class="block w-full bg-orange-100 hover:bg-orange-200 text-orange-700 py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="eye" class="inline" width="18" height="18"></i>
                                View Results
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Prescriptions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <div class="bg-pink-100 p-3 rounded-full w-fit mb-4">
                            <i data-lucide="pill" class="text-pink-600" width="32" height="32"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Prescriptions</h3>
                        <p class="text-gray-600 text-sm mb-4">Medication prescriptions and history</p>
                        <div class="space-y-2">
                            <button class="block w-full bg-pink-600 hover:bg-pink-700 text-white py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="download" class="inline" width="18" height="18"></i>
                                Download All
                            </button>
                            <button class="block w-full bg-pink-100 hover:bg-pink-200 text-pink-700 py-3 px-4 rounded-lg text-center font-semibold transition">
                                <i data-lucide="list" class="inline" width="18" height="18"></i>
                                View List
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Complete Package -->
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg shadow text-white">
                    <div class="p-6">
                        <div class="bg-white/20 p-3 rounded-full w-fit mb-4">
                            <i data-lucide="package" width="32" height="32"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Complete Package</h3>
                        <p class="text-blue-100 text-sm mb-4">Download all your records in one package</p>
                        <button onclick="downloadComplete()" class="block w-full bg-white text-blue-600 hover:bg-blue-50 py-3 px-4 rounded-lg text-center font-semibold transition">
                            <i data-lucide="download" class="inline" width="18" height="18"></i>
                            Download Everything
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Downloads -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold text-gray-800">Recent Downloads</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition">
                            <div class="flex items-center gap-4">
                                <div class="bg-blue-100 p-2 rounded">
                                    <i data-lucide="file-text" class="text-blue-600" width="24" height="24"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Appointment History</p>
                                    <p class="text-sm text-gray-600">Downloaded on <?= date('M d, Y') ?></p>
                                </div>
                            </div>
                            <button class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                                Download Again
                            </button>
                        </div>

                        <div class="text-center py-8 text-gray-500">
                            <i data-lucide="inbox" class="mx-auto mb-3" width="48" height="48"></i>
                            <p>No recent downloads</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.replace();

        function downloadPDF(type) {
            alert(`PDF download for ${type} would be generated here.`);
            // In production, this would generate and download a PDF file
        }

        function printProfile() {
            alert('Print profile functionality would open print dialog here.');
            // window.print();
        }

        function downloadComplete() {
            if (confirm('This will download all your medical records in a ZIP file. Continue?')) {
                alert('Complete package download would be generated here.');
            }
        }
    </script>
</body>
</html>