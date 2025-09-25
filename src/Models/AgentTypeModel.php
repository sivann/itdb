<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class AgentTypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find an agent type by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM agent_types WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get all active agent types ordered by sort_order
     */
    public function getActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM agent_types WHERE active = 1 ORDER BY sort_order, name"
        );
    }

    /**
     * Get all agent types
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM agent_types ORDER BY sort_order, name"
        );
    }

    /**
     * Create a new agent type
     */
    public function create(array $data): int
    {
        $allowedFields = ['name', 'code', 'description', 'active', 'sort_order'];
        $insertData = array_intersect_key($data, array_flip($allowedFields));

        // Set defaults
        $insertData['active'] = $insertData['active'] ?? 1;
        $insertData['sort_order'] = $insertData['sort_order'] ?? 0;
        $insertData['created_at'] = date('Y-m-d H:i:s');
        $insertData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert('agent_types', $insertData);
    }

    /**
     * Update an agent type
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name', 'code', 'description', 'active', 'sort_order'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $rowsAffected = $this->db->update('agent_types', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete an agent type
     */
    public function delete(int $id): bool
    {
        // Check if agent type is in use
        $canDelete = $this->canDelete($id);
        if (!$canDelete['can_delete']) {
            throw new \Exception("Cannot delete agent type: " . implode(', ', $canDelete['references']));
        }

        $rowsAffected = $this->db->delete('agent_types', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Check if agent type can be deleted
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check if any agents use this type
        $agentCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM agent_agent_type WHERE agent_type_id = :id",
            ['id' => $id]
        );

        if ($agentCount > 0) {
            $references[] = "$agentCount agent(s) are assigned this type";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    /**
     * Find agent type by code
     */
    public function findByCode(string $code): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM agent_types WHERE code = :code",
            ['code' => $code]
        );
    }

    /**
     * Get agent types with usage count
     */
    public function getWithUsageCount(): array
    {
        return $this->db->fetchAll(
            "SELECT at.*,
                    COUNT(aat.agent_id) as agent_count
             FROM agent_types at
             LEFT JOIN agent_agent_type aat ON at.id = aat.agent_type_id
             GROUP BY at.id
             ORDER BY at.sort_order, at.name"
        );
    }

    /**
     * Toggle active status
     */
    public function toggleActive(int $id): bool
    {
        $current = $this->find($id);
        if (!$current) {
            return false;
        }

        return $this->update($id, ['active' => $current['active'] ? 0 : 1]);
    }

    /**
     * Get next sort order
     */
    public function getNextSortOrder(): int
    {
        $maxOrder = $this->db->fetchColumn(
            "SELECT MAX(sort_order) FROM agent_types"
        );

        return ((int) $maxOrder) + 10;
    }

    /**
     * Reorder agent types
     */
    public function reorder(array $orderData): bool
    {
        return $this->db->transaction(function($db) use ($orderData) {
            foreach ($orderData as $id => $sortOrder) {
                $db->update('agent_types',
                    ['sort_order' => $sortOrder, 'updated_at' => date('Y-m-d H:i:s')],
                    ['id' => $id]
                );
            }
            return true;
        });
    }

    /**
     * Check if code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM agent_types WHERE code = :code";
        $params = ['code' => $code];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Find with counts (agents using this type)
     */
    public function findWithCounts(int $id): ?array
    {
        $sql = "
            SELECT at.*,
                   COUNT(aat.agent_id) as agents_count
            FROM agent_types at
            LEFT JOIN agent_agent_type aat ON at.id = aat.agent_type_id
            WHERE at.id = :id
            GROUP BY at.id
            LIMIT 1
        ";

        $result = $this->db->fetchAll($sql, ['id' => $id]);
        return $result ? $result[0] : null;
    }
}