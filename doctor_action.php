<?php
// doctor_action.php - Handle doctor actions (cancel appointment, etc.)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

// Require doctor role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'doctor') {
    header('Location: login.php');
    exit;
}

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        die('CSRF token validation failed');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$my_user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'cancel_appointment':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Verify this appointment belongs to this doctor
                $stmt = $pdo->prepare("SELECT id FROM tblappointment WHERE id = ? AND doctor = ? LIMIT 1");
                $stmt->execute([$id, $my_user_id]);
                if ($stmt->fetch()) {
                    // Update status to 0 (cancelled)
                    $stmt = $pdo->prepare("UPDATE tblappointment SET status = 0 WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success_message'] = 'Appointment cancelled successfully';
                } else {
                    $_SESSION['error_message'] = 'Appointment not found or unauthorized';
                }
            }
            header('Location: doctor_home.php');
            exit;

        case 'confirm_appointment':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM tblappointment WHERE id = ? AND doctor = ? LIMIT 1");
                $stmt->execute([$id, $my_user_id]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE tblappointment SET status = 1 WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success_message'] = 'Appointment confirmed successfully';
                } else {
                    $_SESSION['error_message'] = 'Appointment not found or unauthorized';
                }
            }
            header('Location: doctor_home.php');
            exit;

        default:
            $_SESSION['error_message'] = 'Invalid action';
            header('Location: doctor_home.php');
            exit;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    header('Location: doctor_home.php');
    exit;
}
?>