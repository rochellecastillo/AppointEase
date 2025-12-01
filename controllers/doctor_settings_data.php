<?php
// doctor_settings.php - Doctor Profile & Security
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$user_id = session_get_user_id();

$message = '';
$msg_type = '';

// --- 1. HANDLE AVATAR UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            if ($file['size'] <= 2 * 1024 * 1024) { // 2MB
                $new_name = "doc_" . $user_id . "_" . time() . "." . $ext;
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                    $stmt = $pdo->prepare("UPDATE tblinfo SET image = ? WHERE user_id = ?");
                    $stmt->execute([$new_name, $user_id]);
                    $message = "Profile photo updated!";
                    $msg_type = "success";
                } else {
                    $message = "Upload failed.";
                    $msg_type = "error";
                }
            } else {
                $message = "File too large (Max 2MB).";
                $msg_type = "error";
            }
        } else {
            $message = "Invalid file format.";
            $msg_type = "error";
        }
    }
}

// --- 2. HANDLE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $sql = "UPDATE tblinfo SET 
                first_name = ?, 
                last_name = ?, 
                specialization = ?,
                contact = ?, 
                address = ?, 
                bdate = ?, 
                gender = ? 
                WHERE user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['specialization'], // Unique to doctors
            $_POST['contact'],
            $_POST['address'],
            $_POST['bdate'],
            $_POST['gender'],
            $user_id
        ]);

        $message = "Profile updated successfully.";
        $msg_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = "error";
    }
}

// --- 3. HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $message = "New passwords do not match.";
        $msg_type = "error";
    } elseif (strlen($new) < 8) {
        $message = "Password too short (min 8 chars).";
        $msg_type = "error";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM tbluser WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?")->execute([$hash, $user_id]);
            $message = "Password changed successfully.";
            $msg_type = "success";
        } else {
            $message = "Incorrect current password.";
            $msg_type = "error";
        }
    }
}

// --- 4. FETCH DATA ---
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
$stmt->execute([$user_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Specializations List
$specs = $pdo->query("SELECT specialization FROM tblspecialization ORDER BY specialization")->fetchAll(PDO::FETCH_COLUMN);

$avatar = !empty($info['image']) ? 'uploads/' . e($info['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($info['first_name'] . '+' . $info['last_name']) . '&background=16a34a&color=fff';
?>