<?php

declare(strict_types=1);

namespace Modules\Admin;

use Core\Session;
use Exception;

class SettingsController
{
    private SettingsRepository $settingsRepository;

    public function __construct()
    {
        $this->settingsRepository = new SettingsRepository();
    }

    /**
     * Display the settings page.
     */
    public function index(): array
    {
        if (Session::get('role') !== 'admin') {
            throw new Exception("Access denied. Admin only.");
        }

        return [
            'title' => 'System Settings',
            'view' => 'views/admin/settings.php',
            'data' => [
                'settings' => $this->settingsRepository->getAll()
            ]
        ];
    }

    /**
     * Save system settings (AJAX).
     */
    public function save(array $data): array
    {
        if (Session::get('role') !== 'admin') {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            // Validation
            if (isset($data['session_timeout']) && (int)$data['session_timeout'] < 30) {
                return ['success' => false, 'message' => 'Session timeout must be at least 30 minutes.'];
            }

            $this->settingsRepository->updateSettings($data);

            // Update current session if setting changed
            if (isset($data['session_timeout'])) {
                Session::set('session_timeout', $data['session_timeout']);
            }

            (new AdminRepository())->logEvent('info', 'system', 'System settings updated by administrator.');

            return ['success' => true, 'message' => 'Settings updated successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
