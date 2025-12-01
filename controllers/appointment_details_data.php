<?php
// appointment_details.php - Detailed Appointment View (updated)
// ----------------------------------------------------------------
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

if (!function_exists('e')) {
    function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
}

// 1. Enforce Authentication
session_require_auth(['user']);
$user_id = session_get_user_id();

// 2. Validate Input
$apt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (empty($apt_id)) {
    header('Location: client_home.php');
    exit;
}

// 3. Handle Cancellation Action (POST)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];

    // Verify ownership and current status before cancelling
    $check = $pdo->prepare("SELECT id, status FROM tblappointment WHERE id = ? AND user_id = ? LIMIT 1");
    $check->execute([$cancel_id, $user_id]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $msg = "error_access";
    } else {
        $current_status = (int)$row['status'];
        if ($current_status === 3) {
            // Completed appointments cannot be cancelled
            $msg = "error_completed";
        } elseif ($current_status === 0) {
            // Already cancelled
            $msg = "error_already_cancelled";
        } else {
            $update = $pdo->prepare("UPDATE tblappointment SET status = 0 WHERE id = ?");
            if ($update->execute([$cancel_id])) {
                header("Location: appointment_details.php?id=$apt_id&msg=cancelled");
                exit;
            } else {
                $msg = "error_update";
            }
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled') {
    $msg = "success_cancelled";
}

// 4. Fetch Appointment Data
try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               d.first_name AS doc_first, d.last_name AS doc_last, d.specialization,
               d.contact AS doc_contact, d.address AS doc_address, d.image AS doc_image, d.email AS doc_email,
               p.first_name AS pat_first, p.last_name AS pat_last, p.contact AS pat_contact
        FROM tblappointment a
        LEFT JOIN tblinfo d ON d.user_id = a.doctor
        LEFT JOIN tblinfo p ON p.user_id = a.user_id
        WHERE a.id = ? AND a.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$apt_id, $user_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        header('Location: client_home.php');
        exit;
    }

    // Get doctor's schedule for that day
    $day_of_week = date('w', strtotime($appointment['booking_date']));
    $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND day = ?");
    $stmt->execute([$appointment['doctor'], $day_of_week]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . e($e->getMessage()));
}

// 5. Process Data for View
$doctor_name = 'Dr. ' . trim($appointment['doc_first'] . ' ' . $appointment['doc_last']);
$patient_name = trim($appointment['pat_first'] . ' ' . $appointment['pat_last']);

// Image Handling
$image_path = !empty($appointment['doc_image']) ? 'uploads/' . e($appointment['doc_image']) : null;

// Days Mapping
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Status Logic (0=Cancelled, 1=Confirmed, 2=Pending, 3=Completed)
$status_code = (int)$appointment['status'];
switch ($status_code) {
    case 1:
        $status_label = 'Confirmed';
        $status_classes = 'bg-emerald-100 text-emerald-800 border-emerald-200';
        $icon = 'check-circle';
        break;
    case 0:
        $status_label = 'Cancelled';
        $status_classes = 'bg-red-100 text-red-800 border-red-200';
        $icon = 'x-circle';
        break;
    case 3:
        $status_label = 'Completed';
        $status_classes = 'bg-blue-100 text-blue-800 border-blue-200';
        $icon = 'check-circle';
        break;
    default:
        $status_label = 'Pending';
        $status_classes = 'bg-amber-100 text-amber-800 border-amber-200';
        $icon = 'clock';
        break;
}

// For button logic: only allow reschedule/cancel when appointment is NOT cancelled or completed,
// AND booking date is today or in the future.
$booking_date = $appointment['booking_date'];
$can_modify = ($status_code !== 0 && $status_code !== 3 && strtotime($booking_date) >= strtotime(date('Y-m-d')));
?>