<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class FileModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find a file by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM files WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get paginated files with filtering
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $whereConditions[] = "(title LIKE :search OR fname LIKE :search OR type LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['type'])) {
            $whereConditions[] = "type = :type";
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['uploader'])) {
            $whereConditions[] = "uploader = :uploader";
            $params['uploader'] = $filters['uploader'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM files $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get files with limit
        $sql = "
            SELECT *
            FROM files
            $whereClause
            ORDER BY uploaddate DESC, id DESC
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $files = $this->db->fetchAll($sql, $params);

        return [
            'data' => $files,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create a new file record
     */
    public function create(array $data): int
    {
        $allowedFields = [
            'type', 'title', 'fname', 'uploader', 'uploaddate', 'date'
        ];

        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('files', $insertData);
    }

    /**
     * Update a file record
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'type', 'title', 'fname', 'uploader', 'uploaddate', 'date'
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->db->update('files', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete a file record
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('files', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Get all files
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM files ORDER BY uploaddate DESC");
    }

    /**
     * Get file types for dropdown
     */
    public function getFileTypes(): array
    {
        return $this->db->fetchAll("SELECT DISTINCT type FROM files WHERE type IS NOT NULL AND type != '' ORDER BY type");
    }

    /**
     * Get uploaders for dropdown
     */
    public function getUploaders(): array
    {
        return $this->db->fetchAll("
            SELECT DISTINCT uploader
            FROM files
            WHERE uploader IS NOT NULL AND uploader != ''
            ORDER BY uploader
        ");
    }

    /**
     * Search files with optional exclusions
     */
    public function search(array $filters, int $limit = 20): array
    {
        $whereConditions = [];
        $params = [];

        // Basic search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $whereConditions[] = "(title LIKE :search OR fname LIKE :search OR type LIKE :search" .
                                (is_numeric($search) ? " OR id = :search_id" : "") . ")";
            $params['search'] = "%{$search}%";
            if (is_numeric($search)) {
                $params['search_id'] = (int) $search;
            }
        }

        // Exclude files associated with specific software
        if (!empty($filters['exclude_software'])) {
            $whereConditions[] = "id NOT IN (
                SELECT file_id FROM software_files WHERE software_id = :exclude_software
            )";
            $params['exclude_software'] = $filters['exclude_software'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "
            SELECT *
            FROM files
            $whereClause
            ORDER BY uploaddate DESC
            LIMIT :limit
        ";

        $params['limit'] = $limit;

        return $this->db->fetchAll($sql, $params);
    }
}