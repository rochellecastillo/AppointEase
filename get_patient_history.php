<?php
// get_patient_history.php - API to get patient appointment history
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'doctor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$my_user_id = $_SESSION['user_id'];
$patient_id = $_GET['patient_id'] ?? '';

if (empty($patient_id)) {
    echo json_encode(['error' => 'Patient ID is required']);
    exit;
}

try {
    // Get all appointments for this patient with this doctor
    $stmt = $pdo->prepare("SELECT id, booking_date, status
                          FROM tblappointment
                          WHERE user_id = ? AND doctor = ?
                          ORDER BY booking_date DESC");
    $stmt->execute([$patient_id, $my_user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>