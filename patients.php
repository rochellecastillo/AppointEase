<?php
// patients.php - Doctor's Patient List
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$doctor_id = session_get_user_id();

// --- 1. FETCH PATIENTS ---
$search = $_GET['search'] ?? '';

// Base Query: Get all unique patients who have booked with this doctor
$sql = "SELECT DISTINCT 
            i.user_id, 
            i.first_name, 
            i.last_name, 
            i.contact, 
            i.gender, 
            i.bdate, 
            i.address,
            i.image,
            MIN(a.booking_date) as first_visit,
            MAX(a.booking_date) as last_visit
        FROM tblappointment a
        JOIN tblinfo i ON a.user_id = i.user_id
        WHERE a.doctor = ?";

$params = [$doctor_id];

// Apply Filter
if ($search) {
    $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ? OR i.contact LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY i.user_id ORDER BY last_visit DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . e($e->getMessage()));
}

// --- 2. CALCULATE STATS ---
$total_patients = count($patients);

// Active = Visited in last 30 days
$active_patients = count(array_filter($patients, function($p) {
    return !empty($p['last_visit']) && strtotime($p['last_visit']) >= strtotime('-30 days');
}));

// New This Month = First visit was in current month/year
$new_this_month = count(array_filter($patients, function($p) {
    return !empty($p['first_visit']) && date('Y-m', strtotime($p['first_visit'])) === date('Y-m');
}));

// Helper for Age
function getAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Patients - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/doctor_sidebar.php'; ?>

        <main class="flex-1 overflow-auto w-full">
            <!-- Mobile Header -->
            <div class="md:hidden bg-white p-4 border-b flex justify-between items-center sticky top-0 z-30">
                <span class="font-bold text-lg text-slate-800">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-slate-100 rounded-lg"><i data-lucide="menu" width="20"></i></button>
            </div>

            <div class="p-6 md:p-8 max-w-7xl mx-auto">
                
                <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">My Patients</h1>
                        <p class="text-slate-500">Directory of patients under your care.</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center justify-between transition hover:shadow-md">
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Total Patients</p>
                            <p class="text-3xl font-bold text-slate-800 mt-2"><?= e($total_patients) ?></p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-xl">
                            <i data-lucide="users" class="text-blue-600" width="24" height="24"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center justify-between transition hover:shadow-md">
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Active (30 Days)</p>
                            <p class="text-3xl font-bold text-slate-800 mt-2"><?= e($active_patients) ?></p>
                        </div>
                        <div class="bg-green-50 p-3 rounded-xl">
                            <i data-lucide="activity" class="text-green-600" width="24" height="24"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center justify-between transition hover:shadow-md">
                        <div>
                            <p class="text-slate-500 text-sm font-medium">New This Month</p>
                            <p class="text-3xl font-bold text-slate-800 mt-2"><?= e($new_this_month) ?></p>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-xl">
                            <i data-lucide="user-plus" class="text-purple-600" width="24" height="24"></i>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-8">
                    <form method="GET" class="flex gap-3">
                        <div class="flex-1 relative">
                            <i data-lucide="search" class="absolute left-3 top-3 text-slate-400" width="20" height="20"></i>
                            <input type="text" name="search" value="<?= e($search) ?>" 
                                placeholder="Search by name or contact number..."
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-200 transition">
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-xl font-medium transition shadow-sm">
                            Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="patients.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-6 py-2.5 rounded-xl font-medium transition">
                                Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Patient Grid -->
                <?php if (empty($patients)): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 border-dashed p-12 text-center">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="users" class="text-slate-400" width="32"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-700">No patients found</h3>
                        <p class="text-slate-500 text-sm mt-1">Try adjusting your search criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($patients as $p): 
                            $fullName = e($p['first_name'] . ' ' . $p['last_name']);
                            $age = getAge($p['bdate']);
                            $lastVisit = $p['last_visit'] ? date('M d, Y', strtotime($p['last_visit'])) : 'Never';
                            
                            // Avatar Logic
                            $avatar = !empty($p['image']) ? 'uploads/' . e($p['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=random';
                        ?>
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition group relative overflow-hidden">
                            
                            <div class="flex items-start gap-4">
                                <img src="<?= $avatar ?>" alt="<?= $fullName ?>" class="w-16 h-16 rounded-full object-cover border-2 border-slate-100 shadow-sm">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-bold text-slate-800 truncate"><?= $fullName ?></h3>
                                    <p class="text-sm text-slate-500 flex items-center gap-2">
                                        <span><?= $age ?> yrs</span> &bull; <span class="capitalize"><?= e($p['gender']) ?></span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">Last Visit: <?= $lastVisit ?></p>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col gap-2">
                                <div class="text-sm text-slate-600 flex items-center gap-2 truncate">
                                    <i data-lucide="phone" width="14" class="text-slate-400"></i> <?= e($p['contact']) ?>
                                </div>
                                <div class="text-sm text-slate-600 flex items-center gap-2 truncate">
                                    <i data-lucide="map-pin" width="14" class="text-slate-400"></i> <?= e($p['address']) ?>
                                </div>
                            </div>

                            <!-- Actions Overlay -->
                            <div class="mt-4 flex gap-2">
                                <a href="doctor_records.php?patient_id=<?= $p['user_id'] ?>" class="flex-1 py-2.5 bg-slate-50 text-slate-700 font-medium text-sm rounded-xl hover:bg-slate-100 text-center transition border border-slate-200">
                                    History
                                </a>
                                <a href="doctor_view.php?patient_id=<?= $p['user_id'] ?>&action=new" class="flex-1 py-2.5 bg-green-600 text-white font-medium text-sm rounded-xl hover:bg-green-700 text-center transition shadow-sm shadow-green-200">
                                    View Profile
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
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-full'); 
            });
        }
    </script>
</body>
</html>