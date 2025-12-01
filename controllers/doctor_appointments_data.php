<?php
// doctor_appointments.php - Appointment Management for Doctors
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'iprog_sms.php'; // Included SMS helper

session_require_auth(['doctor']);
$user_id = session_get_user_id();

// --- 1. HANDLE ACTIONS (Approve/Decline) ---
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $appt_id = $_POST['appt_id'];
        $action = $_POST['action_type'];
        
        // Validate ownership
        $check = $pdo->prepare("SELECT id FROM tblappointment WHERE id = ? AND doctor = ?");
        $check->execute([$appt_id, $user_id]);
        
        if ($check->rowCount() > 0) {
            $new_status = ($action === 'approve') ? 1 : 0; // 1=Confirmed, 0=Cancelled
            
            try {
                $pdo->beginTransaction(); // Start Transaction

                // Update Status
                $stmt = $pdo->prepare("UPDATE tblappointment SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $appt_id]);

                // --- SMS LOGIC START ---
                // Fetch Patient Info regardless of Approve/Decline
                $infoStmt = $pdo->prepare("
                    SELECT i.contact, i.first_name, a.booking_date, a.booking_time 
                    FROM tblappointment a 
                    JOIN tblinfo i ON a.user_id = i.user_id 
                    WHERE a.id = ?
                ");
                $infoStmt->execute([$appt_id]);
                $apptData = $infoStmt->fetch(PDO::FETCH_ASSOC);

                if ($apptData && !empty($apptData['contact'])) {
                    $formattedDate = date('M d, Y', strtotime($apptData['booking_date']));
                    $formattedTime = date('h:i A', strtotime($apptData['booking_time']));
                    
                    if ($action === 'approve') {
                        // APPROVE MESSAGE
                        $smsContent = "Hello {$apptData['first_name']}, your appointment for $formattedDate at $formattedTime has been CONFIRMED by the doctor. - AppointEase";
                        iprog_send_sms($apptData['contact'], $smsContent);
                        $message = "Appointment confirmed & SMS sent.";
                    } elseif ($action === 'decline') {
                        // DECLINE MESSAGE
                        $smsContent = "Hello {$apptData['first_name']}, unfortunately your appointment request for $formattedDate at $formattedTime has been DECLINED by the doctor. Please reschedule. - AppointEase";
                        iprog_send_sms($apptData['contact'], $smsContent);
                        $message = "Appointment declined & SMS notification sent.";
                    }
                } else {
                    $message = ($action === 'approve') ? "Appointment confirmed (No SMS sent - contact missing)." : "Appointment declined.";
                }
                // --- SMS LOGIC END ---

                $pdo->commit(); // Commit Transaction
                $msg_type = ($action === 'approve') ? "success" : "success"; // Both actions successful

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Database Error: " . $e->getMessage();
                $msg_type = "error";
            }
        } else {
            $message = "Invalid appointment ID.";
            $msg_type = "error";
        }
    }
}

// --- 2. FETCH APPOINTMENTS ---
$filter = $_GET['status'] ?? 'upcoming';
$today = date('Y-m-d'); 

// FIX: Added a.user_id to select list for the records link
$sql = "SELECT a.id, a.user_id, a.booking_date, a.booking_time, a.status, 
                i.first_name, i.last_name, i.contact, i.gender, i.bdate, i.address
        FROM tblappointment a
        JOIN tblinfo i ON a.user_id = i.user_id
        WHERE a.doctor = ?";

if ($filter === 'pending') {
    $sql .= " AND a.status = 2 AND a.booking_date >= ?"; // 2 = Pending
    $params = [$user_id, $today];
} elseif ($filter === 'history') {
    // FIX: Include Status 3 (Completed) in history
    $sql .= " AND (a.booking_date < ? OR a.status = 0 OR a.status = 3)"; 
    $params = [$user_id, $today];
} else {
    // Default: Upcoming Confirmed (status = 1 and date >= today)
    $sql .= " AND a.status = 1 AND a.booking_date >= ?";
    $params = [$user_id, $today];
}

$sql .= " ORDER BY a.booking_date ASC, a.booking_time ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Error: " . $e->getMessage()); }

// Helper for Age Calculation
function getAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>