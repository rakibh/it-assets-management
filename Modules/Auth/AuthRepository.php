<?php

declare(strict_types=1);

namespace Modules\Auth;

use Core\Repository;
use PDO;

class AuthRepository extends Repository
{
    /**
     * Find a user by username or employee ID.
     *
     * @param string $identifier
     * @return array|null
     */
    public function findUserByIdentifier(string $identifier): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE (username = :id1 OR employee_id = :id2) 
            AND status = 'active' 
            LIMIT 1
        ");
        
        $stmt->execute([
            'id1' => $identifier,
            'id2' => $identifier
        ]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    /**
     * Log a failed login attempt for rate limiting.
     *
     * @param string $identifier
     * @param string $ipAddress
     * @return void
     */
    public function logFailedAttempt(string $identifier, string $ipAddress): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO system_logs (level, category, message, ip_address, context) 
            VALUES ('warning', 'security', 'Failed login attempt', :ip, :context)
        ");
        
        $stmt->execute([
            'ip' => $ipAddress,
            'context' => json_encode([
                'identifier' => $identifier,
                'timestamp' => date('Y-m-d H:i:s')
            ])
        ]);
    }

    /**
     * Count failed attempts for an IP or identifier in the last 10 minutes.
     *
     * @param string $identifier
     * @param string $ipAddress
     * @return int
     */
    public function countFailedAttempts(string $identifier, string $ipAddress): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM system_logs 
            WHERE category = 'security' 
            AND level = 'warning'
            AND message = 'Failed login attempt'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            AND (JSON_EXTRACT(context, '$.identifier') = :identifier OR ip_address = :ip)
        ");
        
        $stmt->execute([
            'identifier' => $identifier,
            'ip' => $ipAddress
        ]);
        
        return (int)$stmt->fetchColumn();
    }
}
