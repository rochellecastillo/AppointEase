<?php
// security_helper.php - Password hashing and encryption utilities

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
 * Check if password needs rehashing (if algorithm or cost changed)
 */
// function password_needs_rehash($hash) {
//     return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
// }

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
 * Encrypt data using OpenSSL
 */
function encrypt_data($data, $key = null) {
    if ($key === null) {
        $key = getenv('ENCRYPTION_KEY') ?: 'default-key-change-this-in-production';
    }
    
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
    
    // Return base64 encoded: iv + encrypted data
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt data using OpenSSL
 */
function decrypt_data($encrypted_data, $key = null) {
    if ($key === null) {
        $key = getenv('ENCRYPTION_KEY') ?: 'default-key-change-this-in-production';
    }
    
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, $ivlen);
    $encrypted = substr($data, $ivlen);
    
    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
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
    
    // Optional: require special character
    // if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?]/', $password)) {
    //     $errors[] = "Password must contain at least one special character";
    // }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Rate limiting check (simple implementation)
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900) {
    // Use session for simple rate limiting
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
 * Log security event
 */
function log_security_event($event_type, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'user_id' => $_SESSION['user_id'] ?? 'guest',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    // Log to file
    $log_file = __DIR__ . '/logs/security_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    error_log(json_encode($log_entry) . PHP_EOL, 3, $log_file);
}

/**
 * Escape HTML output (alias for sanitize_output)
 */
function e($data) {
    return sanitize_output($data);
}
?>