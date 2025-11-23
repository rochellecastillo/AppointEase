<?php
// iprog_sms.php
// iProgSMS helper (uses token you provided)
// Docs: https://sms.iprogtech.com/api/v1/documentation

// !!! Replace token here if you rotate it later !!!
define('IPROG_API_TOKEN', trim('9a248d49b141d8f924b752e2aa8fef6bb5608724'));
define('IPROG_BASE', 'https://sms.iprogtech.com/api/v1');

if(empty(IPROG_API_TOKEN)){
    error_log("[AppointmentEase] iProg API token not set in iprog_sms.php");
}

/**
 * Normalize phone: convert +63... or 63... to 09... (iProg uses local 0917... format)
 */
function iprog_normalize_phone(string $phone): string {
    $p = preg_replace('/[^0-9]/', '', $phone);
    if (substr($p, 0, 2) === '63') {
        $p = '0' . substr($p, 2);
    }
    if (substr($p, 0, 1) !== '0') {
        $p = '0' . $p;
    }
    return $p;
}

/**
 * Send OTP (provider generates code)
 * @return array ['success'=>bool, 'status'=>int, 'body'=>array|string]
 */
function iprog_send_otp(string $phone, ?string $message = null): array {
    $phone_clean = iprog_normalize_phone($phone);
    $url = IPROG_BASE . '/otp/send_otp';
    $payload = [
        'api_token' => IPROG_API_TOKEN,
        'phone_number' => $phone_clean
    ];
    if ($message !== null) $payload['message'] = $message; // optional custom message; use :otp placeholder

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        error_log("[AppointmentEase] iprog_send_otp curl error: $err");
        return ['success' => false, 'status' => 0, 'body' => $err];
    }

    $parsed = json_decode($resp, true);
    $ok = false;
    if (is_array($parsed) && isset($parsed['status'])) {
        if ((is_string($parsed['status']) && strtolower($parsed['status']) === 'success') ||
            (is_int($parsed['status']) && $parsed['status'] === 200) ||
            (is_string($parsed['status']) && (int)$parsed['status'] === 200)
        ) {
            $ok = true;
        }
    }

    return ['success' => $ok, 'status' => $http, 'body' => $parsed ?? $resp];
}

/**
 * Verify OTP
 * @return array ['success'=>bool,'status'=>int,'body'=>...]
 */
function iprog_verify_otp(string $phone, string $otp): array {
    $phone_clean = iprog_normalize_phone($phone);
    $url = IPROG_BASE . '/otp/verify_otp';
    $payload = [
        'api_token' => IPROG_API_TOKEN,
        'phone_number' => $phone_clean,
        'otp' => $otp
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        error_log("[AppointmentEase] iprog_verify_otp curl error: $err");
        return ['success' => false, 'status' => 0, 'body' => $err];
    }

    $parsed = json_decode($resp, true);
    $ok = false;
    if (is_array($parsed) && isset($parsed['status'])) {
        if ((is_string($parsed['status']) && strtolower($parsed['status']) === 'success') ||
            (is_int($parsed['status']) && $parsed['status'] === 200) ||
            (isset($parsed['message']) && stripos($parsed['message'], 'verified') !== false)
        ) {
            $ok = true;
        }
    }

    return ['success' => $ok, 'status' => $http, 'body' => $parsed ?? $resp];
}
