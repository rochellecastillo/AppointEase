<?php
// forgot_password.php - UI matched with Signup & linked to Universal OTP
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'iprog_sms.php';

// Redirect if already logged in
if (session_is_logged_in()) {
    header('Location: ' . (session_get_user_type() === 'admin' ? 'admin_home.php' :
           (session_get_user_type() === 'doctor' ? 'doctor_home.php' : 'client_home.php')));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'request_reset') {
        $username_or_contact = trim($_POST['username_or_contact'] ?? '');

        // Rate limiting
        $rate_check = check_rate_limit('forgot_password_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 3, 900);

        if (!$rate_check['allowed']) {
            $minutes = ceil($rate_check['retry_after'] / 60);
            $error = "Too many attempts. Please try again in {$minutes} minutes.";
            log_security_event('forgot_password_rate_limit', ['input' => $username_or_contact]);
        } elseif (empty($username_or_contact)) {
            $error = 'Please enter your username or contact number';
        } else {
            try {
                // Search for user
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.user_name, i.first_name, i.last_name, i.contact
                    FROM tbluser u
                    LEFT JOIN tblinfo i ON u.user_id = i.user_id
                    WHERE (u.user_name = ? OR i.contact = ?) AND u.status = 1
                    LIMIT 1
                ");

                $normalized_contact = normalize_phone_ph($username_or_contact);
                $stmt->execute([$username_or_contact, $normalized_contact]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && !empty($user['contact'])) {

                    // 1. Prepare Universal Session Data
                    $_SESSION['otp_action'] = 'forgot_password';
                    $_SESSION['otp_payload'] = [
                        'user_id' => $user['user_id'],
                        'user_name' => $user['user_name'],
                        'contact' => $user['contact'], // Required for Universal OTP
                        'otp_expires' => time() + (5 * 60)
                    ];

                    // 2. Send OTP
                    $res = iprog_send_otp($user['contact'], null);

                    if (!$res['success']) {
                        error_log("[AppointEase] Forgot Password OTP failed: " . print_r($res, true));
                        $error = "Failed to send OTP. Please try again later.";
                        unset($_SESSION['otp_action']);
                        unset($_SESSION['otp_payload']);
                    } else {
                        // 3. Redirect to Universal OTP Page
                        header('Location: verify_otp.php');
                        exit;
                    }
                } else {
                    // Security: Fake delay to prevent enumeration
                    sleep(2);
                    $error = "If this account exists, we will send a verification code.";
                }
            } catch (Exception $e) {
                $error = "An system error occurred. Please try again.";
                error_log($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Forgot Password - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .bg-pattern { background-color: #ffffff; background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
        .input-field:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .field-error { color: #b91c1c; font-size: 0.875rem; margin-top: 0.375rem; display:none; }
        .input-error { border-color: #fca5a5 !important; background-color: #fff7f7; }
        .btn-spinner { width: 18px; height: 18px; margin-left: 8px; display: inline-block; vertical-align: middle; }
        /* Toast */
        .toast { position: fixed; right: 1rem; bottom: 1rem; z-index: 60; min-width: 220px; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-pattern">

    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden grid md:grid-cols-2 min-h-[500px]">
        <div class="hidden md:flex flex-col justify-between p-12 bg-gradient-to-br from-blue-600 to-indigo-700 text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full translate-x-1/2 translate-y-1/2 blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i data-lucide="shield-question" class="w-8 h-8 text-white"></i>
                    </div>
                    <span class="text-xl font-bold tracking-wide">Recovery</span>
                </div>

                <h1 class="text-3xl font-bold leading-tight mb-4">Forgot Password?</h1>
                <p class="text-blue-100 text-lg leading-relaxed mb-6">Don't worry! It happens. Please enter the address associated with your account.</p>

                <div class="bg-white/10 p-4 rounded-xl border border-white/20 backdrop-blur-sm">
                    <div class="flex items-start space-x-3">
                        <i data-lucide="lock" class="w-5 h-5 mt-1 text-blue-200"></i>
                        <p class="text-sm text-blue-50">We will send a One-Time Password (OTP) to your registered mobile number for verification.</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-auto text-sm text-blue-200">
                Remember your password? <a href="login.php" class="text-white font-semibold underline hover:text-blue-100">Sign in</a>
            </div>
        </div>

        <div class="p-8 md:p-12 flex flex-col justify-center">
            <div class="md:hidden mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800">AppointEase</h2>
                <p class="text-gray-500">Account Recovery</p>
            </div>

            <div class="w-full">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Reset Password</h2>
                    <p class="text-gray-500 mt-2">Enter your username or mobile number.</p>
                </div>

                <!-- server message placeholder (we will use JS to fade it) -->
                <?php if ($error): ?>
                    <div id="server-message" class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg" role="alert" aria-live="polite">
                        <div class="flex items-start">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0"></i>
                            <span class="text-sm text-red-700"><?= htmlspecialchars($error) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="forgotForm" method="POST" action="" class="space-y-6" novalidate>
                    <input type="hidden" name="action" value="request_reset">

                    <div>
                        <label for="username_or_contact" class="block text-sm font-medium text-gray-700 mb-1">Username or Mobile Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <input id="username_or_contact" name="username_or_contact" type="text" required autofocus
                                value="<?= htmlspecialchars($_POST['username_or_contact'] ?? '') ?>"
                                placeholder="Enter username or 09XXXXXXXXX"
                                class="input-field w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none transition-colors"
                                aria-describedby="fieldError" />
                            <div id="fieldError" class="field-error" role="alert" aria-live="assertive"></div>
                        </div>
                    </div>

                    <button id="submitBtn" type="submit"
                        class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-200 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform active:scale-[0.98]"
                        aria-live="polite">
                        <span id="btnText">Send Reset Code</span>
                        <svg id="btnSpinner" class="btn-spinner hidden" viewBox="0 0 50 50" aria-hidden="true">
                            <circle cx="25" cy="25" r="20" fill="none" stroke="white" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.4 31.4"></circle>
                        </svg>
                        <i data-lucide="arrow-right" class="ml-2 w-4 h-4"></i>
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <a href="login.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- toast container -->
    <div id="toastContainer" class="toast"></div>

    <script>
    // Client-side enhancements for forgot_password.php
    (function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const form = document.getElementById('forgotForm');
        const input = document.getElementById('username_or_contact');
        const fieldError = document.getElementById('fieldError');
        const submitBtn = document.getElementById('submitBtn');
        const btnSpinner = document.getElementById('btnSpinner');
        const btnText = document.getElementById('btnText');
        const serverMessage = document.getElementById('server-message');
        const toastContainer = document.getElementById('toastContainer');

        // Utilities
        function normalizePhonePH(raw) {
            if (!raw) return '';
            let v = String(raw).trim();
            // Remove spaces, dashes, parentheses
            v = v.replace(/[\s\-\(\)]/g, '');
            // Remove leading + if present
            if (v.startsWith('+')) v = v.slice(1);
            // If starts with '63' and followed by 9xx -> convert to 09xx...
            if (/^63(9\d{9})$/.test(v)) {
                return '0' + v.slice(2);
            }
            // If starts with '9' and 10 digits total -> add leading 0
            if (/^9\d{9}$/.test(v)) {
                return '0' + v;
            }
            // If starts with '09' and length 11 it's ok
            if (/^09\d{9}$/.test(v)) {
                return v;
            }
            // otherwise return original trimmed
            return v;
        }

        function isLikelyPhone(v) {
            const n = normalizePhonePH(v);
            return /^09\d{9}$/.test(n);
        }

        function showFieldError(msg) {
            fieldError.textContent = msg;
            fieldError.style.display = 'block';
            input.classList.add('input-error');
            input.setAttribute('aria-invalid', 'true');
            input.focus();
        }

        function clearFieldError() {
            fieldError.textContent = '';
            fieldError.style.display = 'none';
            input.classList.remove('input-error');
            input.removeAttribute('aria-invalid');
        }

        function showToast(message, type = 'info', timeout = 4000) {
            const el = document.createElement('div');
            el.className = 'bg-white p-3 rounded-xl shadow-lg border flex items-start gap-3 mb-3';
            if (type === 'error') el.classList.add('border-red-200');
            el.innerHTML = `
                <div class="flex-shrink-0">
                    ${ type === 'error' ? '<i data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>' : '<i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>' }
                </div>
                <div class="text-sm text-gray-700">${message}</div>
            `;
            toastContainer.appendChild(el);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            setTimeout(() => {
                el.style.transition = 'opacity 300ms ease, transform 300ms ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(8px)';
                setTimeout(() => el.remove(), 320);
            }, timeout);
        }

        // If server returned a message, fade it after 5s and also show a toast (so it doesn't disappear abruptly)
        if (serverMessage) {
            setTimeout(() => {
                try { // mirror the server message into a toast for consistent UI
                    const text = serverMessage.innerText || serverMessage.textContent;
                    if (text && text.trim()) showToast(text.trim(), 'error', 5000);
                } catch (e) {}
                serverMessage.style.transition = 'opacity 600ms ease';
                serverMessage.style.opacity = '0';
                setTimeout(() => serverMessage.remove(), 650);
            }, 4200);
        }

        // Prevent double submit & show spinner
        let submitting = false;
        form.addEventListener('submit', function (ev) {
            clearFieldError();

            const val = input.value.trim();

            if (!val) {
                ev.preventDefault();
                showFieldError('Please enter your username or mobile number.');
                return;
            }

            // If it looks like a phone, normalize and overwrite the input value so server receives normalized form
            if (isLikelyPhone(val)) {
                input.value = normalizePhonePH(val);
            }

            // Simple client-side sanitization/checks (username can be alnum + symbols, so we only enforce not-empty)
            // Prevent double-submits
            if (submitting) {
                ev.preventDefault();
                return;
            }

            // UI: set button to busy
            submitting = true;
            submitBtn.disabled = true;
            btnSpinner.classList.remove('hidden');
            btnText.textContent = 'Sending...';

            // Let the form submit normally — server will redirect or return HTML
            // As a fallback, re-enable after 10s if no response (network issue)
            setTimeout(function () {
                if (submitting) {
                    submitting = false;
                    submitBtn.disabled = false;
                    btnSpinner.classList.add('hidden');
                    btnText.textContent = 'Send Reset Code';
                    showToast('The request is taking longer than expected. Please check your connection.', 'error', 5000);
                }
            }, 10000);
        });

        // Input tweaks
        input.addEventListener('input', function () {
            clearFieldError();
        });

        // Helpful hint: format phone while typing (non-intrusive)
        let typingTimer;
        input.addEventListener('keyup', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                const raw = input.value.trim();
                // only attempt to normalize if it starts with +63/63/09/9 and contains digits
                if (/^(\+?63|0?9|9)/.test(raw)) {
                    const normalized = normalizePhonePH(raw);
                    // if normalized is 11-digit 09..., show it but don't overwrite if user typed a username (we detect by presence of letters)
                    if (/^09\d{9}$/.test(normalized) && !/[A-Za-z]/.test(raw)) {
                        input.value = normalized;
                    }
                }
            }, 500);
        });

        // Accessibility: focus input on load
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            input.focus();
        } else {
            window.addEventListener('load', () => input.focus());
        }

        // Re-render icons if any dynamic content was added
        if (typeof lucide !== 'undefined') lucide.createIcons();
    })();
    </script>
</body>
</html>
