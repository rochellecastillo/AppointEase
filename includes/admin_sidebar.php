<?php
// Get the current page name (e.g., 'admin_home.php')
$current_page = basename($_SERVER['PHP_SELF']);

// Helper to set active classes
function getNavClass($targetPage, $current) {
    $active = "bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg";
    $inactive = "text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition";
    
    // Check if the current page matches the target
    return ($targetPage === $current) ? $active : $inactive;
}

// Get user info safely
$sidebarName = $_SESSION['username'] ?? 'Admin';
$sidebarRole = 'Administrator';
?>

<aside id="sidebar" class="w-72 bg-white shadow-2xl transition-all duration-300 flex flex-col border-r border-gray-200 h-screen fixed md:relative z-50">
  <div class="p-6 border-b border-gray-200">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center">
          <i data-lucide="activity" class="text-white" width="20" height="20"></i>
        </div>
        <div>
          <h2 class="font-bold text-lg text-gray-800">AppointEase</h2>
          <p class="text-xs text-gray-500">Untalan Gen. Hospital</p>
        </div>
      </div>
      <button id="toggleSidebar" class="md:hidden p-2 hover:bg-gray-100 rounded-lg transition">
        <i data-lucide="x" class="text-gray-600" width="20" height="20"></i>
      </button>
    </div>
  </div>

  <nav class="flex-1 p-4 overflow-y-auto space-y-1">
    
    <a href="admin_home.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('admin_home.php', $current_page) ?>">
      <i data-lucide="layout-dashboard" width="20" height="20"></i>
      <span class="sidebar-label font-medium">Dashboard</span>
    </a>

    <a href="appointment_list_report.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('appointment_list_report.php', $current_page) ?>">
      <i data-lucide="calendar-check" width="20" height="20"></i>
      <span class="sidebar-label">Appointments</span>
    </a>

    <a href="doctors_info_report.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('doctors_info_report.php', $current_page) ?>">
      <i data-lucide="stethoscope" width="20" height="20"></i>
      <span class="sidebar-label">Doctors</span>
    </a>

    <a href="users_list.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('users_list.php', $current_page) ?>">
      <i data-lucide="users-round" width="20" height="20"></i>
      <span class="sidebar-label">Patients</span>
    </a>

    <a href="admin_medical_records.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('admin_medical_records.php', $current_page) ?>">
      <i data-lucide="clipboard-list" width="20" height="20"></i>
      <span class="sidebar-label">Medical Records</span>
    </a>

    <a href="schedule_manage.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('doctor_schedule_manage.php', $current_page) ?>">
      <i data-lucide="calendar-clock" width="20" height="20"></i>
      <span class="sidebar-label">Schedules</span>
    </a>

    <a href="reports.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('reports.php', $current_page) ?>">
      <i data-lucide="file-bar-chart" width="20" height="20"></i>
      <span class="sidebar-label">Reports</span>
    </a>

    <a href="activity_log.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('activity_log.php', $current_page) ?>">
      <i data-lucide="history" width="20" height="20"></i>
      <span class="sidebar-label">Activity Log</span>
    </a>

    <a href="settings.php" class="nav-link w-full flex items-center gap-3 p-3 rounded-xl <?= getNavClass('settings.php', $current_page) ?>">
      <i data-lucide="settings" width="20" height="20"></i>
      <span class="sidebar-label">Settings</span>
    </a>

  </nav>

  <div class="p-4 border-t border-gray-200">
    <div class="mb-3 p-4 bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl border border-purple-100">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
          <?= strtoupper(substr($sidebarName, 0, 1)) ?>
        </div>
        <div class="flex-1 overflow-hidden">
          <p class="text-xs text-gray-500">Logged in as</p>
          <p class="font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($sidebarName) ?>">
            <?= htmlspecialchars($sidebarName) ?>
          </p>
        </div>
      </div>
    </div>

    <a href="logout.php" class="w-full inline-flex items-center justify-center gap-2 p-3 text-red-600 rounded-xl hover:bg-red-50 transition font-medium">
      <i data-lucide="log-out" width="18" height="18"></i>
      <span class="sidebar-label">Logout</span>
    </a>
  </div>
</aside>

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

<script>
  const overlay = document.getElementById('sidebarOverlay');
  const toggleBtn = document.getElementById('toggleSidebar'); 
</script>