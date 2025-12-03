    <?php
    // session_handler.php - Stable & Secure Session Management

    if (session_status() === PHP_SESSION_NONE) {

        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        // Cookie settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        
        // If using localhost (no SSL), must NOT force secure cookie
        ini_set('session.cookie_secure', $is_https ? 1 : 0);

        // FIX: Switch SameSite from Strict → Lax (recommended)
        // Strict breaks redirects & login navigation
        ini_set('session.cookie_samesite', 'Lax');

        // Session lifetime
        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.cookie_lifetime', 86400);

        session_start();

        // Regenerate ID only once per 30 mins
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /** Initialize user session */
    function session_init_user($user_data) {
        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user_data['user_id'];
        $_SESSION['user_name'] = $user_data['user_name'];
        $_SESSION['user_type'] = $user_data['user_type'];

        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // FIX: Do NOT bind session to full IP (too risky)
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Create CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
    }

    /** Check if user is logged in */
    function session_is_logged_in() {
        return isset($_SESSION['logged_in']) &&
            $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_id']);
    }

    /** Check session timeout */
    function session_check_timeout($timeout = 1800) {
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > $timeout)) 
        {
            session_destroy_user();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    /** Validate session */
    function session_validate() {
        if (!session_is_logged_in()) return false;

        if (!session_check_timeout()) return false;

        // FIX: Validate by User-Agent only (stable)
        if (isset($_SESSION['user_agent']) &&
            $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')) 
        {
            session_destroy_user();
            return false;
        }

        return true;
    }

    /** Destroy user session */
    function session_destroy_user() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /** Require authentication */
    function session_require_auth($allowed_types = []) {
        if (!session_validate()) {
            header('Location: login.php?session_expired=1&redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }

        if (!empty($allowed_types)) {
            if (!in_array(strtolower($_SESSION['user_type'] ?? ''), 
                        array_map('strtolower', $allowed_types))) {
                header('Location: unauthorized.php');
                exit;
            }
        }
    }

    function session_get_user_id() { return $_SESSION['user_id'] ?? null; }
    function session_get_user_type() { return $_SESSION['user_type'] ?? null; }
    function session_get_username() { return $_SESSION['user_name'] ?? null; }

    ?>