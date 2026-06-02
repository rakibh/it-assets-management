<?php

declare(strict_types=1);

namespace Modules\Auth;

use Core\Session;
use Exception;

class PreferencesController
{
    private PreferencesRepository $preferencesRepository;

    public function __construct()
    {
        $this->preferencesRepository = new PreferencesRepository();
    }

    /**
     * Display the preferences page.
     */
    public function index(): array
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            header('Location: index.php?route=login');
            exit;
        }

        $prefs = $this->preferencesRepository->getByUserId((int)$userId);
        
        // Decode JSON notification types
        if (isset($prefs['notification_types']) && is_string($prefs['notification_types'])) {
            $prefs['notification_types'] = json_decode($prefs['notification_types'], true) ?: [];
        } else {
            $prefs['notification_types'] = $prefs['notification_types'] ?? [];
        }

        return [
            'title' => 'Preferences',
            'view' => 'views/users/preferences.php',
            'data' => [
                'preferences' => $prefs,
                'timezones' => \DateTimeZone::listIdentifiers()
            ]
        ];
    }

    /**
     * Save user preferences (AJAX).
     */
    public function save(): array
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $success = $this->preferencesRepository->save((int)$userId, $data);
            
            if ($success) {
                // Update session for immediate effect
                Session::set('user_theme', $data['theme']);
                Session::set('user_timezone', $data['timezone']);
                Session::set('user_time_format', $data['time_format']);
                Session::set('user_toast_position', $data['toast_position']);
                Session::set('user_desktop_notifications', (int)$data['desktop_notifications']);
                Session::set('user_notification_types', json_encode($data['notification_types'] ?? []));
                
                (new \Modules\Admin\AdminRepository())->logEvent('info', 'user', 'User preferences updated.');

                return ['success' => true, 'message' => 'Preferences saved successfully.'];
            }
            
            return ['success' => false, 'message' => 'Failed to save preferences.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
