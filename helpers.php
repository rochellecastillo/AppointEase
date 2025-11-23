<?php
// helpers.php - shared helpers for AppointmentEase admin
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php'; // ensures $pdo is available

if (!function_exists('e')) {
    function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('normalize_phone')) {
    function normalize_phone($phone){
        $p = preg_replace('/[^0-9]/','',$phone);
        if (substr($p,0,2) === '63') $p = '0' . substr($p,2);
        if (substr($p,0,1) !== '0') $p = '0' . $p;
        return $p;
    }
}

if (!function_exists('generate_user_id')) {
    function generate_user_id($pdo) {
        $base = 'U' . date('ymd');
        $i = 1;
        while (true) {
            $uid = $base . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT 1 FROM tblinfo WHERE user_id = ? LIMIT 1");
            $stmt->execute([$uid]);
            if (!$stmt->fetch()) return $uid;
            $i++;
            if ($i > 9999) throw new Exception("Failed to generate user_id");
        }
    }
}

// CSRF helpers
if (!function_exists('ensure_csrf')) {
    function ensure_csrf() {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 1800) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validate_csrf')) {
    function validate_csrf($token) {
        if(empty($token) || !isset($_SESSION['csrf_token'])) return false;
        if(!isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 1800) {
            unset($_SESSION['csrf_token']); unset($_SESSION['csrf_token_time']);
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Admin check
if (!function_exists('require_admin')) {
    function require_admin() {
        if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin'){
            header('Location: login.php');
            exit;
        }
    }
}

// Optional helper for doctor requirement
if (!function_exists('require_doctor')) {
    function require_doctor() {
        if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor'){
            header('Location: dashboard.php');
            exit;
        }
    }
}
