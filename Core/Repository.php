<?php

declare(strict_types=1);

namespace Core;

use PDO;

abstract class Repository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log actions to audit_logs table.
     */
    public function logAudit(string $action, string $table, ?int $targetId, ?array $old, ?array $new): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, target_table, target_id, old_values, new_values)
                VALUES (:user_id, :action, :table, :target_id, :old, :new)
            ");
            
            $stmt->execute([
                'user_id'   => Session::get('user_id'),
                'action'    => $action,
                'table'     => $table,
                'target_id' => $targetId,
                'old'       => $old ? json_encode($old) : null,
                'new'       => $new ? json_encode($new) : null
            ]);
        } catch (\Exception $e) {
            error_log("Audit Logging Failed: " . $e->getMessage());
        }
    }

    /**
     * Get revision history for a specific record.
     */
    public function getRevisionHistory(string $table, int $id, int $limit = 15): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username as responsible_user
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.target_table = :table AND a.target_id = :id
            ORDER BY a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':table', $table);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
