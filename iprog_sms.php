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
} // <- properly close iprog_verify_otp here

/**
 * Send a regular SMS Notification
 * Endpoint: /sms/send (Standard iProg endpoint for custom messages)
 * @return array ['success'=>bool,'status'=>int,'body'=>...]
 */
function iprog_send_sms(string $phone, string $message): array {
    $phone_clean = iprog_normalize_phone($phone);
    // Use documented endpoint
    $url = IPROG_BASE . '/sms_messages';

    // form fields (this matches the documentation examples)
    $payload = http_build_query([
        'api_token'    => IPROG_API_TOKEN,
        'phone_number' => $phone_clean,
        'message'      => $message
        // 'sender_id' => 'AppointEase' // include only if your account requires it
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    // use application/x-www-form-urlencoded as many gateways accept this reliably
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Logging
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/sms.log';
    $logEntry = [
        'ts' => date('c'),
        'url' => $url,
        'payload' => [
            'api_token' => '***REDACTED***',
            'phone_number' => $phone_clean,
            'message' => mb_substr($message, 0, 200)
        ],
        'http_code' => $http,
        'curl_error' => $err ?: null,
        'response_raw' => $resp,
    ];
    @file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

    if ($err) {
        error_log("[AppointEase] iprog_send_sms curl error: $err");
        return ['success' => false, 'status' => 0, 'body' => $err, 'log' => $logEntry];
    }

    $parsed = json_decode($resp, true);
    $ok = false;

    // Provider-specific success detection (adjustable)
    if (is_array($parsed)) {
        if ((isset($parsed['status']) && (strtolower((string)$parsed['status']) === 'success' || (int)$parsed['status'] === 200))
            || (isset($parsed['data']) && isset($parsed['data']['message_id']))
            || (isset($parsed['message']) && stripos($parsed['message'], 'sent') !== false)
        ) {
            $ok = true;
        }
    }

    // Fallback: HTTP 2xx -> success
    if (!$ok && $http >= 200 && $http < 300) $ok = true;

    return ['success' => $ok, 'status' => $http, 'body' => $parsed ?? $resp, 'log' => $logEntry];
}