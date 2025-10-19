<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;
use App\Models\InvoiceModel;

/**
 * Software data access model - handles all database operations for software (CRUD, queries, associations).
 */
class SoftwareModel
{
    private DatabaseManager $db;
    private InvoiceModel $invoiceModel;

    public function __construct(DatabaseManager $db, InvoiceModel $invoiceModel)
    {
        $this->db = $db;
        $this->invoiceModel = $invoiceModel;
    }

    /**
     * Get paginated software with optional filters
     */
    public function getPaginated(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $conditions[] = "(title LIKE ? OR version LIKE ? OR comments LIKE ?)";
            $params = array_merge($params, [$search, $search, $search]);
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        // Get total count
        $countSql = "SELECT COUNT(*) FROM software {$whereClause}";
        $total = $this->db->fetchColumn($countSql, $params);

        // Get software with manufacturer info and more complete data
        $sql = "
            SELECT s.*, a.name as manufacturer_name, s.license_type_id, s.license_key, (SELECT COUNT(*) FROM items_software WHERE software_id = s.id) as installations_count
            FROM software s
            LEFT JOIN agents a ON s.manufacturer_id = a.id
            {$whereClause}
            ORDER BY s.id DESC LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;

        $software = $this->db->fetchAll($sql, $params);

        // Transform data to match template expectations
        $transformedSoftware = array_map(function($item) {
            // Basic display info
            $item['display_title'] = $item['title'] . ($item['version'] ? ' v' . $item['version'] : '');
            $item['sinfo'] = $item['comments'];

            // License quantity from parsed field
            $licenseCount = (int)($item['licqty'] ?? 0);
            $item['licqty'] = $licenseCount > 0 ? $licenseCount : null;

            // License type (0=Per Device, 1=Per User, 2=Site License, 3=Volume License)
            $licenseType = !empty($item['license_type_id']) && is_numeric($item['license_type_id']) ? (int)$item['license_type_id'] : 0;
            $item['lictype'] = $licenseType;

            // Installation count from database query
            $installationsCount = (int)($item['installations_count'] ?? 0);
            $item['installations_count'] = $installationsCount;
            $item['available_licenses'] = max(0, $licenseCount - $installationsCount);

            // Load associated items with details for display
            if ($installationsCount > 0) {
                $itemsSql = "
                    SELECT i.id, i.label, i.function, i.model,
                           a.name as manufacturer_name,
                           it.name as type_name
                    FROM items_software i2s
                    INNER JOIN items i ON i2s.item_id = i.id
                    LEFT JOIN agents a ON i.manufacturer_id = a.id
                    LEFT JOIN item_types it ON i.item_type_id = it.id
                    WHERE i2s.software_id = ?
                    ORDER BY i.function, i.label
                    LIMIT 10
                ";
                $item['items_list'] = $this->db->fetchAll($itemsSql, [$item['id']]);
            } else {
                $item['items_list'] = [];
            }

            // License status
            if ($licenseCount > 0) {
                $item['license_status'] = [
                    'status' => 'active',
                    'days' => 365 // Would calculate from purchase/expiry date
                ];
            } else {
                $item['license_status'] = [
                    'status' => 'unknown',
                    'days' => null
                ];
            }

            return $item;
        }, $software);

        return [
            'data' => $transformedSoftware,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Find software by ID with association counts
     */
    public function find(int $id): ?array
    {
        $sql = "
            SELECT s.*, a.name as manufacturer_name
            FROM software s
            LEFT JOIN agents a ON s.manufacturer_id = a.id
            WHERE s.id = ? LIMIT 1
        ";
        $result = $this->db->fetchAll($sql, [$id]);
        if (!$result) {
            return null;
        }

        $software = $result[0];

        // Enrich with associations
        $software = $this->enrichSoftwareWithAssociations($software);

        // Get full association data
        $software['items'] = $this->getAssociatedItems($id);
        $software['invoices'] = $this->getAssociatedInvoices($id);
        $software['contracts'] = $this->getAssociatedContracts($id);
        $software['files'] = $this->getAssociatedFiles($id);
        $software['tags'] = $this->getAssociatedTags($id);

        return $software;
    }

    /**
     * Enrich software data with association counts and formatted data
     */
    private function enrichSoftwareWithAssociations(array $software): array
    {
        $softwareId = $software['id'];

        // Get association counts
        $software['items_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM items_software WHERE software_id = ?", [$softwareId]
        );

        $software['invoices_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM software_invoices WHERE software_id = ?", [$softwareId]
        );

        $software['contracts_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts_software WHERE software_id = ?", [$softwareId]
        );

        $software['files_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM software_files WHERE software_id = ?", [$softwareId]
        );

        $software['tags_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM software_tags WHERE software_id = ?", [$softwareId]
        );

        // Create mock relationship objects for template compatibility (only if not already set)
        if (!isset($software['items'])) {
            $software['items'] = ['count' => $software['items_count']];
        }
        if (!isset($software['invoices'])) {
            $software['invoices'] = ['count' => $software['invoices_count']];
        }
        if (!isset($software['contracts'])) {
            $software['contracts'] = ['count' => $software['contracts_count']];
        }
        if (!isset($software['files'])) {
            $software['files'] = ['count' => $software['files_count']];
        }
        if (!isset($software['tags'])) {
            $software['tags'] = ['count' => $software['tags_count']];
        }

        // Add display formatting
        $software['display_title'] = $software['title'] . ($software['version'] ? ' v' . $software['version'] : '');
        $software['sinfo'] = $software['comments'];

        return $software;
    }

    /**
     * Create new software
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO software (title, version, license_key, comments, url, license_type_id, category, manufacturer_id, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $params = [
            $data['title'] ?? null,
            $data['version'] ?? null,
            $data['license_key'] ?? null,
            $data['comments'] ?? null,
            $data['url'] ?? null,
            $data['license_type_id'] ?? null,
            $data['category'] ?? null,
            $data['manufacturer_id'] ?? null,
            time()
        ];

        $this->db->execute($sql, $params);
        return $this->db->getLastInsertId();
    }

    /**
     * Update software
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE software SET
                title = ?, version = ?, license_key = ?, comments = ?,
                url = ?, license_type_id = ?, category = ?, manufacturer_id = ?, updated_at = ?
            WHERE id = ?
        ";

        $params = [
            $data['title'] ?? null,
            $data['version'] ?? null,
            $data['license_key'] ?? null,
            $data['comments'] ?? null,
            $data['url'] ?? null,
            $data['license_type_id'] ?? null,
            $data['category'] ?? null,
            $data['manufacturer_id'] ?? null,
            time(),
            $id
        ];

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete software
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM software WHERE id = ?";
        $stmt = $this->db->execute($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get filter options
     */
    public function getFilterOptions(): array
    {
        return [
        ];
    }

    /**
     * Get associated items for a software
     */
    public function getAssociatedItems(int $softwareId): array
    {
        $sql = "
            SELECT i.id, i.label, i.function, i.model, i.status_id, st.name as status_name,
                   it.name as type_name, l.name as location_name, u.username,
                   a.name as manufacturer_name
            FROM items_software i2s
            INNER JOIN items i ON i2s.item_id = i.id
            LEFT JOIN status_types st ON i.status_id = st.id
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN agents a ON i.manufacturer_id = a.id
            WHERE i2s.software_id = ?
            ORDER BY i.function, i.label
        ";
        return $this->db->fetchAll($sql, [$softwareId]);
    }

    /**
     * Get associated invoices for a software
     */
    public function getAssociatedInvoices(int $softwareId): array
    {
        $sql = "
            SELECT inv.id, inv.invoice_number, inv.invoice_date, inv.total_cost, inv.comments,
                   vendor.name as vendor_name, buyer.name as buyer_name
            FROM software_invoices s2i
            INNER JOIN invoices inv ON s2i.invoice_id = inv.id
            LEFT JOIN agents vendor ON inv.vendor_id = vendor.id
            LEFT JOIN agents buyer ON inv.buyer_id = buyer.id
            WHERE s2i.software_id = ?
        ORDER BY inv.invoice_date DESC, inv.id DESC";
        $invoices = $this->db->fetchAll($sql, [$softwareId]);
        return array_map([$this->invoiceModel, 'transformInvoiceForTemplate'], $invoices);
    }

    /**
     * Get associated contracts for a software
     */
    public function getAssociatedContracts(int $softwareId): array
    {
        $sql = "
            SELECT c.id, c.contract_number, c.title, c.start_date, c.end_date,
                   ct.name as type_name, a.name as contractor_name
            FROM contracts_software c2s
            INNER JOIN contracts c ON c2s.contract_id = c.id
            LEFT JOIN contract_types ct ON c.contract_type_id = ct.id
            LEFT JOIN agents a ON c.contractor_id = a.id
            WHERE c2s.software_id = ?
            ORDER BY c.start_date DESC, c.id DESC
        ";
        return $this->db->fetchAll($sql, [$softwareId]);
    }

    /**
     * Get associated files for a software
     */
    public function getAssociatedFiles(int $softwareId): array
    {
        $sql = "
            SELECT f.id, f.title, f.filename_original, f.filename_stored, f.description, f.uploaded_at, f.file_size,
                   f.file_type_id, ft.name as type_name, f.uploader_username
            FROM software_files s2f
            INNER JOIN files f ON s2f.file_id = f.id
            LEFT JOIN file_types ft ON f.file_type_id = ft.id
            WHERE s2f.software_id = ?
            ORDER BY f.uploaded_at DESC, f.id DESC
        ";
        $files = $this->db->fetchAll($sql, [$softwareId]);

        // Enhance each file with template-compatible data
        foreach ($files as &$file) {
            // Add fileType object for template compatibility
            $file['fileType'] = [
                'name' => $file['type_name']
            ];

            // Format upload date
            if ($file['uploaded_at']) {
                $file['uploaded_at_formatted'] = date('M j, Y g:i A', (int) $file['uploaded_at']);
            } else {
                $file['uploaded_at_formatted'] = null;
            }

            // Add uploader_username info (if needed in future)
            if ($file['uploader_username']) {
                $user = $this->db->fetchOne(
                    "SELECT username, display_name FROM users WHERE username = :username",
                    ['username' => $file['uploader_username']]
                );
                $file['uploader_user'] = [
                    'username' => $file['uploader_username'],
                    'display_name' => $user['display_name'] ?? $file['uploader_username']
                ];
            }
        }

        return $files;
    }

    /**
     * Get available items for association (not already associated)
     */
    public function getAvailableItems(int $softwareId): array
    {
        $sql = "
            SELECT i.id, i.label, i.function, st.name as status_name,
                   it.name as type_name, l.name as location_name
            FROM items i
            LEFT JOIN status_types st ON i.status_id = st.id
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN locations l ON i.location_id = l.id
            WHERE i.id NOT IN (
                SELECT item_id FROM items_software WHERE software_id = ?
            )
            ORDER BY i.function, i.label
            LIMIT 100
        ";
        return $this->db->fetchAll($sql, [$softwareId]);
    }

    /**
     * Add association between software and item
     */
    public function associateItem(int $softwareId, int $itemId): bool
    {
        $sql = "INSERT OR IGNORE INTO items_software (software_id, item_id) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $itemId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and item
     */
    public function dissociateItem(int $softwareId, int $itemId): bool
    {
        $sql = "DELETE FROM items_software WHERE software_id = ? AND item_id = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $itemId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Add association between software and invoice
     */
    public function associateInvoice(int $softwareId, int $invoiceId): bool
    {
        $sql = "INSERT OR IGNORE INTO software_invoices (software_id, invoice_id) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $invoiceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and invoice
     */
    public function dissociateInvoice(int $softwareId, int $invoiceId): bool
    {
        $sql = "DELETE FROM software_invoices WHERE software_id = ? AND invoice_id = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $invoiceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Add association between software and contract
     */
    public function associateContract(int $softwareId, int $contractId): bool
    {
        $sql = "INSERT OR IGNORE INTO contracts_software (software_id, contract_id) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $contractId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and contract
     */
    public function dissociateContract(int $softwareId, int $contractId): bool
    {
        $sql = "DELETE FROM contracts_software WHERE software_id = ? AND contract_id = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $contractId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Add association between software and file
     */
    public function associateFile(int $softwareId, int $fileId): bool
    {
        $sql = "INSERT OR IGNORE INTO software_files (software_id, file_id) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $fileId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and file
     */
    public function dissociateFile(int $softwareId, int $fileId): bool
    {
        $sql = "DELETE FROM software_files WHERE software_id = ? AND file_id = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $fileId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get associated tags for software
     */
    public function getAssociatedTags(int $softwareId): array
    {
        $sql = "
            SELECT t.id, t.name, t.color
            FROM software_tags t2s
            INNER JOIN tags t ON t2s.tag_id = t.id
            WHERE t2s.software_id = ?
            ORDER BY t.name ASC
        ";
        return $this->db->fetchAll($sql, [$softwareId]);
    }

    /**
     * Get all available tags
     */
    public function getAllTags(): array
    {
        $sql = "SELECT id, name, color FROM tags ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Add association between software and tag
     */
    public function associateTag(int $softwareId, int $tagId): bool
    {
        $sql = "INSERT OR IGNORE INTO software_tags (software_id, tag_id) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $tagId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and tag
     */
    public function dissociateTag(int $softwareId, int $tagId): bool
    {
        $sql = "DELETE FROM software_tags WHERE software_id = ? AND tag_id = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $tagId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get total count of software
     */
    public function getCount(): int
    {
        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM software");
    }
}