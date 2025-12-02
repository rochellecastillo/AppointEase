<?php
// cancel_appointment.php - Handles Appointment Cancellation with Visual Feedback
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// 1. Authentication Check
session_require_auth(['user']);
$user_id = session_get_user_id();

$appt_id = $_GET['id'] ?? null;
$redirect_url = 'client_appointments.php';

// Initialize Alert Variables
$alert_type = '';
$alert_title = '';
$alert_message = '';

// 2. Process Logic
if (!$appt_id) {
    $alert_type = 'error';
    $alert_title = 'Invalid Request';
    $alert_message = 'No appointment ID specified.';
} else {
    try {
        // Verify Ownership & Status
        $stmt = $pdo->prepare("SELECT id, status FROM tblappointment WHERE id = ? AND user_id = ?");
        $stmt->execute([$appt_id, $user_id]);
        $appt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appt) {
            $alert_type = 'error';
            $alert_title = 'Not Found';
            $alert_message = 'Appointment not found or access denied.';
        } elseif ($appt['status'] == 3) {
            $alert_type = 'warning';
            $alert_title = 'Action Blocked';
            $alert_message = 'Cannot cancel completed appointments.';
        } elseif ($appt['status'] == 0) {
            $alert_type = 'info';
            $alert_title = 'Already Cancelled';
            $alert_message = 'This appointment is already cancelled.';
        } else {
            // Perform Cancellation (Status 0)
            $updateStmt = $pdo->prepare("UPDATE tblappointment SET status = 0 WHERE id = ?");
            $updateStmt->execute([$appt_id]);

            // Log activity
            log_security_event('appointment_cancelled', ['appointment_id' => $appt_id, 'user_id' => $user_id]);

            $alert_type = 'success';
            $alert_title = 'Cancelled';
            $alert_message = 'Your appointment has been successfully cancelled.';
        }

    } catch (Exception $e) {
        $alert_type = 'error';
        $alert_title = 'System Error';
        $alert_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Cancellation...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $alert_type ?>',
                title: '<?= $alert_title ?>',
                text: '<?= addslashes($alert_message) ?>',
                confirmButtonColor: '#7c3aed', // Purple to match your theme
                confirmButtonText: 'Return to Appointments',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= $redirect_url ?>';
                }
            });
        });
    </script>
</body>
</html>