<?php
// security_helper.php - Password hashing and encryption utilities + CSRF helpers

/**
 * Hash password using PHP's password_hash (bcrypt)
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize input for HTML output (prevent XSS)
 */
function sanitize_output($data) {
    if (is_array($data)) {
        return array_map('sanitize_output', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape HTML output (alias)
 */
function e($data) {
    return sanitize_output($data);
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Philippine mobile number
 */
function validate_phone_ph($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Accept formats: 09XXXXXXXXX or 639XXXXXXXXX
    if (preg_match('/^(09|639)[0-9]{9}$/', $phone)) {
        return true;
    }

    return false;
}

/**
 * Normalize Philippine phone number to 09XXXXXXXXX format
 */
function normalize_phone_ph($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Convert 639XXXXXXXXX to 09XXXXXXXXX
    if (substr($phone, 0, 2) === '63' && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    }

    // Ensure it starts with 0
    if (substr($phone, 0, 1) !== '0' && strlen($phone) === 10) {
        $phone = '0' . $phone;
    }

    return $phone;
}

/**
 * Generate secure random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Encryption key helper - read from environment, warn if default
 */
function _get_encryption_key() {
    $key = getenv('ENCRYPTION_KEY') ?: null;
    // In development, you might have no key; in production, ensure the env var is set.
    if (php_sapi_name() !== 'cli' && (empty($key) || $key === 'default-key-change-this-in-production')) {
        error_log('[SEC] WARNING - ENCRYPTION_KEY not set or uses default value.');
        // don't exit here: fallback exists for dev, but ensure you set ENCRYPTION_KEY in production
    }
    return $key ?? 'default-key-change-this-in-production';
}

/**
 * Encrypt data using OpenSSL
 */
function encrypt_data($data, $key = null) {
    if ($key === null) {
        $key = _get_encryption_key();
    }

    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);

    $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    // Return base64 encoded: iv + encrypted data
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt data using OpenSSL
 */
function decrypt_data($encrypted_data, $key = null) {
    if ($key === null) {
        $key = _get_encryption_key();
    }

    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);

    $data = base64_decode($encrypted_data);
    if ($data === false || strlen($data) <= $ivlen) return false;

    $iv = substr($data, 0, $ivlen);
    $encrypted = substr($data, $ivlen);

    return openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Validate password strength
 */
function validate_password_strength($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Rate limiting check (simple implementation)
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900) {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $now = time();
    $key = 'rate_' . md5($identifier);

    // Clean old entries
    if (isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            function($timestamp) use ($now, $time_window) {
                return ($now - $timestamp) < $time_window;
            }
        );
    } else {
        $_SESSION['rate_limit'][$key] = [];
    }

    // Check if limit exceeded
    if (count($_SESSION['rate_limit'][$key]) >= $max_attempts) {
        return [
            'allowed' => false,
            'retry_after' => $time_window - ($now - min($_SESSION['rate_limit'][$key]))
        ];
    }

    // Add current attempt
    $_SESSION['rate_limit'][$key][] = $now;

    return [
        'allowed' => true,
        'attempts_remaining' => $max_attempts - count($_SESSION['rate_limit'][$key])
    ];
}

/**
 * Log security event (optional - uncomment and use if you want file logging)
 */
/*
function log_security_event($event_type, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'user_id' => $_SESSION['user_id'] ?? 'guest',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];

    $log_file = __DIR__ . '/logs/security_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    error_log(json_encode($log_entry) . PHP_EOL, 3, $log_file);
}
*/

/* ----- CSRF helpers ----- */

/**
 * Return CSRF token (create if absent)
 */
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Echo hidden input for forms
 */
function csrf_field() {
    $t = csrf_token();
    return '<input type="hidden" name="_csrf_token" value="'.htmlspecialchars($t, ENT_QUOTES, 'UTF-8').'">';
}

/**
 * Server-side validation (call at top of POST-processing scripts)
 */
function verify_csrf_or_die() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        error_log('[SEC] CSRF validation failed for IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        die('Invalid CSRF token');
    }
}
?>
