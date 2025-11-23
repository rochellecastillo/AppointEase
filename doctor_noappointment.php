<?php
// doctor_noappointment.php - Manage unavailable dates
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_type']) !== 'doctor') {
    header('Location: login.php');
    exit;
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$error = '';
$success = '';
$my_user_id = $_SESSION['user_id'];

// Handle adding unavailable date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_noappointment'])) {
    $date = $_POST['date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($date) || empty($reason)) {
        $error = "Please fill in all fields";
    } elseif ($date < date('Y-m-d')) {
        $error = "Cannot block past dates";
    } else {
        try {
            // Check if already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblnoappointment WHERE doctor_id = ? AND date = ?");
            $stmt->execute([$my_user_id, $date]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "This date is already blocked";
            } else {
                $stmt = $pdo->prepare("INSERT INTO tblnoappointment (doctor_id, date, reason) VALUES (?, ?, ?)");
                $stmt->execute([$my_user_id, $date, $reason]);
                $success = "Date blocked successfully";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tblnoappointment WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$id, $my_user_id]);
        $success = "Date unblocked successfully";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all blocked dates
try {
    $stmt = $pdo->prepare("SELECT * FROM tblnoappointment WHERE doctor_id = ? ORDER BY date DESC");
    $stmt->execute([$my_user_id]);
    $blocked_dates = $stmt->fetchAll();
} catch (Exception $e) {
    $blocked_dates = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unavailable Dates - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Unavailable Dates</h1>
                    <p class="text-gray-600">Block dates when you're not available for appointments</p>
                </div>
                <a href="doctor_home.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    ← Back to Dashboard
                </a>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Form -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Block New Date</h2>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Date</label>
                            <input type="date" name="date" required min="<?= date('Y-m-d') ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Reason</label>
                            <textarea name="reason" required rows="3"
                                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                      placeholder="e.g., Personal leave, Conference, etc."></textarea>
                        </div>

                        <button type="submit" name="add_noappointment"
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-semibold">
                            Block Date
                        </button>
                    </form>
                </div>

                <!-- Blocked Dates List -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-bold text-gray-800">Blocked Dates</h2>
                        <p class="text-sm text-gray-600 mt-1">Dates when you're unavailable for appointments</p>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($blocked_dates)): ?>
                            <p class="text-center text-gray-500 py-8">No blocked dates</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($blocked_dates as $block): 
                                    $isPast = $block['date'] < date('Y-m-d');
                                ?>
                                <div class="border rounded-lg p-4 <?= $isPast ? 'bg-gray-50 opacity-60' : '' ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <h3 class="font-bold text-gray-800">
                                                    <?= e(date('F j, Y', strtotime($block['date']))) ?>
                                                    <?= $isPast ? '(Past)' : '' ?>
                                                </h3>
                                            </div>
                                            <p class="text-sm text-gray-600"><?= e($block['reason']) ?></p>
                                        </div>
                                        
                                        <?php if (!$isPast): ?>
                                            <a href="?delete=<?= e($block['id']) ?>" 
                                               onclick="return confirm('Unblock this date?')"
                                               class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                                Remove
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>