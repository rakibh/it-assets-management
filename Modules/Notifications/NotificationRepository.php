<?php

declare(strict_types=1);

namespace Modules\Notifications;

use Core\Repository;
use PDO;

class NotificationRepository extends Repository
{
    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM user_notifications 
            WHERE user_id = ? AND is_read = 0 AND is_acknowledged = 0
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get recent notifications for a user (dropdown view).
     */
    public function getRecent(int $userId, int $limit = 15): array
    {
        $stmt = $this->db->prepare("
            SELECT n.*, un.is_read, un.is_acknowledged, un.read_at
            FROM notifications n
            JOIN user_notifications un ON n.id = un.notification_id
            WHERE un.user_id = :user_id AND un.is_acknowledged = 0
            ORDER BY n.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all notifications for a user (paginated list view).
     */
    public function getAllPaginated(int $userId, int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $params = [':user_id' => $userId];
        
        // Default: Show non-acknowledged. If status is 'archived', show only acknowledged.
        $isArchived = (isset($filters['status']) && $filters['status'] === 'archived') ? 1 : 0;
        $where = "un.user_id = :user_id AND un.is_acknowledged = :is_archived";
        $params[':is_archived'] = $isArchived;

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'archived') {
            $where .= " AND un.is_read = :is_read";
            $params[':is_read'] = $filters['status'] === 'read' ? 1 : 0;
        }

        if (!empty($filters['date_from'])) {
            $where .= " AND n.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where .= " AND n.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        
        $stmt = $this->db->prepare("
            SELECT n.*, un.is_read, un.is_acknowledged, un.read_at
            FROM notifications n
            JOIN user_notifications un ON n.id = un.notification_id
            WHERE $where
            ORDER BY n.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM notifications n
            JOIN user_notifications un ON n.id = un.notification_id
            WHERE $where
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Bulk mark notifications as read or unread.
     */
    public function bulkMarkRead(int $userId, array $ids, bool $isRead): void
    {
        if (empty($ids)) return;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $readAt = $isRead ? 'NOW()' : 'NULL';
        
        $stmt = $this->db->prepare("
            UPDATE user_notifications 
            SET is_read = ?, read_at = " . ($isRead ? "NOW()" : "NULL") . "
            WHERE user_id = ? AND notification_id IN ($placeholders)
        ");
        
        $params = array_merge([$isRead ? 1 : 0, $userId], $ids);
        $stmt->execute($params);
    }

    /**
     * Create a new global notification for all active users.
     */
    public function createGlobal(string $type, string $message, string $priority = 'medium', ?array $data = null): void
    {
        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (type, message, priority, data) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$type, $message, $priority, $data ? json_encode($data) : null]);
            $notificationId = (int)$this->db->lastInsertId();

            // Link to all active users
            $this->db->prepare("
                INSERT INTO user_notifications (user_id, notification_id)
                SELECT id, ? FROM users WHERE status = 'active'
            ")->execute([$notificationId]);

            if ($useTransaction) $this->db->commit();
        } catch (\Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Create a notification for specific users.
     */
    public function createForUsers(array $userIds, string $type, string $message, string $priority = 'medium', ?array $data = null): void
    {
        if (empty($userIds)) return;

        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (type, message, priority, data) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$type, $message, $priority, $data ? json_encode($data) : null]);
            $notificationId = (int)$this->db->lastInsertId();

            $stmt = $this->db->prepare("INSERT INTO user_notifications (user_id, notification_id) VALUES (?, ?)");
            foreach ($userIds as $uid) {
                $stmt->execute([(int)$uid, $notificationId]);
            }

            if ($useTransaction) $this->db->commit();
        } catch (\Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $userId, int $notificationId): void
    {
        $stmt = $this->db->prepare("
            UPDATE user_notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND notification_id = ?
        ");
        $stmt->execute([$userId, $notificationId]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE user_notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Delete/Acknowledge a notification.
     */
    public function acknowledge(int $userId, int $notificationId): void
    {
        $stmt = $this->db->prepare("
            UPDATE user_notifications 
            SET is_acknowledged = 1, is_read = 1, read_at = IFNULL(read_at, NOW())
            WHERE user_id = ? AND notification_id = ?
        ");
        $stmt->execute([$userId, $notificationId]);
    }
}
