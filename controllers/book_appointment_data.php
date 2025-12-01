<?php
// book_appointment.php - Visual Calendar for Patients
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// Capture Pre-selected Doctor from URL (e.g. coming from Find Doctors)
$preselected_doctor = $_GET['doctor_id'] ?? '';

// =============================================================================
// API: HANDLE AJAX REQUESTS
// =============================================================================
if (isset($_GET['action'])) {
    ob_end_clean();
    header('Content-Type: application/json');

    $doctor_id = $_GET['doctor_id'] ?? '';

    if (!$doctor_id) {
        echo json_encode(['status' => 'error', 'message' => 'No doctor selected']);
        exit;
    }

    try {
        // --- API A: Get Monthly Status ---
        if ($_GET['action'] === 'get_monthly_status') {
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');

            // Get Working Days (Normalize to JS 0-6)
            $stmt = $pdo->prepare("SELECT `day` FROM tblschedule WHERE user_id = ?");
            $stmt->execute([$doctor_id]);
            $raw_days = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $working_days = [];
            foreach ($raw_days as $d) {
                // If DB is 1-7 (Mon-Sun), convert 7->0 for JS Sunday
                $working_days[] = (int)($d % 7);
            }
            $working_days = array_values(array_unique($working_days));

            // Get Leave Dates
            $start_date = sprintf('%04d-%02d-01', (int)$year, (int)$month);
            $end_date = date("Y-m-t", strtotime($start_date));

            $stmt = $pdo->prepare("
                SELECT date_start, date_end, reason
                FROM tblnoappointment
                WHERE doctor_id = ?
                  AND NOT (date_end < ? OR date_start > ?)
            ");
            $stmt->execute([$doctor_id, $start_date, $end_date]);
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'working_days' => $working_days,
                'leaves' => $leaves
            ]);
            exit;
        }

        // --- API B: Get Time Slots ---
        if ($_GET['action'] === 'get_slots') {
            $date = $_GET['date'] ?? '';
            if (!$date) exit;

            // 1. Rule: Prevent Same Day Booking
            if ($date <= date('Y-m-d')) {
                echo json_encode(['status' => 'off', 'message' => 'Appointments must be booked at least 1 day in advance.']);
                exit;
            }

            // 2. Rule: Check if User Already Has an Appointment on This Date
            $userCheck = $pdo->prepare("SELECT id FROM tblappointment WHERE user_id = ? AND booking_date = ? AND status != 0");
            $userCheck->execute([$user_id, $date]);
            if ($userCheck->rowCount() > 0) {
                echo json_encode(['status' => 'off', 'message' => 'You already have an appointment scheduled for this day.']);
                exit;
            }

            // 3. Check Schedule
            $day_of_week = date('N', strtotime($date)); // 1 (Mon) - 7 (Sun)
            $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND `day` = ?");
            $stmt->execute([$doctor_id, $day_of_week]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                echo json_encode(['status' => 'off', 'message' => 'Doctor is not working on this day.']);
                exit;
            }

            // 4. Check Leave
            $check_leave = $pdo->prepare("SELECT 1 FROM tblnoappointment WHERE doctor_id = ? AND ? BETWEEN date_start AND date_end LIMIT 1");
            $check_leave->execute([$doctor_id, $date]);
            if ($check_leave->rowCount() > 0) {
                echo json_encode(['status' => 'leave', 'message' => 'Doctor is on leave.']);
                exit;
            }

            // 5. Calculate Slots
            $booked_stmt = $pdo->prepare("SELECT booking_time FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0");
            $booked_stmt->execute([$doctor_id, $date]);
            $booked_times = $booked_stmt->fetchAll(PDO::FETCH_COLUMN);

            $start = strtotime($schedule['time']);
            $end = strtotime($schedule['time2']);
            $step = 30 * 60;
            $slots = [];

            for ($i = $start; $i < $end; $i += $step) {
                $timeVal = date('H:i:s', $i);
                $timeDisp = date('h:i A', $i);
                $slots[] = [
                    'time' => $timeVal,
                    'display' => $timeDisp,
                    'available' => !in_array($timeVal, $booked_times)
                ];
            }

            echo json_encode(['status' => 'success', 'slots' => $slots]);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        exit;
    }
}

ob_end_flush();

// =============================================================================
// PHP: HANDLE FORM SUBMISSION
// =============================================================================
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor = $_POST['doctor'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if ($doctor && $date && $time) {
        
        // Server-Side Validation: Same Day Check
        if ($date <= date('Y-m-d')) {
            $message = "Error: You cannot book appointments for today or past dates.";
        } 
        else {
            // Server-Side Validation: One Appointment Per Day Check
            $limitCheck = $pdo->prepare("SELECT id FROM tblappointment WHERE user_id = ? AND booking_date = ? AND status != 0");
            $limitCheck->execute([$user_id, $date]);

            if ($limitCheck->rowCount() > 0) {
                $message = "Error: You already have an appointment on this date.";
            } 
            else {
                // Final Redundancy Check (Slot taken?)
                $check = $pdo->prepare("SELECT id FROM tblappointment WHERE doctor=? AND booking_date=? AND booking_time=? AND status!=0");
                $check->execute([$doctor, $date, $time]);
                
                if ($check->rowCount() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO tblappointment (booking_date, booking_time, user_id, doctor, status) VALUES (?, ?, ?, ?, 2)");
                    if ($stmt->execute([$date, $time, $user_id, $doctor])) {
                        header("Location: book_appointment.php?booking=success"); 
                        exit;
                    }
                } else {
                    $message = "Error: This slot was just booked by someone else.";
                }
            }
        }
    } else {
        $message = "Please complete all fields.";
    }
}

// Fetch Doctors
$doctors = $pdo->query("SELECT t.user_id, t.first_name, t.last_name, t.specialization FROM tblinfo t JOIN tbluser u ON t.user_id = u.user_id WHERE u.user_type='doctor'")->fetchAll();
?>