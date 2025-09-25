<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class FileTypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM filetypes WHERE id = :id", ['id' => $id]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM filetypes ORDER BY typedesc");
    }

    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "typedesc LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $totalSql = "SELECT COUNT(*) FROM filetypes $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        $sql = "SELECT * FROM filetypes $whereClause ORDER BY typedesc LIMIT :limit OFFSET :offset";
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
        return $this->db->insert('filetypes', [
            'typedesc' => $data['typedesc']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $rowsAffected = $this->db->update('filetypes', [
            'typedesc' => $data['typedesc']
        ], ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function delete(int $id): bool
    {
        $canDelete = $this->canDelete($id);
        if (!$canDelete['can_delete']) {
            throw new \Exception("Cannot delete file type: " . implode(', ', $canDelete['references']));
        }

        $rowsAffected = $this->db->delete('filetypes', ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function canDelete(int $id): array
    {
        $references = [];

        $fileCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM files WHERE type = :id",
            ['id' => $id]
        );

        if ($fileCount > 0) {
            $references[] = "$fileCount file(s) use this type";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }
}