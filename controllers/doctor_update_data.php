<?php
// doctor_update_profile.php - Update Patient Health Profile
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    header('Location: patients.php');
    exit;
}

$msg = '';
$msg_type = '';

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect Inputs
        $blood_type = $_POST['blood_type'];
        $height = !empty($_POST['height']) ? $_POST['height'] : null;
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $allergies = trim($_POST['allergies']);
        $conditions = trim($_POST['chronic_conditions']);
        $meds = trim($_POST['current_medications']);
        $surgeries = trim($_POST['past_surgeries']);
        $history = trim($_POST['family_history']);
        $emer_name = trim($_POST['emergency_contact_name']);
        $emer_phone = trim($_POST['emergency_contact_phone']);

        // Check if profile exists
        $checkStmt = $pdo->prepare("SELECT id FROM tbl_health_profile WHERE user_id = ?");
        $checkStmt->execute([$patient_id]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            // UPDATE existing record
            $sql = "UPDATE tbl_health_profile SET 
                    blood_type = ?, height = ?, weight = ?, allergies = ?, 
                    chronic_conditions = ?, current_medications = ?, past_surgeries = ?, 
                    family_history = ?, emergency_contact_name = ?, emergency_contact_phone = ?
                    WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$blood_type, $height, $weight, $allergies, $conditions, $meds, $surgeries, $history, $emer_name, $emer_phone, $patient_id]);
        } else {
            // INSERT new record
            $sql = "INSERT INTO tbl_health_profile 
                    (user_id, blood_type, height, weight, allergies, chronic_conditions, current_medications, past_surgeries, family_history, emergency_contact_name, emergency_contact_phone)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patient_id, $blood_type, $height, $weight, $allergies, $conditions, $meds, $surgeries, $history, $emer_name, $emer_phone]);
        }

        log_security_event('update_health_profile', ['patient_id' => $patient_id, 'doctor_id' => session_get_user_id()]);
        
        $msg = "Health profile updated successfully!";
        $msg_type = "success";

    } catch (Exception $e) {
        $msg = "Error updating profile: " . $e->getMessage();
        $msg_type = "error";
    }
}

// 2. Fetch Current Data to Pre-fill Form
try {
    // Get Basic Info
    $stmtUser = $pdo->prepare("SELECT first_name, last_name, image FROM tblinfo WHERE user_id = ?");
    $stmtUser->execute([$patient_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Get Health Profile
    $stmtHealth = $pdo->prepare("SELECT * FROM tbl_health_profile WHERE user_id = ?");
    $stmtHealth->execute([$patient_id]);
    $hp = $stmtHealth->fetch(PDO::FETCH_ASSOC) ?: []; 

} catch (Exception $e) {
    die("Database Error");
}

$avatar = !empty($user['image']) ? 'uploads/' . e($user['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']);
?>