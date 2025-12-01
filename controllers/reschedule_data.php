<?php
// reschedule.php - Reschedule Existing Appointment
ob_start();
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// =============================================================================
// 1. API: HANDLE AJAX REQUESTS
// =============================================================================
if (isset($_GET['action'])) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $appt_id = $_GET['id'] ?? '';
        if (!$appt_id) throw new Exception("Missing Appointment ID");

        $stmt = $pdo->prepare("SELECT doctor FROM tblappointment WHERE id = ? AND user_id = ?");
        $stmt->execute([$appt_id, $user_id]);
        $appt_check = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appt_check) throw new Exception("Appointment not found.");
        
        $doctor_id = $appt_check['doctor'];

        // --- Action: Get Monthly Status ---
        if ($_GET['action'] === 'get_monthly_status') {
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');

            $stmt = $pdo->prepare("SELECT day FROM tblschedule WHERE user_id = ?");
            $stmt->execute([$doctor_id]);
            $working_days_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $working_days = array_map('intval', $working_days_raw);

            $start_date = sprintf('%04d-%02d-01', (int)$year, (int)$month);
            $end_date = date("Y-m-t", strtotime($start_date));
            
            $stmt = $pdo->prepare("SELECT date_start, date_end, reason FROM tblnoappointment WHERE doctor_id = ? AND NOT (date_end < ? OR date_start > ?)");
            $stmt->execute([$doctor_id, $start_date, $end_date]);
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'working_days' => $working_days, 'leaves' => $leaves]);
            exit;
        }

        // --- Action: Get Time Slots ---
        if ($_GET['action'] === 'get_slots') {
            $date = $_GET['date'] ?? '';
            if (!$date) throw new Exception("Date required");

            if ($date <= date('Y-m-d')) {
                echo json_encode(['status' => 'off', 'message' => 'Cannot reschedule to today or past dates.']);
                exit;
            }

            $day_of_week = date('w', strtotime($date)); 
            $db_day = ($day_of_week == 0) ? 7 : $day_of_week; 
            
            $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND (day = ? OR day = ?)");
            $stmt->execute([$doctor_id, $day_of_week, $db_day]); 
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                echo json_encode(['status' => 'off', 'message' => 'Doctor is not working on this day.']);
                exit;
            }

            $check_leave = $pdo->prepare("SELECT 1 FROM tblnoappointment WHERE doctor_id = ? AND ? BETWEEN date_start AND date_end LIMIT 1");
            $check_leave->execute([$doctor_id, $date]);
            
            if ($check_leave->rowCount() > 0) {
                echo json_encode(['status' => 'leave', 'message' => 'Doctor is on leave.']);
                exit;
            }

            $booked_stmt = $pdo->prepare("SELECT booking_time FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0 AND id != ?");
            $booked_stmt->execute([$doctor_id, $date, $appt_id]);
            $booked_times = $booked_stmt->fetchAll(PDO::FETCH_COLUMN);

            $start = strtotime($schedule['time']);
            $end = strtotime($schedule['time2']);
            $step = 30 * 60;
            $slots = [];

            for ($i = $start; $i < $end; $i += $step) {
                $timeVal = date('H:i:s', $i);
                $timeDisp = date('h:i A', $i);
                $slots[] = ['time' => $timeVal, 'display' => $timeDisp, 'available' => !in_array($timeVal, $booked_times)];
            }

            echo json_encode(['status' => 'success', 'slots' => $slots]);
            exit;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// =============================================================================
// 2. PAGE LOGIC
// =============================================================================

$appt_id = $_GET['id'] ?? '';
if (!$appt_id) {
    header('Location: client_appointments.php');
    exit;
}

$stmt = $pdo->prepare("SELECT a.*, i.first_name, i.last_name, i.specialization FROM tblappointment a JOIN tblinfo i ON a.doctor = i.user_id WHERE a.id = ? AND a.user_id = ?");
$stmt->execute([$appt_id, $user_id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) die("Appointment not found or access denied.");

$doctor_id = $appt['doctor'];
$doctor_name = "Dr. " . htmlspecialchars($appt['first_name']) . " " . htmlspecialchars($appt['last_name']);

// Handle Form Submission
$message = '';
$msg_type = ''; // Used for error type

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['date'] ?? '';
    $new_time = $_POST['time'] ?? '';

    if ($new_date && $new_time) {
        // Validation
        $check = $pdo->prepare("SELECT id FROM tblappointment WHERE doctor=? AND booking_date=? AND booking_time=? AND status!=0 AND id != ?");
        $check->execute([$doctor_id, $new_date, $new_time, $appt_id]);
        
        if ($check->rowCount() == 0) {
            // Perform Update
            $stmt = $pdo->prepare("UPDATE tblappointment SET booking_date = ?, booking_time = ?, status = 2 WHERE id = ?");
            
            if ($stmt->execute([$new_date, $new_time, $appt_id])) {
                // SUCCESS: Set variables for JS to handle, DO NOT redirect with header()
                $msg_type = 'success';
                $message = "Appointment rescheduled successfully! Pending doctor approval.";
                $success_redirect = "client_appointments.php";
            } else {
                $msg_type = 'error';
                $message = "Database error occurred.";
            }
        } else {
            $msg_type = 'error';
            $message = "Sorry, that slot was just taken.";
        }
    } else {
        $msg_type = 'error';
        $message = "Please select a new date and time.";
    }
}
?>