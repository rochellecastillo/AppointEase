<?php
// login.php - Hospital-themed Login Page with Security
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Redirect if already logged in
if (session_is_logged_in()) {
    $redirect_map = [
        'admin' => 'admin_home.php',
        'doctor' => 'doctor_home.php',
        'user' => 'client_home.php'
    ];
    $user_type = strtolower($_SESSION['user_type'] ?? 'user');
    header('Location: ' . ($redirect_map[$user_type] ?? 'index.php'));
    exit;
}

$error = '';
$success = '';

// Handle logout message
if (isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
}

if (isset($_GET['session_expired'])) {
    $error = 'Your session has expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $redirect = $_GET['redirect'] ?? '';
    // Rate limiting check
    $rate_check = check_rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 900);

    if (!$rate_check['allowed']) {
        $minutes = ceil($rate_check['retry_after'] / 60);
        $error = "Too many login attempts. Please try again in {$minutes} minutes.";
        log_security_event('login_rate_limit_exceeded', ['username' => $username]);
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT u.*, i.first_name, i.last_name 
                                  FROM tbluser u 
                                  LEFT JOIN tblinfo i ON u.user_id = i.user_id 
                                  WHERE u.user_name = ? AND u.status = 1 
                                  LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Check if password is hashed (starts with $2y$ for bcrypt)
                if (substr($user['password'], 0, 4) === '$2y$') {
                    // New hashed password
                    $password_valid = verify_password($password, $user['password']);
                } else {
                    // Legacy plain text password - validate and upgrade
                    $password_valid = ($password === $user['password']);
                    if ($password_valid) {
                        // Upgrade to hashed password
                        $new_hash = hash_password($password);
                        $update_stmt = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
                        $update_stmt->execute([$new_hash, $user['user_id']]);
                        log_security_event('password_upgraded_to_hash', ['user_id' => $user['user_id']]);
                    }
                }

                if ($password_valid) {
                    // Successful login
                    session_init_user($user);
                    log_security_event('login_success', [
                        'user_id' => $user['user_id'],
                        'user_type' => $user['user_type']
                    ]);

                    // Redirect based on user type
                    if (!empty($redirect) && filter_var($redirect, FILTER_VALIDATE_URL) === false) {
                        header('Location: ' . $redirect);
                    } else {
                        $redirect_map = [
                            'admin' => 'admin_home.php',
                            'doctor' => 'doctor_home.php',
                            'user' => 'client_home.php'
                        ];
                        $user_type = strtolower($user['user_type']);
                        header('Location: ' . ($redirect_map[$user_type] ?? 'index.php'));
                    }
                    exit;
                } else {
                    $error = 'Invalid username or password';
                    log_security_event('login_failed', ['username' => $username, 'reason' => 'invalid_password']);
                }
            } else {
                $error = 'Invalid username or password';
                log_security_event('login_failed', ['username' => $username, 'reason' => 'user_not_found']);
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again  later.';
            log_security_event('login_error', ['username' => $username, 'error' => $e->getMessage()]);
        }
    }
}
?>