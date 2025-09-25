<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class LicenseTypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?array
    {
        $sql = "
            SELECT lt.*,
                   COALESCE(software_counts.software_count, 0) as software_count
            FROM license_types lt
            LEFT JOIN (
                SELECT slicensetype, COUNT(*) as software_count
                FROM software
                WHERE slicensetype IS NOT NULL
                GROUP BY slicensetype
            ) software_counts ON lt.id = software_counts.slicensetype
            WHERE lt.id = :id
            LIMIT 1
        ";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM license_types ORDER BY name");
    }

    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "(name LIKE :search OR description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $totalSql = "SELECT COUNT(*) FROM license_types $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        $sql = "
            SELECT lt.*,
                   COALESCE(software_counts.software_count, 0) as software_count
            FROM license_types lt
            LEFT JOIN (
                SELECT slicensetype, COUNT(*) as software_count
                FROM software
                WHERE slicensetype IS NOT NULL
                GROUP BY slicensetype
            ) software_counts ON lt.id = software_counts.slicensetype
            $whereClause
            ORDER BY lt.name
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
        return $this->db->insert('license_types', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $rowsAffected = $this->db->update('license_types', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ], ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function delete(int $id): bool
    {
        $canDelete = $this->canDelete($id);
        if (!$canDelete['can_delete']) {
            throw new \Exception("Cannot delete license type: " . implode(', ', $canDelete['references']));
        }

        $rowsAffected = $this->db->delete('license_types', ['id' => $id]);
        return $rowsAffected > 0;
    }

    public function canDelete(int $id): array
    {
        $references = [];

        $softwareCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM software WHERE slicensetype = :id",
            ['id' => $id]
        );

        if ($softwareCount > 0) {
            $references[] = "$softwareCount software entries use this license type";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }
}