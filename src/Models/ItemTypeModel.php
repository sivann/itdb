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
        return $this->db->fetchOne("SELECT * FROM itemtypes WHERE id = :id", ['id' => $id]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM itemtypes ORDER BY name");
    }

    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "(typedesc LIKE :search OR name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $totalSql = "SELECT COUNT(*) FROM itemtypes $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        $sql = "
            SELECT it.*,
                   COALESCE(item_counts.items_count, 0) as items_count
            FROM itemtypes it
            LEFT JOIN (
                SELECT itemtypeid, COUNT(*) as items_count
                FROM items
                GROUP BY itemtypeid
            ) item_counts ON it.id = item_counts.itemtypeid
            $whereClause
            ORDER BY it.typedesc
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
        return $this->db->insert('itemtypes', [
            'name' => $data['name'],
            'typedesc' => $data['typedesc'] ?? $data['name'],
            'hassoftware' => (int) ($data['hassoftware'] ?? 0)
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $rowsAffected = $this->db->update('itemtypes', [
            'name' => $data['name'],
            'typedesc' => $data['typedesc'] ?? $data['name'],
            'hassoftware' => (int) ($data['hassoftware'] ?? 0)
        ], ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function delete(int $id): bool
    {
        $canDelete = $this->canDelete($id);
        if (!$canDelete['can_delete']) {
            throw new \Exception("Cannot delete item type: " . implode(', ', $canDelete['references']));
        }

        $rowsAffected = $this->db->delete('itemtypes', ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function canDelete(int $id): array
    {
        $references = [];

        $itemCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE itemtypeid = :id",
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
            FROM itemtypes it
            LEFT JOIN (
                SELECT itemtypeid, COUNT(*) as count
                FROM items
                WHERE itemtypeid = :id
                GROUP BY itemtypeid
            ) items ON it.id = items.itemtypeid
            WHERE it.id = :id
            LIMIT 1
        ";

        $result = $this->db->fetchAll($sql, ['id' => $id]);
        return $result ? $result[0] : null;
    }
}