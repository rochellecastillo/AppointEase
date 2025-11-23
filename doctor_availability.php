<?php
// doctor_availability.php - Manage doctor availability/schedule
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'doctor') {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 1800) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}
$csrf = $_SESSION['csrf_token'];

$my_user_id = $_SESSION['user_id'];
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        $error = 'CSRF token validation failed';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_schedule') {
            $day = (int)($_POST['day'] ?? 0);
            $time = $_POST['time'] ?? '';
            $time2 = $_POST['time2'] ?? '';
            $max_appointment = (int)($_POST['max_appointment'] ?? 5);
            
            if ($day < 1 || $day > 7) {
                $error = 'Invalid day selected';
            } elseif (empty($time)) {
                $error = 'Start time is required';
            } else {
                try {
                    // Check if schedule already exists for this day and time
                    $stmt = $pdo->prepare("SELECT id FROM tblschedule WHERE user_id = ? AND day = ? AND time = ? LIMIT 1");
                    $stmt->execute([$my_user_id, $day, $time]);
                    
                    if ($stmt->fetch()) {
                        $error = 'A schedule already exists for this day and time';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO tblschedule (user_id, day, time, time2, max_appointment) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$my_user_id, $day, $time, $time2, $max_appointment]);
                        $message = 'Schedule added successfully';
                    }
                } catch (Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_schedule') {
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM tblschedule WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $my_user_id]);
                $message = 'Schedule deleted successfully';
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        } elseif ($action === 'update_schedule') {
            $id = (int)($_POST['id'] ?? 0);
            $max_appointment = (int)($_POST['max_appointment'] ?? 5);
            $time2 = $_POST['time2'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE tblschedule SET max_appointment = ?, time2 = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$max_appointment, $time2, $id, $my_user_id]);
                $message = 'Schedule updated successfully';
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch current schedules
$stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? ORDER BY day, time");
$stmt->execute([$my_user_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group schedules by day
$schedulesByDay = [
    1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => []
];
foreach ($schedules as $s) {
    $schedulesByDay[(int)$s['day']][] = $s;
}

$dayNames = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday'
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Availability - AppointmentEase</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gray-100">
  <div class="min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">My Availability</h1>
          <p class="text-gray-600">Manage your weekly schedule and availability</p>
        </div>
        <a href="doctor_home.php" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition">
          <i data-lucide="arrow-left" class="inline-block w-4 h-4"></i> Back to Dashboard
        </a>
      </div>

      <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
          <i data-lucide="check-circle" class="w-5 h-5"></i>
          <span><?= e($message) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-5 h-5"></i>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Schedule Form -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i data-lucide="plus-circle" class="w-6 h-6 text-green-600"></i>
              Add Schedule
            </h2>
            <form method="POST" class="space-y-4">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="add_schedule">
              
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Day of Week</label>
                <select name="day" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                  <option value="">Select Day</option>
                  <?php foreach ($dayNames as $num => $name): ?>
                    <option value="<?= $num ?>"><?= e($name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time</label>
                <input type="time" name="time" required 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End Time (Optional)</label>
                <input type="time" name="time2" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-500 mt-1">Leave empty for single time slot</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Max Appointments</label>
                <input type="number" name="max_appointment" value="5" min="1" max="50" required
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-500 mt-1">Maximum appointments per time slot</p>
              </div>

              <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition font-semibold">
                Add Schedule
              </button>
            </form>
          </div>

          <!-- Info Card -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
            <div class="flex items-start gap-2">
              <i data-lucide="info" class="text-blue-600 mt-1 w-5 h-5"></i>
              <div class="text-sm text-blue-700">
                <p class="font-semibold mb-2">Schedule Tips:</p>
                <ul class="space-y-1 text-xs">
                  <li>• Add multiple time slots per day if needed</li>
                  <li>• Set realistic max appointments</li>
                  <li>• Use end time for appointment ranges</li>
                  <li>• Update regularly for holidays</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Current Schedule Display -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
              <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i data-lucide="calendar" class="w-6 h-6 text-green-600"></i>
                Weekly Schedule
              </h2>
            </div>
            <div class="p-6">
              <?php if (empty($schedules)): ?>
                <div class="text-center py-12 text-gray-500">
                  <i data-lucide="calendar-x" class="inline-block w-16 h-16 mb-4 text-gray-400"></i>
                  <p class="text-lg">No schedules set</p>
                  <p class="text-sm">Add your availability to start accepting appointments</p>
                </div>
              <?php else: ?>
                <div class="space-y-6">
                  <?php foreach ($dayNames as $dayNum => $dayName): ?>
                    <?php if (!empty($schedulesByDay[$dayNum])): ?>
                      <div class="border rounded-lg p-4">
                        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                          <i data-lucide="calendar-days" class="w-5 h-5 text-green-600"></i>
                          <?= e($dayName) ?>
                        </h3>
                        <div class="space-y-2">
                          <?php foreach ($schedulesByDay[$dayNum] as $schedule): ?>
                            <div class="bg-gray-50 rounded p-3">
                              <div class="flex items-center justify-between">
                                <div class="flex-1">
                                  <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                      <i data-lucide="clock" class="w-4 h-4 text-gray-600"></i>
                                      <span class="font-semibold text-gray-800">
                                        <?= e(substr($schedule['time'], 0, 5)) ?>
                                        <?php if (!empty($schedule['time2']) && $schedule['time2'] !== '00:00:00'): ?>
                                          - <?= e(substr($schedule['time2'], 0, 5)) ?>
                                        <?php endif; ?>
                                      </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                      <i data-lucide="users" class="w-4 h-4 text-gray-600"></i>
                                      <span class="text-sm text-gray-600">Max: <?= e($schedule['max_appointment']) ?> patients</span>
                                    </div>
                                  </div>
                                  <div class="text-xs text-gray-500 mt-1">
                                    Schedule ID: <?= e($schedule['id']) ?>
                                  </div>
                                </div>
                                <div class="flex gap-2">
                                  <button onclick="editSchedule(<?= e($schedule['id']) ?>, <?= e($schedule['max_appointment']) ?>, '<?= e($schedule['time2']) ?>')" 
                                          class="text-blue-600 hover:text-blue-700 p-2">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                  </button>
                                  <form method="POST" class="inline" onsubmit="return confirm('Delete this schedule?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="id" value="<?= e($schedule['id']) ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-700 p-2">
                                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                  </form>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Schedule Modal -->
  <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full m-4">
      <div class="p-6 border-b flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Edit Schedule</h2>
        <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
          <i data-lucide="x" class="w-6 h-6"></i>
        </button>
      </div>
      <form method="POST" class="p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="update_schedule">
        <input type="hidden" name="id" id="edit_id">
        
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">End Time (Optional)</label>
          <input type="time" name="time2" id="edit_time2"
                 class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Max Appointments</label>
          <input type="number" name="max_appointment" id="edit_max_appointment" min="1" max="50" required
                 class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>

        <div class="flex gap-3">
          <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition">
            Update
          </button>
          <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg transition">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    if (typeof lucide !== 'undefined') lucide.replace();

    function editSchedule(id, maxAppointment, time2) {
      document.getElementById('edit_id').value = id;
      document.getElementById('edit_max_appointment').value = maxAppointment;
      document.getElementById('edit_time2').value = time2 && time2 !== '00:00:00' ? time2.substring(0, 5) : '';
      document.getElementById('editModal').classList.remove('hidden');
      if (typeof lucide !== 'undefined') lucide.replace();
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }

    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) closeEditModal();
    });

    // Auto-dismiss messages
    <?php if ($message): ?>
    setTimeout(() => {
      const msg = document.querySelector('.bg-green-50');
      if (msg) {
        msg.style.transition = 'opacity 0.5s';
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 500);
      }
    }, 5000);
    <?php endif; ?>
  </script>
</body>
</html>