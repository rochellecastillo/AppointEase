<?php
// patients.php - Doctor's Patient List
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$doctor_id = session_get_user_id();

// --- 1. FETCH PATIENTS ---
$search = $_GET['search'] ?? '';

// Base Query: Get all unique patients who have booked with this doctor
$sql = "SELECT DISTINCT 
            i.user_id, 
            i.first_name, 
            i.last_name, 
            i.contact, 
            i.gender, 
            i.bdate, 
            i.address,
            i.image,
            MIN(a.booking_date) as first_visit,
            MAX(a.booking_date) as last_visit
        FROM tblappointment a
        JOIN tblinfo i ON a.user_id = i.user_id
        WHERE a.doctor = ?";

$params = [$doctor_id];

// Apply Filter
if ($search) {
    $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ? OR i.contact LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY i.user_id ORDER BY last_visit DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . e($e->getMessage()));
}

// --- 2. CALCULATE STATS ---
$total_patients = count($patients);

// Active = Visited in last 30 days
$active_patients = count(array_filter($patients, function($p) {
    return !empty($p['last_visit']) && strtotime($p['last_visit']) >= strtotime('-30 days');
}));

// New This Month = First visit was in current month/year
$new_this_month = count(array_filter($patients, function($p) {
    return !empty($p['first_visit']) && date('Y-m', strtotime($p['first_visit'])) === date('Y-m');
}));

// Helper for Age
function getAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>