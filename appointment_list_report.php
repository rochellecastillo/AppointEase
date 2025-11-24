<?php
// appointment_list_report.php - Enhanced Admin Appointment Manager
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

// Require admin authentication
session_require_auth(['admin']);

$adminName = session_get_username();
$success = '';
$error = '';

// --- HANDLE ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Status
    if (isset($_POST['update_status'])) {
        $apt_id = (int)$_POST['apt_id'];
        $new_status = (int)$_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE tblappointment SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $apt_id]);
            $success = "Appointment #$apt_id status updated successfully!";
        } catch (Exception $ex) {
            $error = "Error: " . $ex->getMessage();
        }
    }

    // Delete Appointment
    if (isset($_POST['delete_appointment'])) {
        $del_id = (int)$_POST['apt_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tblappointment WHERE id = ?");
            $stmt->execute([$del_id]);
            $success = "Appointment #$del_id deleted successfully.";
        } catch (Exception $ex) {
            $error = "Error: " . $ex->getMessage();
        }
    }
}

// --- HANDLE FILTERS (GET) ---
$filter_search = $_GET['search'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? 'all';

// Build Query
$sql = "SELECT a.id, a.booking_date, a.booking_time, a.status, a.user_id,
               p.first_name AS pfirst, p.last_name AS plast, p.contact, p.image,
               d.first_name AS dfirst, d.last_name AS dlast, d.specialization
        FROM tblappointment a
        LEFT JOIN tblinfo p ON p.user_id = a.user_id
        LEFT JOIN tblinfo d ON d.user_id = a.doctor
        WHERE 1=1";

$params = [];

if (!empty($filter_search)) {
    $sql .= " AND (p.last_name LIKE ? OR p.first_name LIKE ? OR a.id = ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $params[] = $filter_search;
}

if (!empty($filter_date)) {
    $sql .= " AND a.booking_date = ?";
    $params[] = $filter_date;
}

if ($filter_status !== 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY a.booking_date DESC, a.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Appointments - AppointEase</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }
    .sidebar-hidden .sidebar-label { display: none; }
    .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
  </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800">
  <div class="flex h-screen overflow-hidden">
    
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="flex-1 overflow-auto">
      <div class="p-8">

        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Appointments List</h1>
                <p class="text-gray-500 mt-1">Manage patient bookings and statuses.</p>
            </div>
            <div class="flex gap-3">
                <a href="admin_home.php" class="text-gray-500 hover:text-purple-600 flex items-center gap-2 transition">
                    <i data-lucide="arrow-left" width="18"></i> Back
                </a>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-xl flex items-center gap-2">
            <i data-lucide="check-circle" width="20"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-xl flex items-center gap-2">
            <i data-lucide="alert-circle" width="20"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                
                <div class="col-span-1 md:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Search Patient</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-3 text-gray-400" width="18"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" 
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500"
                               placeholder="Name or ID...">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter by Date</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" 
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter by Status</label>
                    <select name="status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 appearance-none">
                        <option value="all">All Statuses</option>
                        <option value="2" <?= $filter_status === '2' ? 'selected' : '' ?>>Pending</option>
                        <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="3" <?= $filter_status === '3' ? 'selected' : '' ?>>Completed</option>
                        <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition shadow-md flex-1 font-medium">
                        Filter
                    </button>
                    <a href="appointment_list_report.php" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition border border-gray-200" title="Reset">
                        <i data-lucide="rotate-ccw" width="18"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Patient Info</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Assigned Doctor</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if(empty($appointments)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="calendar-x" width="32" class="text-gray-300"></i>
                                        <p>No appointments found matching your filters.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: foreach($appointments as $apt): 
                            $patientName = $apt['pfirst'] ? trim($apt['plast'] . ', ' . $apt['pfirst']) : 'Guest User';
                            $doctorName = $apt['dfirst'] ? trim('Dr. ' . $apt['dlast'] . ' ' . $apt['dfirst']) : 'Unassigned';
                            
                            // Updated Status Logic
                            $status = (int)$apt['status'];
                            switch ($status) {
                                case 1: 
                                    $statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200 inline-flex items-center gap-1"><i data-lucide="check-circle" width="12"></i> Confirmed</span>';
                                    break;
                                case 2: 
                                    $statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200 inline-flex items-center gap-1"><i data-lucide="clock" width="12"></i> Pending</span>';
                                    break;
                                case 3: 
                                    $statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200 inline-flex items-center gap-1"><i data-lucide="check-circle-2" width="12"></i> Completed</span>';
                                    break;
                                case 0: 
                                    $statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 inline-flex items-center gap-1"><i data-lucide="x-circle" width="12"></i> Cancelled</span>';
                                    break;
                                default:
                                    $statusBadge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700">Unknown</span>';
                            }
                        ?>
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-100 to-blue-100 text-purple-600 flex items-center justify-center font-bold text-sm">
                                        <?= strtoupper(substr($patientName, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($patientName) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($apt['contact'] ?: 'No Contact') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-5">
                                <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($doctorName) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($apt['specialization'] ?? 'General') ?></p>
                            </td>
                            <td class="p-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-700"><?= date('M d, Y', strtotime($apt['booking_date'])) ?></span>
                                    <span class="text-xs text-gray-400"><?= $apt['booking_time'] ? date('h:i A', strtotime($apt['booking_time'])) : '--:--' ?></span>
                                </div>
                            </td>
                            <td class="p-5 text-center">
                                <?= $statusBadge ?>
                            </td>
                            <td class="p-5 text-center">
                                <div class="flex items-center justify-center gap-2 opacity-100">
                                    
                                    <?php if($status == 2 || $status == 0): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="apt_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="status" value="1">
                                        <button type="submit" name="update_status" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition border border-green-100" title="Confirm">
                                            <i data-lucide="check" width="16"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if($status == 2 || $status == 1): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="apt_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="status" value="0">
                                        <button type="submit" name="update_status" class="p-2 bg-yellow-50 text-yellow-600 rounded-lg hover:bg-yellow-100 transition border border-yellow-100" title="Cancel">
                                            <i data-lucide="x" width="16"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this record?');">
                                        <input type="hidden" name="apt_id" value="<?= $apt['id'] ?>">
                                        <button type="submit" name="delete_appointment" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition border border-red-100" title="Delete">
                                            <i data-lucide="trash-2" width="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

      </div>
    </main>
  </div>

  <script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
  </script>
</body>
</html>