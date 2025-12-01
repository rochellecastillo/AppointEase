<?php
// client_settings.php - Account & Profile Management
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

$message = '';
$msg_type = '';

// --- 1. HANDLE PROFILE PICTURE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            if ($file['size'] <= 2 * 1024 * 1024) { // 2MB Limit
                $new_name = "avatar_" . $user_id . "_" . time() . "." . $ext;
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                    // Update DB
                    $stmt = $pdo->prepare("UPDATE tblinfo SET image = ? WHERE user_id = ?");
                    $stmt->execute([$new_name, $user_id]);
                    $message = "Profile picture updated!";
                    $msg_type = "success";
                } else {
                    $message = "Failed to move uploaded file.";
                    $msg_type = "error";
                }
            } else {
                $message = "File size must be less than 2MB.";
                $msg_type = "error";
            }
        } else {
            $message = "Invalid file type. Only JPG, PNG, and WEBP allowed.";
            $msg_type = "error";
        }
    }
}

// --- 2. HANDLE PERSONAL INFO UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $sql = "UPDATE tblinfo SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                contact = ?, 
                address = ?, 
                bdate = ?, 
                gender = ? 
                WHERE user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['contact'],
            $_POST['address'],
            $_POST['bdate'],
            $_POST['gender'],
            $user_id
        ]);

        $message = "Personal information updated successfully.";
        $msg_type = "success";
    } catch (Exception $e) {
        $message = "Error updating profile: " . $e->getMessage();
        $msg_type = "error";
    }
}

// --- 3. HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match.";
        $msg_type = "error";
    } elseif (strlen($new_pass) < 8) {
        $message = "Password must be at least 8 characters.";
        $msg_type = "error";
    } else {
        // Verify old password
        $stmt = $pdo->prepare("SELECT password FROM tbluser WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_pass, $user['password'])) {
            // Hash new password
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $update = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
            $update->execute([$new_hash, $user_id]);
            
            $message = "Password changed successfully.";
            $msg_type = "success";
        } else {
            $message = "Incorrect current password.";
            $msg_type = "error";
        }
    }
}

// --- 4. FETCH CURRENT DATA ---
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
$stmt->execute([$user_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// Default Avatar Logic
$avatar = !empty($info['image']) ? 'uploads/' . e($info['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($info['first_name'] . '+' . $info['last_name']) . '&background=7c3aed&color=fff';

?>