<?php

declare(strict_types=1);

namespace Modules\Admin;

use Core\Repository;
use PDO;

class GlobalSearchRepository extends Repository
{
    /**
     * Perform a global search across multiple modules.
     */
    public function search(string $query): array
    {
        $term = '%' . $query . '%';
        $results = [];
        $userId = \Core\Session::get('user_id');
        $isAdmin = \Core\Session::get('role') === 'admin';

        // 1. Search Equipment
        $stmt = $this->db->prepare("
            SELECT 'equipment' as type, e.id, CONCAT_WS(' ', e.brand, e.model) as title, 
                   CONCAT_WS(' - ', e.serial_number, COALESCE(e.location, 'No Location'), GROUP_CONCAT(n.ip_address SEPARATOR ', ')) as subtitle 
            FROM equipments e
            JOIN equipment_types et ON e.type_id = et.id
            LEFT JOIN equipment_network_map enm ON e.id = enm.equipment_id
            LEFT JOIN network_info n ON enm.network_id = n.id
            WHERE e.serial_number LIKE ? OR e.brand LIKE ? OR e.model LIKE ? OR e.name LIKE ? 
               OR e.location LIKE ? OR e.department LIKE ? OR e.mac_address LIKE ? OR n.ip_address LIKE ?
               OR et.name LIKE ?
            GROUP BY e.id
            LIMIT 5
        ");
        $stmt->execute([$term, $term, $term, $term, $term, $term, $term, $term, $term]);
        foreach ($stmt->fetchAll() as $row) {
            $row['url'] = 'index.php?route=view_equipment&id=' . $row['id'];
            $row['icon'] = 'bi-pc-display';
            $results[] = $row;
        }

        // 2. Search Tasks
        $stmt = $this->db->prepare("
            SELECT 'task' as type, t.id, t.title, 
                   CONCAT_WS(' | ', t.status, CONCAT('Priority: ', t.priority)) as subtitle 
            FROM tasks t
            LEFT JOIN task_assignees ta ON t.id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE t.title LIKE ? OR t.description LIKE ? OR t.tags LIKE ? OR u.username LIKE ?
            GROUP BY t.id
            LIMIT 5
        ");
        $stmt->execute([$term, $term, $term, $term]);
        foreach ($stmt->fetchAll() as $row) {
            $row['url'] = 'index.php?route=view_task&id=' . $row['id'];
            $row['icon'] = 'bi-list-task';
            $results[] = $row;
        }

        // 3. Search Network
        $stmt = $this->db->prepare("
            SELECT 'network' as type, n.id, n.ip_address as title, 
                   CONCAT_WS(' ', COALESCE(e.brand, ''), COALESCE(e.model, ''), CONCAT('(', COALESCE(e.serial_number, 'Unassigned'), ')')) as subtitle 
            FROM network_info n
            LEFT JOIN equipment_network_map enm ON n.id = enm.network_id
            LEFT JOIN equipments e ON enm.equipment_id = e.id
            WHERE n.ip_address LIKE ? OR n.mac_address LIKE ? OR n.cable_no LIKE ? OR n.remarks LIKE ? OR e.serial_number LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$term, $term, $term, $term, $term]);
        foreach ($stmt->fetchAll() as $row) {
            $row['url'] = 'index.php?route=view_network&id=' . $row['id'];
            $row['icon'] = 'bi-diagram-3';
            $results[] = $row;
        }

        // 4. Search Users
        $stmt = $this->db->prepare("
            SELECT 'user' as type, id, username as title, CONCAT(role, ' | ', COALESCE(employee_id, 'N/A')) as subtitle 
            FROM users 
            WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$term, $term, $term, $term, $term]);
        foreach ($stmt->fetchAll() as $row) {
            $row['url'] = 'index.php?route=profile&id=' . $row['id'];
            $row['icon'] = 'bi-person';
            $results[] = $row;
        }

        // 5. Search Equipment Types & Blocks (Admin only)
        if ($isAdmin) {
            $stmt = $this->db->prepare("
                SELECT 'type' as type, id, name as title, 'Equipment Type' as subtitle 
                FROM equipment_types 
                WHERE name LIKE ?
                LIMIT 3
            ");
            $stmt->execute([$term]);
            foreach ($stmt->fetchAll() as $row) {
                $row['url'] = 'index.php?route=equipment_types';
                $row['icon'] = 'bi-grid-3x3-gap';
                $results[] = $row;
            }

            $stmt = $this->db->prepare("
                SELECT 'block' as type, id, name as title, 'Predefined Block' as subtitle 
                FROM equipment_blocks 
                WHERE name LIKE ?
                LIMIT 3
            ");
            $stmt->execute([$term]);
            foreach ($stmt->fetchAll() as $row) {
                $row['url'] = 'index.php?route=equipment_blocks';
                $row['icon'] = 'bi-puzzle';
                $results[] = $row;
            }
        }

        // 6. Search Notifications (User specific)
        if ($userId) {
            $stmt = $this->db->prepare("
                SELECT 'notification' as type, n.id, n.message as title, n.type as subtitle 
                FROM notifications n
                JOIN user_notifications un ON n.id = un.notification_id
                WHERE un.user_id = ? AND n.message LIKE ?
                ORDER BY n.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$userId, $term]);
            foreach ($stmt->fetchAll() as $row) {
                $row['url'] = 'index.php?route=list_notifications';
                $row['icon'] = 'bi-bell';
                // If it's a specific type, we could try to make the URL more specific, but list is safer
                $results[] = $row;
            }
        }

        // 7. Search Settings (Admin only)
        if ($isAdmin) {
            $stmt = $this->db->prepare("
                SELECT 'setting' as type, setting_key as id, setting_key as title, setting_value as subtitle 
                FROM system_settings 
                WHERE setting_key LIKE ? OR setting_value LIKE ?
                LIMIT 3
            ");
            $stmt->execute([$term, $term]);
            foreach ($stmt->fetchAll() as $row) {
                $row['url'] = 'index.php?route=settings';
                $row['icon'] = 'bi-gear';
                $results[] = $row;
            }

            // 8. Search System Logs (Admin only)
            $stmt = $this->db->prepare("
                SELECT 'log' as type, id, message as title, CONCAT_WS(' | ', level, category, timestamp) as subtitle 
                FROM system_logs 
                WHERE message LIKE ? OR level LIKE ? OR category LIKE ?
                ORDER BY timestamp DESC
                LIMIT 5
            ");
            $stmt->execute([$term, $term, $term]);
            foreach ($stmt->fetchAll() as $row) {
                $row['url'] = 'index.php?route=admin_logs';
                $row['icon'] = 'bi-journal-text';
                $results[] = $row;
            }
        }

        return $results;
    }
}
