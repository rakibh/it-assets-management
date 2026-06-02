<?php

declare(strict_types=1);

namespace Modules\Equipment;

use Core\Repository;
use PDO;
use Exception;

class EquipmentRepository extends Repository
{
    /**
     * Get all equipment types.
     */
    public function getTypes(): array
    {
        return $this->db->query("SELECT * FROM equipment_types ORDER BY name ASC")->fetchAll();
    }

    /**
     * Get a single equipment type by ID.
     */
    public function getTypeById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM equipment_types WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create or update equipment type.
     */
    public function saveType(array $data): int
    {
        $blockIds = isset($data['block_ids']) ? json_encode($data['block_ids']) : '[]';
        $hasNetwork = isset($data['has_network']) ? ($data['has_network'] ? 1 : 0) : 0;

        if (isset($data['id']) && $data['id']) {
            $stmt = $this->db->prepare("
                UPDATE equipment_types 
                SET name = :name, form_schema = :schema, block_ids = :block_ids, has_network = :has_network 
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $data['id'],
                'name' => $data['name'],
                'schema' => $data['form_schema'],
                'block_ids' => $blockIds,
                'has_network' => $hasNetwork
            ]);
            return (int)$data['id'];
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO equipment_types (name, form_schema, block_ids, has_network) 
                VALUES (:name, :schema, :block_ids, :has_network)
            ");
            $stmt->execute([
                'name' => $data['name'],
                'schema' => $data['form_schema'],
                'block_ids' => $blockIds,
                'has_network' => $hasNetwork
            ]);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Check if an equipment type is being used by any equipment assets.
     */
    public function isTypeInUse(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipments WHERE type_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Delete equipment type.
     */
    public function deleteType(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM equipment_types WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Check for expiring warranties and trigger notifications.
     * Triggers at 30, 15, and 0 days before expiry.
     */
    public function checkWarranties(): void
    {
        $notificationRepo = new \Modules\Notifications\NotificationRepository();
        
        $intervals = [30, 15, 0];
        
        foreach ($intervals as $days) {
            $stmt = $this->db->prepare("
                SELECT id, name, serial_number, warranty_expiry 
                FROM equipments 
                WHERE warranty_expiry IS NOT NULL 
                AND DATEDIFF(warranty_expiry, CURDATE()) = ?
            ");
            $stmt->execute([$days]);
            $equipments = $stmt->fetchAll();
            
            foreach ($equipments as $e) {
                $label = $e['name'] . (!empty($e['serial_number']) ? " ({$e['serial_number']})" : "");
                $expiryDate = date('d/m/Y', strtotime($e['warranty_expiry']));

                if ($days === 0) {
                    $msg = "Warranty for \"{$label}\" expired on {$expiryDate}";
                    $priority = 'urgent';
                } else {
                    $msg = "Warranty for \"{$label}\" expires in {$days} days ({$expiryDate})";
                    $priority = $days <= 15 ? 'high' : 'medium';
                }
                
                // Avoid duplicate notifications for the same day/interval
                $checkMsgPart = $days === 0 ? '%TODAY%' : "%$days days%";
                $checkStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM notifications 
                    WHERE type = 'warranty' 
                    AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.equipment_id')) = ?
                    AND message LIKE ?
                    AND created_at >= CURDATE()
                ");
                $checkStmt->execute([$e['id'], $checkMsgPart]);

                if ((int)$checkStmt->fetchColumn() === 0) {
                    // Notify ALL active users (Admin + Standard) as requested
                    $userStmt = $this->db->query("SELECT id FROM users WHERE status = 'active'");
                    $userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($userIds)) {
                        $notificationRepo->createForUsers($userIds, 'warranty', $msg, $priority, ['equipment_id' => $e['id']]);
                    }
                }
            }
        }
    }

    /**
     * Get equipment list with filtering and pagination.
     */
    public function getEquipments(array $filters = [], int $page = 1, int $limit = 100): array
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = " WHERE 1=1";

        if (!empty($filters['type_id'])) {
            $where .= " AND e.type_id = :type_id";
            $params['type_id'] = $filters['type_id'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND e.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (e.serial_number LIKE :s1 OR e.brand LIKE :s2 OR e.model LIKE :s3 OR e.location LIKE :s4 OR e.floor LIKE :s5 OR e.department LIKE :s6 OR e.`condition` LIKE :s7 OR e.name LIKE :s8 OR n.ip_address LIKE :s9)";
            $params['s1'] = '%' . $filters['search'] . '%';
            $params['s2'] = '%' . $filters['search'] . '%';
            $params['s3'] = '%' . $filters['search'] . '%';
            $params['s4'] = '%' . $filters['search'] . '%';
            $params['s5'] = '%' . $filters['search'] . '%';
            $params['s6'] = '%' . $filters['search'] . '%';
            $params['s7'] = '%' . $filters['search'] . '%';
            $params['s8'] = '%' . $filters['search'] . '%';
            $params['s9'] = '%' . $filters['search'] . '%';
        }

        $limitInt = (int)$limit;
        $offsetInt = (int)max(0, $offset);

        $query = "
            SELECT e.*, et.name as type_name, 
            n.ip_address
            FROM equipments e
            JOIN equipment_types et ON e.type_id = et.id
            LEFT JOIN equipment_network_map enm ON e.id = enm.equipment_id
            LEFT JOIN network_info n ON enm.network_id = n.id
            $where
            ORDER BY e.created_at DESC 
            LIMIT $limitInt OFFSET $offsetInt
        ";

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $items = $stmt->fetchAll();

        // Optimized Total count query
        if (!empty($filters['search'])) {
            // Join required for IP address search
            $countQuery = "
                SELECT COUNT(*) FROM equipments e 
                LEFT JOIN equipment_network_map enm ON e.id = enm.equipment_id
                LEFT JOIN network_info n ON enm.network_id = n.id
                $where
            ";
        } else {
            // Simple count without extra joins
            $countQuery = "SELECT COUNT(*) FROM equipments e $where";
        }

        $countStmt = $this->db->prepare($countQuery);
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
     * Get a single equipment record by ID.
     */
    public function getEquipmentById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, et.name as type_name, et.form_schema, et.block_ids, et.has_network as type_has_network
            FROM equipments e
            JOIN equipment_types et ON e.type_id = et.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $equipment = $stmt->fetch();

        if ($equipment) {
            // Get Detailed Network Info
            $stmt = $this->db->prepare("
                SELECT n.* FROM network_info n
                JOIN equipment_network_map enm ON n.id = enm.network_id
                WHERE enm.equipment_id = ?
            ");
            $stmt->execute([$id]);
            $equipment['network'] = $stmt->fetch() ?: null;

            // Fetch Linked Blocks (Legacy support, though we use mandatory ones now)
            $blockIds = $equipment['block_ids'] ? json_decode($equipment['block_ids'], true) : [];
            if (!empty($blockIds)) {
                $blockRepo = new EquipmentBlockRepository();
                $equipment['blocks'] = $blockRepo->getByIds($blockIds);
            } else {
                $equipment['blocks'] = [];
            }
        }

        return $equipment ?: null;
    }

    /**
     * Save equipment record (Create/Update).
     */
    public function saveEquipment(array $data, ?array $networkData = null): int
    {
        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $id = !empty($data['id']) ? (int)$data['id'] : null;

            $params = [
                'type_id' => $data['type_id'],
                'name' => $data['name'],
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'serial_number' => $data['serial_number'] ?: null,
                'mac' => $data['mac_address'] ?: null,
                'status' => $data['status'] ?? 'Available',
                'location' => $data['location'] ?? null,
                'office_location' => $data['office_location'] ?? null,
                'floor' => $data['floor'] ?? null,
                'department' => $data['department'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'condition' => !empty($data['condition']) ? $data['condition'] : 'excellent',
                'warranty_seller' => $data['warranty_seller'] ?? null,
                'warranty_purchase_date' => !empty($data['warranty_purchase_date']) ? $data['warranty_purchase_date'] : null,
                'warranty_expiry' => !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
                'warranty_file' => $data['warranty_file'] ?? null,
                'custom_data' => isset($data['custom_data']) ? (is_string($data['custom_data']) ? $data['custom_data'] : json_encode($data['custom_data'])) : null,
                'images' => isset($data['images']) ? (is_string($data['images']) ? $data['images'] : json_encode($data['images'])) : null,
            ];

            if ($id) {
                $params['id'] = $id;
                $stmt = $this->db->prepare("
                    UPDATE equipments SET 
                    type_id = :type_id, name = :name, brand = :brand, model = :model, 
                    serial_number = :serial_number, mac_address = :mac, status = :status,
                    location = :location, office_location = :office_location, floor = :floor,
                    department = :department, assigned_to = :assigned_to,
                    `condition` = :condition, warranty_seller = :warranty_seller,
                    warranty_purchase_date = :warranty_purchase_date,
                    warranty_expiry = :warranty_expiry, warranty_file = :warranty_file,
                    custom_data = :custom_data, images = :images
                    WHERE id = :id
                ");
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO equipments 
                    (type_id, name, brand, model, serial_number, mac_address, status, 
                    location, office_location, floor, department, assigned_to, 
                    `condition`, warranty_seller, warranty_purchase_date, 
                    warranty_expiry, warranty_file, custom_data, images)
                    VALUES 
                    (:type_id, :name, :brand, :model, :serial_number, :mac, :status, 
                    :location, :office_location, :floor, :department, :assigned_to, 
                    :condition, :warranty_seller, :warranty_purchase_date, 
                    :warranty_expiry, :warranty_file, :custom_data, :images)
                ");
            }

            $stmt->execute($params);

            // Notifications
            $notificationRepo = new \Modules\Notifications\NotificationRepository();
            $performer = \Core\Session::get('username', 'Someone');
            $label = $data['name'] . (!empty($data['serial_number']) ? " ({$data['serial_number']})" : "");

            if (!$id) {
                $id = (int)$this->db->lastInsertId();
                $this->logAudit('create', 'equipments', $id, null, $data);
                
                // Get type name for the message
                $typeStmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
                $typeStmt->execute([$data['type_id']]);
                $typeName = $typeStmt->fetchColumn() ?: 'Equipment';
                $msg = "New {$typeName} \"{$label}\" added by {$performer}";
            } else {
                $oldEquipment = $this->getEquipmentById($id);
                $this->logAudit('update', 'equipments', $id, $oldEquipment, $data);
                
                if (isset($data['status']) && $oldEquipment['status'] !== $data['status']) {
                    $status = $data['status'];
                    $msg = "Equipment \"{$label}\" status changed to {$status} by {$performer}";
                } elseif (isset($data['assigned_to']) && $oldEquipment['assigned_to'] !== $data['assigned_to']) {
                    $person = $data['assigned_to'] ?: 'Unassigned';
                    $msg = "Equipment \"{$label}\" assigned to {$person} by {$performer}";
                } else {
                    $msg = "Equipment \"{$label}\" updated by {$performer}";
                }
            }

            $notificationRepo->createGlobal('equipment', $msg, 'medium', ['equipment_id' => $id]);

            // Handle Network Info linking
            if ($networkData && !empty($networkData['ip_address'])) {
                $networkRepo = new \Modules\Network\NetworkRepository();
                // Check if IP already exists
                $stmt = $this->db->prepare("SELECT id FROM network_info WHERE ip_address = ?");
                $stmt->execute([$networkData['ip_address']]);
                $networkId = $stmt->fetchColumn();

                if (!$networkId) {
                    $networkId = $networkRepo->createNetwork($networkData);
                } else {
                    // Inject equipment_id to prevent redundant unassign/assign notifications in updateNetwork
                    $networkData['equipment_id'] = $id;
                    $networkRepo->updateNetwork((int)$networkId, $networkData);
                }

                // Assign this equipment to the network node
                $networkRepo->assignEquipment((int)$networkId, $id);
            }

            if ($useTransaction) $this->db->commit();
            return $id;
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete equipment.
     */
    public function deleteEquipment(int $id): bool
    {
        $equipment = $this->getEquipmentById($id);
        $stmt = $this->db->prepare("DELETE FROM equipments WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result && $equipment) {
            $notificationRepo = new \Modules\Notifications\NotificationRepository();
            $msg = "Equipment deleted: " . $equipment['name'] . (!empty($equipment['serial_number']) ? " (" . $equipment['serial_number'] . ")" : "");
            $notificationRepo->createGlobal('equipment', $msg, 'medium');
            
            $this->logAudit('delete', 'equipments', $id, $equipment, null);
        }

        return $result;
    }

    /**
     * Bulk delete equipment.
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) return 0;
        
        // Log before deletion
        foreach ($ids as $id) {
            $equipment = $this->getEquipmentById((int)$id);
            if ($equipment) {
                $this->logAudit('delete', 'equipments', (int)$id, $equipment, null);
            }
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM equipments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        $count = $stmt->rowCount();
        if ($count > 0) {
            $notificationRepo = new \Modules\Notifications\NotificationRepository();
            $notificationRepo->createGlobal('equipment', "$count equipment items were deleted via bulk action.", 'medium');
        }
        
        return $count;
    }
}
