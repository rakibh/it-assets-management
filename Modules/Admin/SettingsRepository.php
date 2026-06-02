<?php

declare(strict_types=1);

namespace Modules\Admin;

use Core\Repository;
use PDO;

class SettingsRepository extends Repository
{
    /**
     * Get all system settings as key-value pairs.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM system_settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Update multiple settings.
     */
    public function updateSettings(array $settings): bool
    {
        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($settings as $key => $value) {
                // Skip sensitive or non-setting keys
                if ($key === 'csrf_token') continue;
                $stmt->execute([$key, $value]);
            }

            if ($useTransaction) $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get a single setting value.
     */
    public function get(string $key, $default = null): ?string
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    }
}
