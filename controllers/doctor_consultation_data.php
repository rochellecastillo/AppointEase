<?php
// doctor_consultation.php - Consultation Interface
ob_start();
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$user_id = session_get_user_id();

$appt_id = $_GET['id'] ?? '';
if (!$appt_id) {
    header('Location: doctor_appointments.php');
    exit;
}

// --- 1. FETCH APPOINTMENT & PATIENT DATA ---
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               i.first_name, i.last_name, i.bdate, i.gender, i.contact, i.address, i.image,
               hp.blood_type, hp.height, hp.weight, hp.allergies, hp.chronic_conditions, hp.current_medications
        FROM tblappointment a
        JOIN tblinfo i ON a.user_id = i.user_id
        LEFT JOIN tbl_health_profile hp ON a.user_id = hp.user_id
        WHERE a.id = ? AND a.doctor = ?
    ");
    $stmt->execute([$appt_id, $user_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        die("Appointment not found or unauthorized.");
    }

    $age = date_diff(date_create($appt['bdate']), date_create('today'))->y;

    $histStmt = $pdo->prepare("
        SELECT mr.*, a.booking_date 
        FROM tbl_medical_records mr
        JOIN tblappointment a ON mr.appointment_id = a.id
        WHERE a.user_id = ? AND a.id != ?
        ORDER BY a.booking_date DESC LIMIT 5
    ");
    $histStmt->execute([$appt['user_id'], $appt_id]);
    $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// --- 2. HANDLE FORM SUBMISSION ---
// FIX: Check for hidden input 'save_consultation' OR button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_consultation']) || isset($_POST['btn_save']))) {
    $diagnosis = trim($_POST['diagnosis']);
    $prescription = trim($_POST['prescription']);
    $notes = trim($_POST['notes']);
    
    if(empty($diagnosis) || empty($notes)) {
        $error_msg = "Diagnosis and Clinical Notes are required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert Record
            $stmt = $pdo->prepare("INSERT INTO tbl_medical_records (appointment_id, diagnosis, prescription, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$appt_id, $diagnosis, $prescription, $notes]);

            // Update Appointment Status
            $updateStmt = $pdo->prepare("UPDATE tblappointment SET status = 3 WHERE id = ?");
            $updateStmt->execute([$appt_id]);

            $pdo->commit();
            
            // Set success flag for JS redirection
            $success_redirect = "doctor_records.php?patient_id=" . $appt['user_id'] . "&saved=1";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

$avatar = !empty($appt['image']) ? 'uploads/' . htmlspecialchars($appt['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($appt['first_name'] . '+' . $appt['last_name']) . '&background=random';
?>