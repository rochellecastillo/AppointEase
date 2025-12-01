<?php
// doctor_records.php - Medical History Management
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$doctor_id = session_get_user_id();

$search = $_GET['search'] ?? '';
$patient_filter = $_GET['patient_id'] ?? '';
$action = $_GET['action'] ?? '';

// --- 1. FETCH RECORDS ---
// We join medical_records -> appointment -> patient info
$sql = "SELECT mr.*, 
               a.booking_date, 
               p.first_name, p.last_name, p.gender, p.bdate, p.image
        FROM tbl_medical_records mr
        JOIN tblappointment a ON mr.appointment_id = a.id
        JOIN tblinfo p ON a.user_id = p.user_id
        WHERE a.doctor = ?";

$params = [$doctor_id];

if ($patient_filter) {
    $sql .= " AND p.user_id = ?";
    $params[] = $patient_filter;
}

if ($search) {
    $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR mr.diagnosis LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.booking_date DESC, mr.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Get Patient Details if filtered
$patientInfo = null;
if ($patient_filter) {
    $stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
    $stmt->execute([$patient_filter]);
    $patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper for Age
function getAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>