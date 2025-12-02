<?php
// doctor_home.php - Modernized Doctor Dashboard
// ---------------------------------------------------------
require_once 'session_handler.php'; 
require_once 'security_helper.php'; 
require_once 'db.php'; 
require_once 'logging_helper.php';

session_require_auth(['doctor']);


$my_user_id = session_get_user_id();

// Fetch Data
$stmt = $pdo->prepare("SELECT i.*, u.user_name FROM tblinfo i JOIN tbluser u ON u.user_id = i.user_id WHERE i.user_id = ? LIMIT 1");
$stmt->execute([$my_user_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

$today = date('Y-m-d');

// Stats Counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0");
$stmt->execute([$my_user_id, $today]);
$count_today = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tblappointment WHERE doctor = ? AND booking_date > ? AND status != 0");
$stmt->execute([$my_user_id, $today]);
$count_upcoming = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM tblappointment WHERE doctor = ?");
$stmt->execute([$my_user_id]);
$count_patients = $stmt->fetchColumn();

// Today's Schedule
$stmt = $pdo->prepare("
    SELECT a.id, a.booking_date, a.booking_time, a.status, 
           i.first_name, i.last_name, i.contact, i.gender
    FROM tblappointment a
    LEFT JOIN tblinfo i ON i.user_id = a.user_id
    WHERE a.doctor = ? AND a.booking_date = ? AND a.status != 0
    ORDER BY a.booking_time ASC
");
$stmt->execute([$my_user_id, $today]);
$today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming List
$stmt = $pdo->prepare("
    SELECT a.id, a.booking_date, a.booking_time, a.status, 
           i.first_name, i.last_name
    FROM tblappointment a
    LEFT JOIN tblinfo i ON i.user_id = a.user_id
    WHERE a.doctor = ? AND a.booking_date > ? AND a.status != 0
    ORDER BY a.booking_date ASC, a.booking_time ASC
    LIMIT 5
");
$stmt->execute([$my_user_id, $today]);
$upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>