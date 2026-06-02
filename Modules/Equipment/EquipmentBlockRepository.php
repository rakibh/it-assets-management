<?php

declare(strict_types=1);

namespace Modules\Equipment;

use Core\Repository;
use PDO;

class EquipmentBlockRepository extends Repository
{
    /**
     * Get all predefined blocks.
     */
    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM equipment_blocks ORDER BY name ASC")->fetchAll();
    }

    /**
     * Get a single block by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM equipment_blocks WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Save (Create/Update) a block.
     */
    public function save(array $data): int
    {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->db->prepare("
                UPDATE equipment_blocks 
                SET name = :name, fields_schema = :schema 
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $data['id'],
                'name' => $data['name'],
                'schema' => $data['fields_schema']
            ]);
            return (int)$data['id'];
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO equipment_blocks (name, fields_schema) 
                VALUES (:name, :schema)
            ");
            $stmt->execute([
                'name' => $data['name'],
                'schema' => $data['fields_schema']
            ]);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Check if a block is attached to any equipment types.
     */
    public function isInUse(int $id): bool
    {
        // Check equipment_types table. block_ids is a JSON array.
        // We use JSON_CONTAINS to check if the ID exists in that array.
        // We check both integer and string versions because JSON is type-sensitive.
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM equipment_types 
            WHERE JSON_CONTAINS(block_ids, :id_int) 
            OR JSON_CONTAINS(block_ids, :id_str)
        ");
        $stmt->execute([
            'id_int' => json_encode($id),
            'id_str' => json_encode((string)$id)
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Delete a block.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM equipment_blocks WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get multiple blocks by their IDs.
     */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM equipment_blocks WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }
}
