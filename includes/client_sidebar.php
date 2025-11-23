<?php
// includes/client_sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);

function getClientNavClass($targetPage, $current) {
    $active = "bg-purple-600 text-white shadow-lg shadow-purple-200";
    $inactive = "text-gray-600 hover:bg-purple-50 hover:text-purple-700 transition-all duration-200";
    return ($targetPage === $current) ? $active : $inactive;
}

// Fallback for user info if not set in parent
$sidebarName = $currentUser['name'] ?? 'Patient';
?>

<aside id="sidebar" class="w-72 bg-white h-screen fixed md:relative z-40 border-r border-gray-100 flex flex-col transition-all duration-300">
    <div class="p-8 flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-200">
            <i data-lucide="heart-pulse" class="text-white" width="22" height="22"></i>
        </div>
        <div>
            <h1 class="font-bold text-xl text-gray-800 tracking-tight">AppointEase</h1>
            <p class="text-xs text-gray-500 font-medium">Patient Portal</p>
        </div>
    </div>

    <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-4">Main</p>
        
        <a href="client_home.php" class="flex items-center gap-3 px-4 py-3.5 rounded-xl font-medium transition-all <?= getClientNavClass('client_home.php', $current_page) ?>">
            <i data-lucide="layout-grid" width="20" height="20"></i>
            <span>Dashboard</span>
        </a>

        <a href="client_appointments.php" class="flex items-center gap-3 px-4 py-3.5 rounded-xl font-medium transition-all <?= getClientNavClass('client_appointments.php', $current_page) ?>">
            <i data-lucide="calendar" width="20" height="20"></i>
            <span>My Appointments</span>
        </a>

        <a href="find_doctors.php" class="flex items-center gap-3 px-4 py-3.5 rounded-xl font-medium transition-all <?= getClientNavClass('find_doctors.php', $current_page) ?>">
            <i data-lucide="stethoscope" width="20" height="20"></i>
            <span>Find Doctors</span>
        </a>

        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-6">Records</p>

        <a href="medical_records.php" class="flex items-center gap-3 px-4 py-3.5 rounded-xl font-medium transition-all <?= getClientNavClass('medical_records.php', $current_page) ?>">
            <i data-lucide="file-text" width="20" height="20"></i>
            <span>Medical Records</span>
        </a>

        <a href="health_history.php" class="flex items-center gap-3 px-4 py-3.5 rounded-xl font-medium transition-all <?= getClientNavClass('health_history.php', $current_page) ?>">
            <i data-lucide="history" width="20" height="20"></i>
            <span>History</span>
        </a>

        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-6">Account</p>

        <a href="client_settings.php" class="flex items-center gap-3 px-4 py-3.5 rounded-xl font-medium transition-all <?= getClientNavClass('client_settings.php', $current_page) ?>">
            <i data-lucide="settings" width="20" height="20"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="p-4 m-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl border border-purple-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-purple-600 font-bold shadow-sm">
                <?= substr($sidebarName, 0, 1) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($sidebarName) ?></p>
                <a href="logout.php" class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
                    Sign Out 
                </a>
            </div>
        </div>
    </div>
</aside>