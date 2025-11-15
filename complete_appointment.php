<?php
// API/complete_appointment.php
session_start();
require_once '../Class/Database.php';

header('Content-Type: application/json');

$db = new Database();
$doctor_id = $_SESSION['user_id'] ?? '';

if (empty($doctor_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$appointment_id = $_POST['id'] ?? '';

if (empty($appointment_id)) {
    echo json_encode(['success' => false, 'message' => 'No appointment ID provided']);
    exit;
}

// Verify that this appointment belongs to the logged-in doctor
$verify = $db->query("SELECT * FROM tblappointment WHERE id = ? AND doctor = ?", [$appointment_id, $doctor_id]);

if (empty($verify)) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or unauthorized']);
    exit;
}

// Update appointment status to completed (1)
$result = $db->execute("UPDATE tblappointment SET status = 1 WHERE id = ?", [$appointment_id]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Appointment marked as completed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update appointment']);
}