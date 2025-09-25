<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class StatusTypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM statustypes WHERE id = :id", ['id' => $id]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM statustypes ORDER BY statusdesc");
    }

    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "statusdesc LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $totalSql = "SELECT COUNT(*) FROM statustypes $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        $sql = "SELECT * FROM statustypes $whereClause ORDER BY statusdesc LIMIT :limit OFFSET :offset";
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
        return $this->db->insert('statustypes', [
            'statusdesc' => $data['statusdesc'],
            'availableforloan' => $data['availableforloan'] ?? 0
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $rowsAffected = $this->db->update('statustypes', [
            'statusdesc' => $data['statusdesc'],
            'availableforloan' => $data['availableforloan'] ?? 0
        ], ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function delete(int $id): bool
    {
        $canDelete = $this->canDelete($id);
        if (!$canDelete['can_delete']) {
            throw new \Exception("Cannot delete status type: " . implode(', ', $canDelete['references']));
        }

        $rowsAffected = $this->db->delete('statustypes', ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function canDelete(int $id): array
    {
        $references = [];

        $itemCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE status = :id",
            ['id' => $id]
        );

        if ($itemCount > 0) {
            $references[] = "$itemCount item(s) use this status";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }
}