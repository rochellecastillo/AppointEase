<?php
// app/data/appointment_list_data.php
// Data + action handling for appointment_list_report.php
// This file should NOT output HTML — it only prepares variables used by the template.

// Adjust paths as needed depending on your project layout.
require_once 'session_handler.php'; 
require_once 'security_helper.php'; 
require_once 'db.php'; 
require_once 'logging_helper.php'; 
require_once 'iprog_sms.php'; // <--- 1. NEW: Include SMS helper

// Require admin authentication
session_require_auth(['admin']);

$adminName = session_get_username();
$success = '';
$error = '';

// --- HANDLE ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Status
    if (isset($_POST['update_status'])) {
        $apt_id = (int)$_POST['apt_id'];
        $new_status = (int)$_POST['status'];
        
        // Fetch patient and appointment details needed for SMS before updating
        $infoStmt = $pdo->prepare("
            SELECT 
                a.booking_date, 
                a.booking_time, 
                p.contact, 
                p.first_name
            FROM tblappointment a 
            JOIN tblinfo p ON a.user_id = p.user_id 
            WHERE a.id = ?
        ");
        $infoStmt->execute([$apt_id]);
        $apptData = $infoStmt->fetch(PDO::FETCH_ASSOC);

        try {
            // Update Status in DB
            $stmt = $pdo->prepare("UPDATE tblappointment SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $apt_id]);
            
            $smsSent = false;
            // SMS LOGIC: Send notification for Confirmed (1) or Cancelled (0)
            if ($apptData && !empty($apptData['contact']) && ($new_status === 1 || $new_status === 0)) {
                
                $formattedDate = date('M d, Y', strtotime($apptData['booking_date']));
                $formattedTime = date('h:i A', strtotime($apptData['booking_time']));
                $patientFirstName = $apptData['first_name'];
                $contactNumber = $apptData['contact'];
                $smsContent = '';
                $statusName = '';

                if ($new_status === 1) {
                    $smsContent = "Hello {$patientFirstName}, your appointment #$apt_id for $formattedDate at $formattedTime has been CONFIRMED by the admin. - AppointEase";
                    $statusName = "confirmed";
                } elseif ($new_status === 0) {
                    $smsContent = "Hello {$patientFirstName}, your appointment #$apt_id for $formattedDate at $formattedTime has been CANCELLED by the admin. Please contact us for rescheduling. - AppointEase";
                    $statusName = "cancelled";
                }
                
                if (!empty($smsContent)) {
                    // iprog_send_sms should be defined in iprog_sms.php
                    try {
                        iprog_send_sms($contactNumber, $smsContent);
                        // optional: log SMS send
                        if (function_exists('log_activity')) {
                            log_activity("SMS sent to {$contactNumber} for appointment {$apt_id}: {$statusName}");
                        }
                        $success = "Appointment #$apt_id status updated to **$statusName** & SMS notification sent.";
                        $smsSent = true;
                    } catch (Exception $smsEx) {
                        // Continue: show success but include a warning about SMS failure
                        if (function_exists('log_activity')) {
                            log_activity("SMS failed for appointment {$apt_id}: " . $smsEx->getMessage());
                        }
                        $success = "Appointment #$apt_id status updated to **$statusName**, but SMS failed to send.";
                        $smsSent = false;
                    }
                }
            }

            if (!$smsSent && $success === '') {
                 // Fallback success message
                 $success = "Appointment #$apt_id status updated successfully.";
            }

        } catch (Exception $ex) {
            $error = "Error: " . $ex->getMessage();
        }
    }

    // Delete Appointment
    if (isset($_POST['delete_appointment'])) {
        $del_id = (int)$_POST['apt_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tblappointment WHERE id = ?");
            $stmt->execute([$del_id]);
            $success = "Appointment #$del_id deleted successfully.";
        } catch (Exception $ex) {
            $error = "Error: " . $ex->getMessage();
        }
    }
}

// --- HANDLE FILTERS (GET) ---
$filter_search = $_GET['search'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? 'all';

// Build Query
$sql = "SELECT a.id, a.booking_date, a.booking_time, a.status, a.user_id,
              p.first_name AS pfirst, p.last_name AS plast, p.contact, p.image,
              d.first_name AS dfirst, d.last_name AS dlast, d.specialization
        FROM tblappointment a
        LEFT JOIN tblinfo p ON p.user_id = a.user_id
        LEFT JOIN tblinfo d ON d.user_id = a.doctor
        WHERE 1=1";

$params = [];

if (!empty($filter_search)) {
    $sql .= " AND (p.last_name LIKE ? OR p.first_name LIKE ? OR a.id = ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $params[] = $filter_search;
}

if (!empty($filter_date)) {
    $sql .= " AND a.booking_date = ?";
    $params[] = $filter_date;
}

if ($filter_status !== 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY a.booking_date DESC, a.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If something goes wrong, set empty array and capture error for the template
    $appointments = [];
    $error = "Database Error: " . $e->getMessage();
}