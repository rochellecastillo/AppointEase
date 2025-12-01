<?php
// find_doctors.php - Doctor Directory for Patients
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);

// --- 1. FETCH DATA ---

// Get Specializations for Filter
$specializations = $pdo->query("SELECT DISTINCT specialization FROM tblspecialization ORDER BY specialization ASC")->fetchAll(PDO::FETCH_COLUMN);

// Search Logic
$search = $_GET['q'] ?? '';
$filter_spec = $_GET['specialization'] ?? '';

$sql = "SELECT u.user_id, i.first_name, i.last_name, i.specialization, i.image, i.gender 
        FROM tbluser u 
        JOIN tblinfo i ON u.user_id = i.user_id 
        WHERE u.user_type = 'doctor' AND u.status = 1";

$params = [];

if ($search) {
    $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_spec) {
    $sql .= " AND i.specialization = ?";
    $params[] = $filter_spec;
}

$sql .= " ORDER BY i.last_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2. HELPER: GET AVAILABLE DAYS (FIXED) ---
function getDoctorDays($pdo, $doctor_id) {
    $stmt = $pdo->prepare("SELECT day FROM tblschedule WHERE user_id = ? ORDER BY day ASC");
    $stmt->execute([$doctor_id]);
    $days = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($days)) return 'Contact for schedule';
    
    // FIXED: Extended map to handle '7' as Sunday just in case
    $dayMap = [
        0 => 'Sun', 
        1 => 'Mon', 
        2 => 'Tue', 
        3 => 'Wed', 
        4 => 'Thu', 
        5 => 'Fri', 
        6 => 'Sat',
        7 => 'Sun' // Added this to fix "Undefined array key 7"
    ];

    $shortDays = [];
    foreach ($days as $d) {
        // Safety check: Does the key exist?
        if (isset($dayMap[$d])) {
            $shortDays[] = $dayMap[$d];
        }
    }
    
    // Unique removes duplicates (e.g., if both 0 and 7 exist)
    return implode(', ', array_unique($shortDays));
}
?>