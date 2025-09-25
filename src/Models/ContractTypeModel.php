<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class ContractTypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Get all contract types
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM contracttypes ORDER BY name");
    }

    /**
     * Get paginated contract types with optional search
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "name LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $totalSql = "SELECT COUNT(*) FROM contracttypes $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        $sql = "SELECT * FROM contracttypes $whereClause ORDER BY name LIMIT :limit OFFSET :offset";
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $items = $this->db->fetchAll($sql, $params);

        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Find a contract type by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM contracttypes WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Create a new contract type
     */
    public function create(array $data): int
    {
        $allowedFields = ['name'];
        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('contracttypes', $insertData);
    }

    /**
     * Update a contract type
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->db->update('contracttypes', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete a contract type
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('contracttypes', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Check if contract type can be deleted
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check contracts using this type
        $contractCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE type = :id",
            ['id' => $id]
        );
        if ($contractCount > 0) {
            $references[] = "$contractCount contract(s)";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }
}