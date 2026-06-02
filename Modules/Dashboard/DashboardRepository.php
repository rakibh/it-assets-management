<?php

declare(strict_types=1);

namespace Modules\Dashboard;

use Core\Repository;
use PDO;
use Exception;

class DashboardRepository extends Repository
{
    /**
     * Get summary statistics for the dashboard.
     */
    public function getStats(int $userId, string $role): array
    {
        $taskRepo = new \Modules\Tasks\TaskRepository();
        $taskRepo->checkDeadlines();

        $equipmentRepo = new \Modules\Equipment\EquipmentRepository();
        $equipmentRepo->checkWarranties();

        $stats = [
            'total_equipment' => 0,
            'active_tasks' => 0,
            'past_due_tasks' => 0,
            'network_nodes' => 0
        ];

        try {
            // 1. Total Equipment
            $stats['total_equipment'] = (int)$this->db->query("SELECT COUNT(*) FROM equipments")->fetchColumn();

            // 2. Active Tasks
            if ($role === 'admin') {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE status NOT IN ('done', 'dropped')");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM tasks t
                    JOIN task_assignees ta ON t.id = ta.task_id
                    WHERE t.status NOT IN ('done', 'dropped') AND ta.user_id = ?
                ");
                $stmt->execute([$userId]);
            }
            $stats['active_tasks'] = (int)$stmt->fetchColumn();

            // 3. Past Due Tasks
            if ($role === 'admin') {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE status NOT IN ('done', 'dropped') AND deadline < CURDATE()");
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM tasks t
                    JOIN task_assignees ta ON t.id = ta.task_id
                    WHERE t.status NOT IN ('done', 'dropped') AND t.deadline < CURDATE() AND ta.user_id = ?
                ");
                $stmt->execute([$userId]);
            }
            $stats['past_due_tasks'] = (int)$stmt->fetchColumn();

            // 4. Network Nodes
            $stats['network_nodes'] = (int)$this->db->query("SELECT COUNT(*) FROM network_info")->fetchColumn();
        } catch (Exception $e) {
            // Log error but return partial stats to avoid dashboard crash
            error_log("Dashboard Stats Error: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get data for charts.
     */
    public function getChartData(): array
    {
        $data = [
            'equipment_status' => [
                'Available' => 0,
                'In Use' => 0,
                'Under Repair' => 0,
                'Retired' => 0,
                'Lost/Stolen' => 0
            ],
            'tasks_status' => [
                'todo' => 0,
                'doing' => 0,
                'past_due' => 0,
                'done' => 0,
                'dropped' => 0
            ]
        ];

        try {
            $equipmentResults = $this->db->query("
                SELECT status, COUNT(*) as count 
                FROM equipments 
                GROUP BY status
            ")->fetchAll(PDO::FETCH_KEY_PAIR);
            
            if ($equipmentResults) {
                foreach ($equipmentResults as $status => $count) {
                    $data['equipment_status'][$status] = (int)$count;
                }
            }

            $taskResults = $this->db->query("
                SELECT status, COUNT(*) as count 
                FROM tasks 
                GROUP BY status
            ")->fetchAll(PDO::FETCH_KEY_PAIR);

            if ($taskResults) {
                foreach ($taskResults as $status => $count) {
                    $data['tasks_status'][$status] = (int)$count;
                }
            }
        } catch (Exception $e) {
            error_log("Dashboard Chart Error: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Get recent tasks.
     */
    public function getRecentTasks(int $userId, string $role, int $limit = 5): array
    {
        try {
            if ($role === 'admin') {
                $stmt = $this->db->prepare("
                    SELECT t.*, GROUP_CONCAT(u.username SEPARATOR ', ') as assignee_names
                    FROM tasks t 
                    LEFT JOIN task_assignees ta ON t.id = ta.task_id
                    LEFT JOIN users u ON ta.user_id = u.id 
                    GROUP BY t.id
                    ORDER BY t.created_at DESC 
                    LIMIT :limit
                ");
            } else {
                $stmt = $this->db->prepare("
                    SELECT t.*, GROUP_CONCAT(u.username SEPARATOR ', ') as assignee_names
                    FROM tasks t 
                    JOIN task_assignees ta_me ON t.id = ta_me.task_id AND ta_me.user_id = :user_id
                    LEFT JOIN task_assignees ta ON t.id = ta.task_id
                    LEFT JOIN users u ON ta.user_id = u.id 
                    GROUP BY t.id
                    ORDER BY t.created_at DESC 
                    LIMIT :limit
                ");
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Dashboard Tasks Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activity (for admin) or notifications (for user).
     */
    public function getRecentActivity(int $userId, string $role, int $limit = 5): array
    {
        try {
            if ($role === 'admin') {
                $stmt = $this->db->prepare("
                    SELECT level as type, message, timestamp as created_at 
                    FROM system_logs 
                    ORDER BY timestamp DESC 
                    LIMIT :limit
                ");
            } else {
                $stmt = $this->db->prepare("
                    SELECT type, message, created_at 
                    FROM notifications n
                    JOIN user_notifications un ON n.id = un.notification_id
                    WHERE un.user_id = :user_id
                    ORDER BY n.created_at DESC 
                    LIMIT :limit
                ");
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Dashboard Activity Error: " . $e->getMessage());
            return [];
        }
    }
}
