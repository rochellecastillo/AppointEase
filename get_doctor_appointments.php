<?php
// API/get_doctor_appointments.php
session_start();
require_once '../Class/Database.php';

header('Content-Type: application/json');

$db = new Database();
$doctor_id = $_SESSION['user_id'] ?? '';

if (empty($doctor_id)) {
    echo json_encode(['error' => 'No doctor ID']);
    exit;
}

// Fetch all appointments for this doctor
$appointments = $db->query("
    SELECT 
        a.id,
        a.booking_date,
        a.user_id,
        a.status,
        CONCAT(i.first_name, ' ', i.last_name) as patient_name
    FROM tblappointment a
    JOIN tblinfo i ON a.user_id = i.user_id
    WHERE a.doctor = ?
    ORDER BY a.booking_date ASC
", [$doctor_id]);

// Format events for FullCalendar
$events = [];
foreach ($appointments as $apt) {
    $color = '#0d6efd'; // Default blue
    if ($apt['status'] == 1) {
        $color = '#198754'; // Green for completed
    } elseif ($apt['status'] == 2) {
        $color = '#dc3545'; // Red for cancelled
    }
    
    $events[] = [
        'id' => $apt['id'],
        'title' => $apt['patient_name'],
        'start' => $apt['booking_date'],
        'color' => $color,
        'extendedProps' => [
            'user_id' => $apt['user_id'],
            'status' => $apt['status']
        ]
    ];
}

echo json_encode($events);