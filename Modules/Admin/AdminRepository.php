<?php

declare(strict_types=1);

namespace Modules\Admin;

use Core\Repository;
use PDO;
use Exception;

class AdminRepository extends Repository
{
    /**
     * Get DB instance for backup.
     */
    public function getDb(): \PDO
    {
        return $this->db;
    }

    /**
     * Get system logs with advanced filtering and pagination.
     */
    public function getLogs(int $page = 1, int $limit = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $sortBy = $filters['sort_by'] ?? 'timestamp';
        $sortDir = $filters['sort_dir'] ?? 'DESC';
        
        // Allowed sort columns for security
        $allowedSort = ['timestamp', 'level', 'category', 'username', 'ip_address'];
        if (!in_array($sortBy, $allowedSort)) $sortBy = 'timestamp';
        if (!in_array(strtoupper($sortDir), ['ASC', 'DESC'])) $sortDir = 'DESC';

        $query = "SELECT l.*, u.username 
                  FROM system_logs l
                  LEFT JOIN users u ON l.user_id = u.id
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['level'])) {
            $query .= " AND l.level = :level";
            $params[':level'] = $filters['level'];
        }

        if (!empty($filters['category'])) {
            $query .= " AND l.category = :category";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND l.timestamp >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND l.timestamp <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $query .= " ORDER BY l.$sortBy $sortDir LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        // Total count
        $countQuery = "SELECT COUNT(*) FROM system_logs l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
        // Re-use logic for filters in count
        $filterPart = "";
        if (!empty($filters['level'])) $filterPart .= " AND l.level = :level";
        if (!empty($filters['category'])) $filterPart .= " AND l.category = :category";
        if (!empty($filters['date_from'])) $filterPart .= " AND l.timestamp >= :date_from";
        if (!empty($filters['date_to'])) $filterPart .= " AND l.timestamp <= :date_to";

        $countStmt = $this->db->prepare($countQuery . $filterPart);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Clear all logs and log the action itself.
     */
    public function clearLogs(): void
    {
        $this->db->exec("TRUNCATE TABLE system_logs");
        $this->logEvent('info', 'system', 'All system logs were cleared by administrator.');
    }

    /**
     * Centralized logging method.
     */
    public function logEvent(string $level, string $category, string $message, ?array $context = null): void
    {
        try {
            $userId = \Core\Session::get('user_id');
            $ip = \Core\Env::getClientIp();
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $stmt = $this->db->prepare("
                INSERT INTO system_logs (level, category, user_id, ip_address, user_agent, message, context)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bindValue(1, $level);
            $stmt->bindValue(2, $category);
            $stmt->bindValue(3, $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(4, $ip);
            $stmt->bindValue(5, $ua);
            $stmt->bindValue(6, $message);
            $stmt->bindValue(7, $context ? json_encode($context) : null, $context === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Logging Error: " . $e->getMessage());
        }
    }

    /**
     * Optimize all tables in the database.
     */
    public function optimizeDatabase(): array
    {
        $results = [];
        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $stmt = $this->db->query("OPTIMIZE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $results[] = [
                'table' => $table,
                'status' => $stmt['Msg_text'] ?? 'OK'
            ];
        }

        return $results;
    }

    /**
     * Get single log detail with username.
     */
    public function getLogDetail(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.username 
            FROM system_logs l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
