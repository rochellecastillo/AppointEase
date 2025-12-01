<?php
// schedule_manage.php - Visual Calendar Manager
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['admin', 'doctor']);

$user_id = session_get_user_id();
$user_type = session_get_user_type();

// --- 1. DETERMINE TARGET DOCTOR ---
// Admin can see all, Doctor sees only themselves
$doctors = [];
if ($user_type === 'admin') {
    $doctors = $pdo->query("SELECT u.user_id, i.first_name, i.last_name FROM tbluser u JOIN tblinfo i ON u.user_id = i.user_id WHERE u.user_type='doctor'")->fetchAll();
    
    // FIX: Check for 'doctor_id' (matches link) OR 'doctor_filter'
    $target_doctor = $_GET['doctor_id'] ?? $_GET['doctor_filter'] ?? ($doctors[0]['user_id'] ?? '');
} else {
    $target_doctor = $user_id;
}

// --- API: AJAX HANDLER FOR CALENDAR DATA ---
if (isset($_GET['action']) && $_GET['action'] === 'get_calendar_data') {
    ob_end_clean();
    header('Content-Type: application/json');

    try {
        // Use the ID passed to the API, or fall back to the context
        $api_target = ($user_type === 'admin') ? ($_GET['target_id'] ?? $target_doctor) : $user_id;

        if (!$api_target) throw new Exception("No doctor ID selected.");

        // 1. Get Weekly Roster
        $roster_stmt = $pdo->prepare("SELECT day, time, time2, max_appointment FROM tblschedule WHERE user_id = ?");
        $roster_stmt->execute([$api_target]);
        
        // 2. Get Blocked Dates
        $leaves_stmt = $pdo->prepare("SELECT id, date_start, date_end, reason FROM tblnoappointment WHERE doctor_id = ?");
        $leaves_stmt->execute([$api_target]);
        
        echo json_encode([
            'status' => 'success',
            'roster' => $roster_stmt->fetchAll(PDO::FETCH_ASSOC),
            'leaves' => $leaves_stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

ob_end_flush();

// --- HANDLE FORM SUBMISSIONS ---
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $todayStr = date('Y-m-d');

    // Admin might submit form for a different doctor than the one in URL? 
    // Usually safest to use the hidden field ID or the context ID.
    $form_doctor_id = ($user_type === 'admin') ? ($_POST['doctor_id'] ?? $target_doctor) : $user_id;

    if ($form_doctor_id) {
        // 1. BLOCK DATE
        if (isset($_POST['block_date'])) {
            $date = $_POST['date_start'];
            $reason = $_POST['reason'] ?? 'Unavailable';
            
            if ($date < $todayStr) {
                $message = "Error: You cannot block dates in the past.";
                $msg_type = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO tblnoappointment (doctor_id, date_start, date_end, reason) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$form_doctor_id, $date, $date, $reason]);
                    $message = "Date blocked successfully.";
                    $msg_type = 'success';
                } catch (Exception $e) { 
                    $message = "DB Error: " . $e->getMessage(); 
                    $msg_type = 'error'; 
                }
            }
        }

        // 2. UNBLOCK DATE
        if (isset($_POST['unblock_date'])) {
            $date = $_POST['date_to_unblock'];
            try {
                $stmt = $pdo->prepare("DELETE FROM tblnoappointment WHERE doctor_id = ? AND date_start = ?");
                $stmt->execute([$form_doctor_id, $date]);
                $message = "Date unblocked successfully.";
                $msg_type = 'success';
            } catch (Exception $e) {
                $message = "DB Error: " . $e->getMessage();
                $msg_type = 'error';
            }
        }

        // 3. UPDATE WEEKLY ROSTER
        if (isset($_POST['update_roster'])) {
            $day = $_POST['day_index']; 
            $time_start = $_POST['time_start'];
            $time_end = $_POST['time_end'];
            $max = $_POST['max_appointment']; // This now receives the calculated value from JS
            $is_active = isset($_POST['is_active']);

            try {
                // Clear existing roster for this day index first
                $pdo->prepare("DELETE FROM tblschedule WHERE user_id = ? AND day = ?")->execute([$form_doctor_id, $day]);

                if ($is_active && $time_start && $time_end) {
                    // Ensure max appointment is an integer >= 1
                    $max = max(1, (int)$max); 
                    $stmt = $pdo->prepare("INSERT INTO tblschedule (user_id, day, time, time2, max_appointment) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$form_doctor_id, $day, $time_start, $time_end, $max]);
                    $message = "Weekly schedule updated.";
                } else {
                    $message = "Weekly schedule removed for this day.";
                }
                $msg_type = 'success';
            } catch (Exception $e) { 
                $message = "DB Error: " . $e->getMessage(); 
                $msg_type = 'error'; 
            }
        }
    } else {
        $message = "Error: Doctor ID missing.";
        $msg_type = 'error';
    }
}
?>