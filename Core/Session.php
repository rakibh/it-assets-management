<?php

declare(strict_types=1);

namespace Core;

class Session
{
    /**
     * Start the session with secure settings.
     *
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = (int)Env::get('SESSION_LIFETIME', 86400); // 24 hours default
            
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            session_start();
        }

        // Initialize CSRF token if not set
        if (!isset($_SESSION['csrf_token'])) {
            self::regenerateCsrfToken();
        }

        // Track activity
        if (isset($_SESSION['user_id'])) {
            self::checkTimeout();
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Check if session has timed out based on system settings.
     *
     * @return void
     */
    public static function checkTimeout(): void
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        // We can't easily access the repository here without circular dependency 
        // or static global access, so we'll fetch from a global helper or DB directly 
        // if we want to be very precise, but typically we store the timeout in session 
        // at login, or fetch it once. 
        // For simplicity and correctness, we'll use the value stored in the session 
        // which will be populated at login or first check.
        
        $timeoutMinutes = (int)self::get('session_timeout', 60);
        $timeoutSeconds = $timeoutMinutes * 60;

        if (time() - $_SESSION['last_activity'] > $timeoutSeconds) {
            self::destroy();
            header('Location: index.php?route=login&timeout=1');
            exit;
        }
    }

    /**
     * Regenerate CSRF token.
     *
     * @return void
     */
    public static function regenerateCsrfToken(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Validate CSRF token.
     *
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerate session ID (useful for login).
     *
     * @return void
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        self::regenerateCsrfToken();
    }

    /**
     * Set a session variable.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Destroy the session.
     *
     * @return void
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        session_destroy();
    }
}
