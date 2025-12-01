<?php
// settings.php - System settings page
require_once 'session_handler.php';
require_once 'security_helper.php'; 
require_once 'db.php';

// Require admin authentication
session_require_auth(['admin']);

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// --- Handle Form Submissions ---

// 1. Handle PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = htmlspecialchars(trim($_POST['first_name'] ?? ''));
    $last_name = htmlspecialchars(trim($_POST['last_name'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');

    // Normalize phone number
    $normalized_phone = normalize_phone_ph($phone); 

    if (empty($first_name) || empty($last_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !validate_phone_ph($normalized_phone)) {
        $error = "Please provide valid First Name, Last Name, Email, and Philippine Phone Number.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE tblinfo SET first_name = ?, last_name = ?, email = ?, contact = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $email, $normalized_phone, $user_id]);
            $success = "Profile information updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// 2. Handle PASSWORD CHANGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $password_validation = validate_password_strength($new_password);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (!$password_validation['valid']) {
        $error = "New password is too weak. " . implode(', ', $password_validation['errors']);
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM tbluser WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_new_password, $user_id]);
                $success = "Password changed successfully!";
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (Exception $e) {
            $error = "Error updating password: " . $e->getMessage();
        }
    }
}

// --- Get Admin Info ---
try {
    $stmt = $pdo->prepare("
        SELECT i.first_name, i.last_name, i.email, i.contact AS phone, u.user_name AS username
        FROM tblinfo i 
        JOIN tbluser u ON u.user_id = i.user_id
        WHERE i.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $admin_info = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage()); 
}

$admin_info = $admin_info ?: ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'username' => ''];
?>