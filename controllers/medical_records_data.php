<?php
// medical_records.php - Patient Health History & Reports (updated)
// ----------------------------------------------------------------
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

if (!function_exists('e')) {
    function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
}

session_require_auth(['user']);
$user_id = session_get_user_id();

// Inputs
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : '';
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';

$records = [];

try {
    if ($appointment_id) {
        // Fetch the medical record for this exact appointment (secure: ensure belongs to current user)
        $sql = "SELECT 
                    a.id AS appointment_id, 
                    a.booking_date, 
                    a.booking_time, 
                    a.status,
                    d.first_name, 
                    d.last_name, 
                    d.specialization, 
                    mr.diagnosis, 
                    mr.prescription, 
                    mr.notes
                FROM tblappointment a
                JOIN tblinfo d ON a.doctor = d.user_id
                JOIN tbl_medical_records mr ON a.id = mr.appointment_id
                WHERE a.id = ? AND a.user_id = ?
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$appointment_id, $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $records[] = $row;
    } else {
        // Build base query (only completed appointments with medical records)
        $sql = "SELECT 
                    a.id AS appointment_id, 
                    a.booking_date, 
                    a.booking_time, 
                    a.status,
                    d.first_name, 
                    d.last_name, 
                    d.specialization, 
                    mr.diagnosis, 
                    mr.prescription, 
                    mr.notes
                FROM tblappointment a
                JOIN tblinfo d ON a.doctor = d.user_id
                JOIN tbl_medical_records mr ON a.id = mr.appointment_id
                WHERE a.user_id = ? AND a.status = 3";
        $params = [$user_id];

        if ($doctor_id) {
            $sql .= " AND a.doctor = ?";
            $params[] = $doctor_id;
        }

        if ($search) {
            $sql .= " AND (d.first_name LIKE ? OR d.last_name LIKE ? OR d.specialization LIKE ? OR mr.diagnosis LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($date_filter) {
            $sql .= " AND a.booking_date = ?";
            $params[] = $date_filter;
        }

        $sql .= " ORDER BY a.booking_date DESC, a.booking_time DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    die("Error loading records: " . e($e->getMessage()));
}
?>