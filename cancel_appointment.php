<?php
// cancel.php - Cancel Appointment Confirmation
ob_start();
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// 1. Validate Input
$appt_id = $_GET['id'] ?? null;
if (!$appt_id) {
    header('Location: client_home.php');
    exit;
}

// 2. Fetch Appointment Details (Security: AND user_id = ?)
// We confirm the appointment belongs to the logged-in user here
$stmt = $pdo->prepare("
    SELECT a.*, d.first_name, d.last_name, d.specialization 
    FROM tblappointment a
    JOIN tblinfo d ON a.doctor = d.user_id
    WHERE a.id = ? AND a.user_id = ?
    LIMIT 1
");
$stmt->execute([$appt_id, $user_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

// If appointment doesn't exist or doesn't belong to user
if (!$appointment) {
    // Log suspicious attempt if needed
    header('Location: client_home.php?error=not_found');
    exit;
}

// 3. Handle Cancellation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancel'])) {
    
    // Update status to 0 (Cancelled)
    $updateStmt = $pdo->prepare("UPDATE tblappointment SET status = 0 WHERE id = ?");
    
    if ($updateStmt->execute([$appt_id])) {
        header('Location: client_home.php?cancel=success');
        exit;
    } else {
        $error = "Failed to cancel appointment. Please try again.";
    }
}

$doctor_name = "Dr. " . e($appointment['first_name']) . " " . e($appointment['last_name']);
$date_formatted = date('l, F j, Y', strtotime($appointment['booking_date']));
$time_formatted = date('h:i A', strtotime($appointment['booking_time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/client_sidebar.php'; ?>

        <main class="flex-1 overflow-auto relative flex items-center justify-center p-4">
            
            <div class="md:hidden absolute top-0 left-0 w-full p-4 flex items-center justify-between bg-white border-b z-20">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden relative">
                
                <div class="h-32 bg-red-50 relative overflow-hidden flex items-center justify-center">
                    <div class="absolute w-64 h-64 bg-red-100 rounded-full -top-32 -left-10 opacity-50"></div>
                    <div class="absolute w-64 h-64 bg-red-100 rounded-full -bottom-32 -right-10 opacity-50"></div>
                    
                    <div class="relative z-10 w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm text-red-500">
                        <i data-lucide="alert-triangle" width="40" height="40"></i>
                    </div>
                </div>

                <div class="p-8 text-center">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Cancel Appointment?</h1>
                    <p class="text-gray-500 mb-8">Are you sure you want to cancel your upcoming visit? This action cannot be undone.</p>

                    <div class="bg-gray-50 rounded-2xl p-4 mb-8 text-left border border-gray-100">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-purple-600 shadow-sm">
                                <i data-lucide="calendar" width="24"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= $doctor_name ?></h3>
                                <p class="text-purple-600 font-medium text-sm"><?= e($appointment['specialization'] ?? 'General Practice') ?></p>
                                <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
                                    <span class="flex items-center gap-1"><i data-lucide="calendar-days" width="14"></i> <?= $date_formatted ?></span>
                                    <span class="flex items-center gap-1"><i data-lucide="clock" width="14"></i> <?= $time_formatted ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="flex flex-col sm:flex-row gap-3">
                        <a href="client_home.php" class="flex-1 py-3 px-4 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition flex items-center justify-center gap-2">
                            No, Keep It
                        </a>
                        <button type="submit" name="confirm_cancel" class="flex-1 py-3 px-4 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 shadow-lg shadow-red-200 transition flex items-center justify-center gap-2">
                            <i data-lucide="x-circle" width="18"></i> Yes, Cancel
                        </button>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if(sidebar) {
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
            }
        });
    </script>
</body>
</html>