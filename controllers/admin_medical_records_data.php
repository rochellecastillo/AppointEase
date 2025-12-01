<?php
// admin_medical_records.php - Manage Patient Health Profiles
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Enforce Admin Access
session_require_auth(['admin']);

// --- 1. HANDLE FORM SUBMISSION (UPDATE PROFILE) ---
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'])) {
    $p_id = $_POST['patient_id'];
    
    try {
        // Check if profile exists for this user
        $check = $pdo->prepare("SELECT id FROM tbl_health_profile WHERE user_id = ?");
        $check->execute([$p_id]);

        if ($check->rowCount() > 0) {
            // Update existing profile
            $sql = "UPDATE tbl_health_profile SET 
                    blood_type=?, height=?, weight=?, allergies=?, 
                    chronic_conditions=?, current_medications=?, past_surgeries=?, 
                    family_history=?, emergency_contact_name=?, emergency_contact_phone=? 
                    WHERE user_id=?";
        } else {
            // Create new profile
            $sql = "INSERT INTO tbl_health_profile 
                    (blood_type, height, weight, allergies, chronic_conditions, 
                     current_medications, past_surgeries, family_history, 
                     emergency_contact_name, emergency_contact_phone, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['blood_type'], $_POST['height'], $_POST['weight'], 
            $_POST['allergies'], $_POST['chronic_conditions'], 
            $_POST['current_medications'], $_POST['past_surgeries'], 
            $_POST['family_history'], $_POST['ec_name'], $_POST['ec_phone'], 
            $p_id
        ]);
        
        header("Location: admin_medical_records.php?msg=success");
        exit;

    } catch (Exception $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// --- 2. FETCH PATIENTS & PROFILES ---
$search = $_GET['search'] ?? '';

// FIXED: Changed 'i.avatar' to 'i.image' in the SELECT list
$sql = "SELECT u.user_id, i.first_name, i.last_name, i.contact, i.email, i.image,
               hp.blood_type, hp.height, hp.weight, hp.allergies, hp.chronic_conditions, 
               hp.current_medications, hp.past_surgeries, hp.family_history, 
               hp.emergency_contact_name, hp.emergency_contact_phone, hp.updated_at
        FROM tbluser u
        JOIN tblinfo i ON u.user_id = i.user_id
        LEFT JOIN tbl_health_profile hp ON u.user_id = hp.user_id
        WHERE u.user_type = 'user'"; 

$params = [];

if ($search) {
    $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ? OR u.user_id LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY i.last_name ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>