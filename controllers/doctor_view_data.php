<?php
// doctor_view.php - Patient Profile View for Doctors
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$doctor_id = session_get_user_id();

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    header('Location: patients.php');
    exit;
}

// --- 1. FETCH PATIENT DETAILS & HEALTH PROFILE ---
try {
    $stmt = $pdo->prepare("
        SELECT i.first_name, i.last_name, i.contact, i.email, i.gender, i.bdate, i.address, i.image,
               hp.blood_type, hp.height, hp.weight, hp.allergies, hp.chronic_conditions, 
               hp.current_medications, hp.past_surgeries, hp.family_history, 
               hp.emergency_contact_name, hp.emergency_contact_phone
        FROM tblinfo i
        LEFT JOIN tbl_health_profile hp ON i.user_id = hp.user_id
        WHERE i.user_id = ?
    ");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Patient not found.");
    }

    // Calculate Age
    $age = date_diff(date_create($patient['bdate']), date_create('today'))->y;

    // --- 2. FETCH RECENT VISITS ---
    $histStmt = $pdo->prepare("
        SELECT a.booking_date, a.status, mr.diagnosis, mr.prescription
        FROM tblappointment a
        LEFT JOIN tbl_medical_records mr ON a.id = mr.appointment_id
        WHERE a.user_id = ? AND a.doctor = ?
        ORDER BY a.booking_date DESC LIMIT 5
    ");
    $histStmt->execute([$patient_id, $doctor_id]);
    $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}

// Helper for Avatar
$avatar = !empty($patient['image']) ? 'uploads/' . htmlspecialchars($patient['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($patient['first_name'] . '+' . $patient['last_name']) . '&background=random';

// Helper function to safely display values
function val($key, $data) { 
    return !empty($data[$key]) ? htmlspecialchars($data[$key]) : '<span class="text-slate-400 italic">None</span>'; 
}
?>