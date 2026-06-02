<?php

declare(strict_types=1);

namespace Modules\Auth;

use Core\Session;
use Core\Env;

class LoginController
{
    private AuthRepository $authRepository;

    public function __construct()
    {
        $this->authRepository = new AuthRepository();
    }

    /**
     * Handle login request.
     *
     * @param array $data POST data
     * @return array Response with success and message
     */
    public function login(array $data): array
    {
        // 1. Validate CSRF
        if (!isset($data['csrf_token']) || !Session::validateCsrfToken($data['csrf_token'])) {
            return ['success' => false, 'message' => 'Security token invalid. Please refresh.'];
        }

        // 2. Validate Input
        $identifier = trim($data['identifier'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'message' => 'Please fill in all fields.'];
        }

        // 3. Rate Limiting Check
        $ip = \Core\Env::getClientIp();
        if ($this->authRepository->countFailedAttempts($identifier, $ip) >= 5) {
            return ['success' => false, 'message' => 'Too many failed attempts. Please try again after 10 minutes.'];
        }

        // 4. Find User
        $user = $this->authRepository->findUserByIdentifier($identifier);

        // 5. Verify Password
        $adminRepo = new \Modules\Admin\AdminRepository();
        if ($user && password_verify($password, $user['password'])) {
            // Success: Secure Session
            Session::regenerate();
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('role', $user['role']);
            Session::set('force_pw', (bool)$user['force_password_change']);
            
            // Store session timeout from settings
            $settingsRepo = new \Modules\Admin\SettingsRepository();
            $timeout = $settingsRepo->get('session_timeout', '60');
            Session::set('session_timeout', $timeout);

            // Store user preferences
            $prefsRepo = new \Modules\Auth\PreferencesRepository();
            $prefs = $prefsRepo->getByUserId((int)$user['id']);
            Session::set('user_theme', $prefs['theme'] ?? 'light');
            Session::set('user_timezone', $prefs['timezone'] ?? 'Asia/Dhaka');
            Session::set('user_time_format', $prefs['time_format'] ?? '12');
            Session::set('user_toast_position', $prefs['toast_position'] ?? 'bottom-right');
            Session::set('user_desktop_notifications', (bool)($prefs['desktop_notifications'] ?? 0));
            Session::set('user_notification_types', $prefs['notification_types'] ?? '[]');

            $adminRepo->logEvent('info', 'auth', "User logged in: {$user['username']}", [
                'user_id' => $user['id'],
                'role' => $user['role']
            ]);

            return [
                'success' => true, 
                'redirect' => $user['force_password_change'] ? 'index.php?route=profile&force=1' : 'index.php?route=dashboard'
            ];
        }

        // 6. Failure: Log attempt & generic error
        $this->authRepository->logFailedAttempt($identifier, $ip);
        $adminRepo->logEvent('warning', 'security', "Failed login attempt for identifier: $identifier", [
            'identifier' => $identifier,
            'ip' => $ip
        ]);
        
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    /**
     * Handle logout.
     *
     * @return void
     */
    public function logout(): void
    {
        $adminRepo = new \Modules\Admin\AdminRepository();
        $username = Session::get('username');
        if ($username) {
            $adminRepo->logEvent('info', 'auth', "User logged out: $username");
        }
        Session::destroy();
        header('Location: index.php?route=login');
        exit;
    }
}
