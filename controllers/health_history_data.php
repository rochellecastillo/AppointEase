<?php
// health_history.php - Patient Personal Health Profile (Read-Only / Print Mode)
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// --- 1. FETCH DATA (Read Only) ---
$profile = [];
try {
    // Fetch Basic Info for the header
    $stmtUser = $pdo->prepare("SELECT first_name, last_name, bdate, gender, contact FROM tblinfo WHERE user_id = ?");
    $stmtUser->execute([$user_id]);
    $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Fetch Health Profile
    $stmt = $pdo->prepare("SELECT * FROM tbl_health_profile WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) { /* Ignore */ }

// Helper to safely get value
function val($key, $data) { return e($data[$key] ?? ''); }
?>