<?php
// users_list.php - Enhanced Patient Management
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php'; 

// Require admin authentication
session_require_auth(['admin']);

$success = '';
$error = '';

function getAge($dob) {
    if (empty($dob)) return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Toggle Status
    if (isset($_POST['toggle_status'])) {
        $target_id = $_POST['user_id'];
        $current_status = (int)$_POST['current_status'];
        $new_status = ($current_status == 1) ? 0 : 1;
        
        try {
            $stmt = $pdo->prepare("UPDATE tbluser SET status = ? WHERE user_id = ?");
            $stmt->execute([$new_status, $target_id]);
            $success = "Patient status updated successfully.";
        } catch (Exception $ex) {
            $error = "Error updating status: " . $ex->getMessage();
        }
    }

    // 2. Delete User (FIXED ORDER)
    if (isset($_POST['delete_user'])) {
        $target_id = $_POST['user_id'] ?? null;
        if ($target_id) {
            try {
                $pdo->beginTransaction();

                // A. Delete Health Profile (Child of tblinfo)
                $stmtHealth = $pdo->prepare("DELETE FROM tbl_health_profile WHERE user_id = ?");
                $stmtHealth->execute([$target_id]);

                // B. Delete Appointments (Child of tblinfo)
                $stmtAppt = $pdo->prepare("DELETE FROM tblappointment WHERE user_id = ?");
                $stmtAppt->execute([$target_id]);

                // C. Delete tbluser (Child of tblinfo due to FK constraint)
                $stmtUser = $pdo->prepare("DELETE FROM tbluser WHERE user_id = ?");
                $stmtUser->execute([$target_id]);

                // D. Delete tblinfo (Parent)
                $stmtInfo = $pdo->prepare("DELETE FROM tblinfo WHERE user_id = ?");
                $stmtInfo->execute([$target_id]);

                $pdo->commit();
                $success = "Patient account and all related records deleted successfully.";

            } catch (Exception $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "Error deleting patient: " . $ex->getMessage();
            }
        }
    }
}

// --- FETCH DATA ---
$search = $_GET['search'] ?? '';

try {
    $sql = "SELECT u.user_id, u.status AS account_status,
                   i.first_name, i.last_name, i.middle_name, 
                   i.bdate, i.gender, i.contact, i.address, i.image
            FROM tbluser u
            LEFT JOIN tblinfo i ON i.user_id = u.user_id
            WHERE u.user_type = 'user'";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (i.last_name LIKE ? OR i.first_name LIKE ? OR u.user_id LIKE ?)";
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    $sql .= " ORDER BY i.last_name, i.first_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { die("Database Error: " . $e->getMessage()); }
?>