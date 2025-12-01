<?php
// client_appointments.php - Enhanced Appointment Management
// ---------------------------------------------------------
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Client Authentication
session_require_auth(['user']);

$user_id = session_get_user_id();

// Fetch all appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.booking_date, a.status,
               d.first_name AS doc_first, d.last_name AS doc_last, 
               d.specialization, d.contact AS doc_contact
        FROM tblappointment a
        LEFT JOIN tblinfo d ON d.user_id = a.doctor
        WHERE a.user_id = ?
        ORDER BY a.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . e($e->getMessage()));
}

// Categorize appointments
$upcoming = [];
$past = [];
$cancelled = [];
$today = date('Y-m-d');

foreach ($appointments as $apt) {
    $status = (int)$apt['status'];
    $date = $apt['booking_date'];
    
    // Logic:
    // 0 = Cancelled -> Cancelled Tab
    // 3 = Completed -> Past Tab (regardless of date)
    // Others (1, 2) -> If date >= today -> Upcoming, else -> Past
    
    if ($status === 0) {
        $cancelled[] = $apt;
    } elseif ($status === 3) {
        $past[] = $apt; // Completed always goes to history
    } elseif ($date >= $today) {
        $upcoming[] = $apt;
    } else {
        $past[] = $apt;
    }
}

// Helper to render status badges
function getStatusBadge($statusInt) {
    switch ($statusInt) {
        case 1: // Confirmed
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Confirmed</span>';
        case 2: // Pending
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Pending</span>';
        case 3: // Completed
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">Completed</span>';
        case 0: // Cancelled
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Cancelled</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Unknown</span>';
    }
}
?>