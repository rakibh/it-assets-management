<?php

declare(strict_types=1);

namespace Modules\Auth;

use Core\Repository;
use PDO;

class PreferencesRepository extends Repository
{
    /**
     * Get preferences for a specific user.
     */
    public function getByUserId(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prefs) {
                return $this->getDefaults($userId);
            }

            return $prefs;
        } catch (\PDOException $e) {
            // Table might not exist yet, return defaults
            return $this->getDefaults($userId);
        }
    }

    /**
     * Save or update user preferences.
     */
    public function save(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_preferences (user_id, theme, timezone, time_format, desktop_notifications, notification_types, toast_position)
            VALUES (:user_id, :theme, :timezone, :time_format, :desktop_notifications, :notification_types, :toast_position)
            ON DUPLICATE KEY UPDATE 
                theme = VALUES(theme),
                timezone = VALUES(timezone),
                time_format = VALUES(time_format),
                desktop_notifications = VALUES(desktop_notifications),
                notification_types = VALUES(notification_types),
                toast_position = VALUES(toast_position)
        ");

        return $stmt->execute([
            'user_id' => $userId,
            'theme' => $data['theme'] ?? 'light',
            'timezone' => $data['timezone'] ?? 'Asia/Dhaka',
            'time_format' => $data['time_format'] ?? '12',
            'desktop_notifications' => isset($data['desktop_notifications']) ? (int)$data['desktop_notifications'] : 0,
            'notification_types' => isset($data['notification_types']) ? json_encode($data['notification_types']) : null,
            'toast_position' => $data['toast_position'] ?? 'top-right'
        ]);
    }

    /**
     * Default preferences.
     */
    private function getDefaults(int $userId): array
    {
        return [
            'user_id' => $userId,
            'theme' => 'light',
            'timezone' => 'Asia/Dhaka',
            'time_format' => '12',
            'desktop_notifications' => 0,
            'notification_types' => null,
            'toast_position' => 'top-right'
        ];
    }
}
