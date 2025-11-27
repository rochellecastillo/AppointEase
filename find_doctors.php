<?php
// find_doctors.php - Doctor Directory for Patients
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);

// --- 1. FETCH DATA ---

// Get Specializations for Filter
$specializations = $pdo->query("SELECT DISTINCT specialization FROM tblspecialization ORDER BY specialization ASC")->fetchAll(PDO::FETCH_COLUMN);

// Search Logic
$search = $_GET['q'] ?? '';
$filter_spec = $_GET['specialization'] ?? '';

$sql = "SELECT u.user_id, i.first_name, i.last_name, i.specialization, i.image, i.gender 
        FROM tbluser u 
        JOIN tblinfo i ON u.user_id = i.user_id 
        WHERE u.user_type = 'doctor' AND u.status = 1";

$params = [];

if ($search) {
    $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_spec) {
    $sql .= " AND i.specialization = ?";
    $params[] = $filter_spec;
}

$sql .= " ORDER BY i.last_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2. HELPER: GET AVAILABLE DAYS (FIXED) ---
function getDoctorDays($pdo, $doctor_id) {
    $stmt = $pdo->prepare("SELECT day FROM tblschedule WHERE user_id = ? ORDER BY day ASC");
    $stmt->execute([$doctor_id]);
    $days = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($days)) return 'Contact for schedule';
    
    // FIXED: Extended map to handle '7' as Sunday just in case
    $dayMap = [
        0 => 'Sun', 
        1 => 'Mon', 
        2 => 'Tue', 
        3 => 'Wed', 
        4 => 'Thu', 
        5 => 'Fri', 
        6 => 'Sat',
        7 => 'Sun' // Added this to fix "Undefined array key 7"
    ];

    $shortDays = [];
    foreach ($days as $d) {
        // Safety check: Does the key exist?
        if (isset($dayMap[$d])) {
            $shortDays[] = $dayMap[$d];
        }
    }
    
    // Unique removes duplicates (e.g., if both 0 and 7 exist)
    return implode(', ', array_unique($shortDays));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .doctor-card:hover { transform: translateY(-4px); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/client_sidebar.php'; ?>

        <main class="flex-1 overflow-auto">
            <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="p-6 max-w-7xl mx-auto">
                
                <div class="bg-gradient-to-r from-purple-600 to-indigo-700 rounded-3xl p-8 text-white mb-8 shadow-lg shadow-purple-200">
                    <div class="max-w-3xl">
                        <h1 class="text-3xl font-bold mb-2">Find Your Specialist</h1>
                        <p class="text-purple-100 mb-6">Search through our network of qualified doctors and book your appointment instantly.</p>
                        
                        <form method="GET" class="bg-white p-2 rounded-2xl flex flex-col md:flex-row gap-2 shadow-lg">
                            <div class="flex-1 relative">
                                <i data-lucide="search" class="absolute left-4 top-3.5 text-gray-400 w-5 h-5"></i>
                                <input type="text" name="q" value="<?= e($search) ?>" 
                                       placeholder="Search doctor name..." 
                                       class="w-full pl-12 pr-4 py-3 bg-transparent text-gray-800 focus:outline-none placeholder-gray-400">
                            </div>
                            <div class="h-px md:h-auto md:w-px bg-gray-200 mx-2"></div>
                            <div class="flex-1 relative">
                                <i data-lucide="stethoscope" class="absolute left-4 top-3.5 text-gray-400 w-5 h-5"></i>
                                <select name="specialization" class="w-full pl-12 pr-4 py-3 bg-transparent text-gray-800 focus:outline-none appearance-none cursor-pointer">
                                    <option value="">All Specializations</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?= e($spec) ?>" <?= $filter_spec == $spec ? 'selected' : '' ?>><?= e($spec) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i data-lucide="chevron-down" class="absolute right-4 top-3.5 text-gray-400 w-4 h-4 pointer-events-none"></i>
                            </div>
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-xl font-bold transition shadow-md">
                                Search
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (empty($doctors)): ?>
                    <div class="text-center py-20">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="user-search" class="w-10 h-10 text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-700">No doctors found</h3>
                        <p class="text-gray-500 mt-2">Try adjusting your search or filters.</p>
                        <a href="find_doctors.php" class="inline-block mt-4 text-purple-600 font-medium hover:underline">Clear all filters</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php foreach ($doctors as $doc): 
                            $fullName = "Dr. " . e($doc['first_name']) . " " . e($doc['last_name']);
                            $spec = e($doc['specialization']) ?: 'General Practitioner';
                            $availDays = getDoctorDays($pdo, $doc['user_id']);
                            
                            // Default Avatar
                            $defaultImg = (strtolower($doc['gender'] ?? '') === 'female') 
                                ? 'https://cdn-icons-png.flaticon.com/512/3304/3304567.png' 
                                : 'https://cdn-icons-png.flaticon.com/512/3774/3774299.png';
                            
                            $imgSrc = !empty($doc['image']) ? 'uploads/' . e($doc['image']) : $defaultImg;
                        ?>
                        <div class="doctor-card bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col">
                            <div class="p-6 flex flex-col items-center text-center border-b border-gray-50">
                                <div class="w-24 h-24 rounded-full p-1 border-2 border-purple-100 mb-4 relative">
                                    <img src="<?= $imgSrc ?>" alt="<?= $fullName ?>" class="w-full h-full rounded-full object-cover">
                                    <span class="absolute bottom-1 right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full" title="Active"></span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800 mb-1"><?= $fullName ?></h3>
                                <span class="px-3 py-1 bg-purple-50 text-purple-600 text-xs font-semibold rounded-full">
                                    <?= $spec ?>
                                </span>
                            </div>

                            <div class="p-6 pt-4 flex-1">
                                <div class="flex items-start gap-3 mb-3 text-sm text-gray-600">
                                    <i data-lucide="calendar-days" class="w-4 h-4 mt-0.5 text-purple-400 shrink-0"></i>
                                    <div>
                                        <p class="text-xs font-bold text-gray-400 uppercase">Availability</p>
                                        <p><?= $availDays ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-b-2xl flex gap-2">
                                <a href="book_appointment.php?doctor_id=<?= e($doc['user_id']) ?>" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-xl font-semibold text-sm text-center transition shadow-sm flex items-center justify-center gap-2">
                                    <i data-lucide="calendar-plus" class="w-4 h-4"></i> Book Now
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
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