<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

/**
 * File data access model - handles all database operations for files (CRUD, queries, uploads, associations).
 */
class FileModel
{
    private DatabaseManager $db;
    private SettingsModel $settings;

    public function __construct(DatabaseManager $db, SettingsModel $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    /**
     * Find a file by ID with related data
     */
    public function find(int $id): ?array
    {
        $file = $this->db->fetchOne(
            "SELECT f.*, ft.name as type_name, f.file_type_id,
                    (SELECT COUNT(*) FROM items_files WHERE file_id = f.id) as items_count,
                    (SELECT COUNT(*) FROM software_files WHERE file_id = f.id) as software_count,
                    (SELECT COUNT(*) FROM contracts_files WHERE file_id = f.id) as contracts_count,
                    (SELECT COUNT(*) FROM invoices_files WHERE file_id = f.id) as invoices_count
             FROM files f
             LEFT JOIN file_types ft ON f.file_type_id = ft.id
             WHERE f.id = :id",
            ['id' => $id]
        );

        if (!$file) {
            return null;
        }

        // Add uploader_user object for template compatibility
        // Since uploader_username field contains username directly, we use it
        if ($file['uploader_username']) {
            // Try to get display name from users table if uploader_username is a username
            $user = $this->db->fetchOne(
                "SELECT username, display_name FROM users WHERE username = :username",
                ['username' => $file['uploader_username']]
            );

            $file['uploader_user'] = [
                'username' => $file['uploader_username'],
                'display_name' => $user['display_name'] ?? $file['uploader_username']
            ];
        }

        return $file;
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
            $whereConditions[] = "(f.title LIKE :search OR f.filename_stored LIKE :search OR ft.name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['type'])) {
            $whereConditions[] = "f.file_type_id = :type";
            $params['type'] = (int) $filters['type'];
        }

        if (!empty($filters['uploader'])) {
            $whereConditions[] = "f.uploader_username = :uploader";
            $params['uploader'] = $filters['uploader'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM files f LEFT JOIN file_types ft ON f.file_type_id = ft.id $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get files with limit and enhanced data including association counts
        $sql = "
            SELECT f.*, ft.name as type_name,
                   (SELECT COUNT(*) FROM items_files WHERE file_id = f.id) as items_count,
                   (SELECT COUNT(*) FROM software_files WHERE file_id = f.id) as software_count,
                   (SELECT COUNT(*) FROM contracts_files WHERE file_id = f.id) as contracts_count,
                   (SELECT COUNT(*) FROM invoices_files WHERE file_id = f.id) as invoices_count
            FROM files f
            LEFT JOIN file_types ft ON f.file_type_id = ft.id
            $whereClause
            ORDER BY f.uploaded_at DESC, f.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $files = $this->db->fetchAll($sql, $params);

        // Enhance each file with additional data
        foreach ($files as &$file) {
            // Add uploader_user object for template compatibility
            if ($file['uploader_username']) {
                // Try to get display name from users table if uploader_username is a username
                $user = $this->db->fetchOne(
                    "SELECT username, display_name FROM users WHERE username = :username",
                    ['username' => $file['uploader_username']]
                );

                $file['uploader_user'] = [
                    'username' => $file['uploader_username'],
                    'display_name' => $user['display_name'] ?? $file['uploader_username']
                ];
            }

            // Add file size from disk (already in DB as file_size, but get fresh from disk)
            $file['file_size_disk'] = $this->getFileSize($file);

            // Add file existence check
            $file['file_exists'] = $this->fileExists($file);
        }

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
            'file_type_id', 'title', 'filename_stored', 'filename_original', 'description', 'file_size',
            'uploader_username', 'uploaded_at', 'updated_at'
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
            'file_type_id', 'title', 'filename_stored', 'filename_original', 'description', 'file_size',
            'uploader_username', 'uploaded_at', 'updated_at'
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
        return $this->db->fetchAll("SELECT * FROM files ORDER BY uploaded_at DESC");
    }

    /**
     * Get file types from file_types table
     */
    public function getFileTypes(): array
    {
        return $this->db->fetchAll("SELECT id, name FROM file_types ORDER BY name");
    }

    /**
     * Get uploaders for dropdown
     */
    public function getUploaders(): array
    {
        return $this->db->fetchAll("
            SELECT DISTINCT uploader_username
            FROM files
            WHERE uploader_username IS NOT NULL AND uploader_username != ''
            ORDER BY uploader_username
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
            $whereConditions[] = "(f.title LIKE :search OR f.filename_stored LIKE :search OR ft.name LIKE :search" .
                                (is_numeric($search) ? " OR f.id = :search_id" : "") . ")";
            $params['search'] = "%{$search}%";
            if (is_numeric($search)) {
                $params['search_id'] = (int) $search;
            }
        }

        // Exclude files associated with specific software
        if (!empty($filters['exclude_software'])) {
            $whereConditions[] = "f.id NOT IN (
                SELECT file_id FROM software_files WHERE software_id = :exclude_software
            )";
            $params['exclude_software'] = $filters['exclude_software'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "
            SELECT f.*, ft.name as type_name
            FROM files f
            LEFT JOIN file_types ft ON f.file_type_id = ft.id
            $whereClause
            ORDER BY f.uploaded_at DESC
            LIMIT :limit
        ";

        $params['limit'] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get file path for a file record
     */
    public function getFilePath(array $file): string
    {
        $uploadPath = $this->settings->getFileStoragePath();
        return $uploadPath . '/' . $file['filename_stored'];
    }

    /**
     * Check if file exists on disk
     */
    public function fileExists(array $file): bool
    {
        return file_exists($this->getFilePath($file));
    }

    /**
     * Get file size from disk
     */
    public function getFileSize(array $file): int
    {
        $filePath = $this->getFilePath($file);
        return file_exists($filePath) ? filesize($filePath) : 0;
    }

    /**
     * Get items associated with this file
     */
    public function getAssociatedItems(int $fileId): array
    {
        $sql = "
            SELECT i.id, i.label, i.model, i.function,
                   it.name as type_name, st.name as status_name
            FROM items_files i2f
            INNER JOIN items i ON i2f.item_id = i.id
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN status_types st ON i.status_id = st.id
            WHERE i2f.file_id = :file_id
            ORDER BY i.label
        ";
        return $this->db->fetchAll($sql, ['file_id' => $fileId]);
    }

    /**
     * Get software associated with this file
     */
    public function getAssociatedSoftware(int $fileId): array
    {
        $sql = "
            SELECT s.id, s.title, s.version,
                   s.license_type_id,
                   a.name as manufacturer_name
            FROM software_files s2f
            INNER JOIN software s ON s2f.software_id = s.id
            LEFT JOIN agents a ON s.manufacturer_id = a.id
            WHERE s2f.file_id = :file_id
            ORDER BY s.title
        ";
        return $this->db->fetchAll($sql, ['file_id' => $fileId]);
    }

    /**
     * Get contracts associated with this file
     */
    public function getAssociatedContracts(int $fileId): array
    {
        $sql = "
            SELECT c.id, c.title, c.contract_number, c.start_date, c.end_date,
                   ct.name as contract_type_name
            FROM contracts_files c2f
            INNER JOIN contracts c ON c2f.contract_id = c.id
            LEFT JOIN contract_types ct ON c.contract_type_id = ct.id
            WHERE c2f.file_id = :file_id
            ORDER BY c.title
        ";
        return $this->db->fetchAll($sql, ['file_id' => $fileId]);
    }

    /**
     * Get invoices associated with this file
     */
    public function getAssociatedInvoices(int $fileId): array
    {
        $sql = "
            SELECT inv.id, inv.invoice_date, inv.total_cost as amount, inv.comments,
                   v.name as vendor_name, b.name as buyer_name
            FROM invoices_files inv2f
            INNER JOIN invoices inv ON inv2f.invoice_id = inv.id
            LEFT JOIN agents v ON inv.vendor_id = v.id
            LEFT JOIN agents b ON inv.buyer_id = b.id
            WHERE inv2f.file_id = :file_id
            ORDER BY inv.invoice_date DESC
        ";
        return $this->db->fetchAll($sql, ['file_id' => $fileId]);
    }
}