<?php
// reports.php - Comprehensive Reports & Analytics
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Require admin authentication
session_require_auth(['admin']);

// --- Date Filter Setup ---
$today = date('Y-m-d');
$default_start_date = date('Y-m-d', strtotime('-30 days'));

$start_date = $_GET['start_date'] ?? $default_start_date;
$end_date = $_GET['end_date'] ?? $today;

// Base WHERE clause
$where_clause = "WHERE a.booking_date >= :start_date AND a.booking_date <= :end_date";
$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

try {
    // 1. Appointment Status Breakdown
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS confirmed_count,
            SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS cancelled_count,
            COUNT(*) as total_count
        FROM tblappointment a
        {$where_clause}
    ");
    $stmt->execute($params);
    $status_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Appointments per month
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COUNT(*) as count 
        FROM tblappointment a
        {$where_clause}
        GROUP BY month 
        ORDER BY month ASC
    ");
    $stmt->execute($params);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Top doctors
    $stmt = $pdo->prepare("
        SELECT a.doctor, i.first_name, i.last_name, i.specialization, COUNT(*) as count
        FROM tblappointment a
        LEFT JOIN tblinfo i ON i.user_id = a.doctor
        {$where_clause}
        GROUP BY a.doctor
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $top_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Appointments by Specialization
    $stmt = $pdo->prepare("
        SELECT i.specialization, COUNT(a.id) as count
        FROM tblappointment a
        LEFT JOIN tblinfo i ON i.user_id = a.doctor
        {$where_clause}
        GROUP BY i.specialization
        ORDER BY count DESC
    ");
    $stmt->execute($params);
    $specialization_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Overall User statistics
    $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM tbluser GROUP BY user_type");
    $user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 6. NEW: Patient Age Demographics (Child, Adult, Senior)
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, bdate, CURDATE()) < 18 THEN 'Child'
                WHEN TIMESTAMPDIFF(YEAR, bdate, CURDATE()) >= 60 THEN 'Senior'
                ELSE 'Adult'
            END AS age_group,
            COUNT(*) as count
        FROM tblinfo i
        JOIN tbluser u ON i.user_id = u.user_id
        WHERE u.user_type = 'user' AND i.bdate IS NOT NULL AND i.bdate != '0000-00-00'
        GROUP BY age_group
    ");
    $age_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Ensure all keys exist (even if 0)
    $age_stats = [
        'Child' => $age_raw['Child'] ?? 0,
        'Adult' => $age_raw['Adult'] ?? 0,
        'Senior' => $age_raw['Senior'] ?? 0
    ];
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>