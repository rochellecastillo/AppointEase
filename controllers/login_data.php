<?php
// login.php - updated to cooperate with session_handler.php (SameSite=None)
// Place this file in your web root and include session_handler.php earlier in the request.

require_once 'session_handler.php';
require_once 'db.php';         
require_once 'security_helper.php';
require_once 'logging_helper.php';

// If already logged in -> send to dashboard immediately
if (session_is_logged_in()) {
    $redirect_map = [
        'admin'  => 'admin_home.php',
        'doctor' => 'doctor_home.php',
        'user'   => 'client_home.php'
    ];
    $user_type = strtolower($_SESSION['user_type'] ?? 'user');
    header('Location: ' . ($redirect_map[$user_type] ?? 'index.php'));
    exit;
}

$error = '';
$success = '';

// Show logout message if present
if (isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
}

// Show session expired message if redirected
if (isset($_GET['session_expired'])) {
    $error = 'Your session has expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';

    // Simple validation
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT u.*, i.first_name, i.last_name FROM tbluser u LEFT JOIN tblinfo i ON u.user_id = i.user_id WHERE u.user_name = ? AND u.status = 1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // determine password validity
                $password_valid = false;
                if (isset($user['password']) && strpos($user['password'], '$2y$') === 0) {
                    $password_valid = verify_password($password, $user['password']);
                } else {
                    // legacy plain text fallback (upgrade)
                    $password_valid = ($password === $user['password']);
                    if ($password_valid) {
                        $new_hash = hash_password($password);
                        $u = $pdo->prepare("UPDATE tbluser SET password = ? WHERE user_id = ?");
                        $u->execute([$new_hash, $user['user_id']]);
                    }
                }

                if ($password_valid) {
                    // login success
                    session_init_user($user);
                    log_security_event('login_success', ['user_id' => $user['user_id']]);

                    // Redirect to provided redirect OR role-based dashboard
                    if (!empty($redirect) && filter_var($redirect, FILTER_VALIDATE_URL) === false) {
                        header('Location: ' . $redirect);
                        exit;
                    }

                    $redirect_map = [
                        'admin'  => 'admin_home.php',
                        'doctor' => 'doctor_home.php',
                        'user'   => 'client_home.php'
                    ];
                    $user_type = strtolower($user['user_type'] ?? 'user');
                    header('Location: ' . ($redirect_map[$user_type] ?? 'index.php'));
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
            $error = 'An internal error occurred. Please try again later.';
            log_security_event('login_error', ['username' => $username, 'error' => $e->getMessage()]);
        }
    }
}
?>