<?php
// includes/doctor_sidebar.php

// Helper to highlight active link
function getDocNavClass($targetPage) {
    $current = basename($_SERVER['PHP_SELF']);
    $active = "bg-green-50 text-green-700 border-r-4 border-green-600";
    $inactive = "text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all";
    return ($current === $targetPage) ? $active : $inactive;
}

// Fallback for doctor name if not set in parent
$sidebarDocName = $doc['last_name'] ?? $_SESSION['user_name'] ?? 'Doctor';
?>

<aside id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col z-40 hidden md:flex h-screen fixed md:relative transition-all duration-300">
    <div class="p-6 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center text-white shadow-sm shadow-green-200">
                <i data-lucide="activity" width="20"></i>
            </div>
            <div>
                <span class="font-bold text-lg text-slate-800 tracking-tight">AppointEase</span>
                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Doctor Portal</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 mt-2">Main</p>
        
        <a href="doctor_home.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium <?= getDocNavClass('doctor_home.php') ?>">
            <i data-lucide="layout-dashboard" width="18"></i>
            <span>Dashboard</span>
        </a>
        <a href="doctor_appointments.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium <?= getDocNavClass('doctor_appointments.php') ?>">
            <i data-lucide="calendar" width="18"></i>
            <span>Appointments</span>
        </a>
        <a href="patients.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium <?= getDocNavClass('patients.php') ?>">
            <i data-lucide="users" width="18"></i>
            <span>My Patients</span>
        </a>

        <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 mt-6">Management</p>
        
        <a href="doctor_schedule_manage.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium <?= getDocNavClass('doctor_schedule_manage.php') ?>">
            <i data-lucide="clock" width="18"></i>
            <span>Schedule</span>
        </a>
        <a href="doctor_records.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium <?= getDocNavClass('doctor_records.php') ?>">
            <i data-lucide="file-text" width="18"></i>
            <span>Medical Records</span>
        </a>
        
        <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 mt-6">Account</p>
        
        <a href="doctor_settings.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium <?= getDocNavClass('doctor_settings.php') ?>">
            <i data-lucide="settings" width="18"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-slate-100 bg-slate-50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-green-700 font-bold shadow-sm">
                <?= substr($sidebarDocName, 0, 1) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-700 truncate">Dr. <?= htmlspecialchars($sidebarDocName) ?></p>
                <a href="logout.php" class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1 hover:underline">
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</aside>