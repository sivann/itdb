<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class ItemTypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM item_types WHERE id = :id", ['id' => $id]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM item_types ORDER BY name");
    }

    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "(description LIKE :search OR name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $totalSql = "SELECT COUNT(*) FROM item_types $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        $sql = "
            SELECT it.*,
                   COALESCE(item_counts.items_count, 0) as items_count
            FROM item_types it
            LEFT JOIN (
                SELECT item_type_id, COUNT(*) as items_count
                FROM items
                GROUP BY item_type_id
            ) item_counts ON it.id = item_counts.item_type_id
            $whereClause
            ORDER BY it.name
            LIMIT :limit OFFSET :offset
        ";
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

    public function create(array $data): int
    {
        return $this->db->insert('item_types', [
            'name' => $data['name'],
            'description' => $data['description'] ?? $data['name'],
            'has_software' => (int) ($data['has_software'] ?? 0)
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $rowsAffected = $this->db->update('item_types', [
            'name' => $data['name'],
            'description' => $data['description'] ?? $data['name'],
            'has_software' => (int) ($data['has_software'] ?? 0)
        ], ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function delete(int $id): bool
    {
        $canDelete = $this->canDelete($id);
        if (!$canDelete['can_delete']) {
            throw new \Exception("Cannot delete item type: " . implode(', ', $canDelete['references']));
        }

        $rowsAffected = $this->db->delete('item_types', ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function canDelete(int $id): array
    {
        $references = [];

        $itemCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE item_type_id = :id",
            ['id' => $id]
        );

        if ($itemCount > 0) {
            $references[] = "$itemCount item(s) use this type";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    public function findWithCounts(int $id): ?array
    {
        $sql = "
            SELECT it.*,
                   COALESCE(items.count, 0) as items_count
            FROM item_types it
            LEFT JOIN (
                SELECT item_type_id, COUNT(*) as count
                FROM items
                WHERE item_type_id = :id
                GROUP BY item_type_id
            ) items ON it.id = items.item_type_id
            WHERE it.id = :id
            LIMIT 1
        ";

        $result = $this->db->fetchAll($sql, ['id' => $id]);
        return $result ? $result[0] : null;
    }
}