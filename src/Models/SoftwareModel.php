<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;
use App\Models\InvoiceModel;

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
            $conditions[] = "(stitle LIKE ? OR sversion LIKE ? OR scomments LIKE ?)";
            $params = array_merge($params, [$search, $search, $search]);
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        // Get total count
        $countSql = "SELECT COUNT(*) FROM software {$whereClause}";
        $total = $this->db->fetchColumn($countSql, $params);

        // Get software with manufacturer info and more complete data
        $sql = "
            SELECT s.*, a.title as manufacturer_name,
                   CASE
                       WHEN s.slicense IS NOT NULL AND s.slicense != '' THEN CAST(s.slicense AS INTEGER)
                       ELSE 0
                   END as license_quantity
            FROM software s
            LEFT JOIN agents a ON s.manufacturerid = a.id
            {$whereClause}
            ORDER BY s.id DESC LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;

        $software = $this->db->fetchAll($sql, $params);

        // Transform data to match template expectations
        $transformedSoftware = array_map(function($item) {
            // Basic display info
            $item['display_title'] = $item['stitle'] . ($item['sversion'] ? ' v' . $item['sversion'] : '');
            $item['sinfo'] = $item['scomments'];

            // License quantity from parsed field
            $licenseCount = (int)$item['license_quantity'];
            $item['licqty'] = $licenseCount > 0 ? $licenseCount : null;

            // License type (0=Per Device, 1=Per User, 2=Site License, 3=Volume License)
            $licenseType = !empty($item['slicensetype']) && is_numeric($item['slicensetype']) ? (int)$item['slicensetype'] : 0;
            $item['lictype'] = $licenseType;

            // Installation count (would need pivot table data - placeholder for now)
            $item['installations_count'] = 0;
            $item['available_licenses'] = $licenseCount; // All available for now

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

            // Last updated (would be from updated_at timestamp if available)
            // For now, use a placeholder or null
            $item['purchdate'] = null; // Would be actual purchase date

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
            SELECT s.*, a.title as manufacturer_name,
                   CASE
                       WHEN s.slicense IS NOT NULL AND s.slicense != '' THEN CAST(s.slicense AS INTEGER)
                       ELSE 0
                   END as license_quantity
            FROM software s
            LEFT JOIN agents a ON s.manufacturerid = a.id
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
            "SELECT COUNT(*) FROM item2soft WHERE softid = ?", [$softwareId]
        );

        $software['invoices_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM soft2inv WHERE softid = ?", [$softwareId]
        );

        $software['contracts_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM contract2soft WHERE softid = ?", [$softwareId]
        );

        $software['files_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM software2file WHERE softwareid = ?", [$softwareId]
        );

        $software['tags_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM tag2software WHERE softwareid = ?", [$softwareId]
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
        $software['display_title'] = $software['stitle'] . ($software['sversion'] ? ' v' . $software['sversion'] : '');
        $software['sinfo'] = $software['scomments'];

        // License info
        $licenseCount = (int)($software['license_quantity'] ?? 0);
        $software['licqty'] = $licenseCount > 0 ? $licenseCount : null;
        $software['lictype'] = !empty($software['slicensetype']) && is_numeric($software['slicensetype']) ? (int)$software['slicensetype'] : 0;

        return $software;
    }

    /**
     * Create new software
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO software (stitle, sversion, slicense, scomments, url, slicensetype, scat, manufacturerid, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $params = [
            $data['stitle'] ?? null,
            $data['sversion'] ?? null,
            $data['slicense'] ?? null,
            $data['scomments'] ?? null,
            $data['url'] ?? null,
            $data['slicensetype'] ?? null,
            $data['scat'] ?? null,
            $data['manufacturerid'] ?? null,
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
                stitle = ?, sversion = ?, slicense = ?, scomments = ?,
                url = ?, slicensetype = ?, scat = ?, manufacturerid = ?, updated_at = ?
            WHERE id = ?
        ";

        $params = [
            $data['stitle'] ?? null,
            $data['sversion'] ?? null,
            $data['slicense'] ?? null,
            $data['scomments'] ?? null,
            $data['url'] ?? null,
            $data['slicensetype'] ?? null,
            $data['scat'] ?? null,
            $data['manufacturerid'] ?? null,
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
            'manufacturers' => $this->db->fetchAll("SELECT DISTINCT manufacturer FROM software WHERE manufacturer IS NOT NULL ORDER BY manufacturer"),
            'operating_systems' => $this->db->fetchAll("SELECT DISTINCT os FROM software WHERE os IS NOT NULL ORDER BY os")
        ];
    }

    /**
     * Get associated items for a software
     */
    public function getAssociatedItems(int $softwareId): array
    {
        $sql = "
            SELECT i.id, i.label, i.function, i.status, st.statusdesc as status_name,
                   it.name as type_name, l.name as location_name, u.username
            FROM item2soft i2s
            INNER JOIN items i ON i2s.itemid = i.id
            LEFT JOIN statustypes st ON i.status = st.id
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN locations l ON i.locationid = l.id
            LEFT JOIN users u ON i.userid = u.id
            WHERE i2s.softid = ?
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
            SELECT inv.id, inv.id as invoiceid, inv.date, inv.totalcost, inv.comments,
                   vendor.title as vendor_name, buyer.title as buyer_name
            FROM soft2inv s2i
            INNER JOIN invoices inv ON s2i.invid = inv.id
            LEFT JOIN agents vendor ON inv.vendorid = vendor.id
            LEFT JOIN agents buyer ON inv.buyerid = buyer.id
            WHERE s2i.softid = ?
        ORDER BY inv.date DESC, inv.id DESC";
        $invoices = $this->db->fetchAll($sql, [$softwareId]);
        return array_map([$this->invoiceModel, 'transformInvoiceForTemplate'], $invoices);
    }

    /**
     * Get associated contracts for a software
     */
    public function getAssociatedContracts(int $softwareId): array
    {
        $sql = "
            SELECT c.id, c.number, c.title, c.startdate, c.currentenddate as enddate,
                   ct.name as type_name, a.title as contractor_name
            FROM contract2soft c2s
            INNER JOIN contracts c ON c2s.contractid = c.id
            LEFT JOIN contracttypes ct ON c.type = ct.id
            LEFT JOIN agents a ON c.contractorid = a.id
            WHERE c2s.softid = ?
            ORDER BY c.startdate DESC, c.id DESC
        ";
        return $this->db->fetchAll($sql, [$softwareId]);
    }

    /**
     * Get associated files for a software
     */
    public function getAssociatedFiles(int $softwareId): array
    {
        $sql = "
            SELECT f.id, f.title, f.filename, f.fname, f.description, f.uploaddate, f.filesize,
                   f.type, ft.typedesc as type_name, f.uploader
            FROM software2file s2f
            INNER JOIN files f ON s2f.fileid = f.id
            LEFT JOIN filetypes ft ON f.type = ft.id
            WHERE s2f.softwareid = ?
            ORDER BY f.uploaddate DESC, f.id DESC
        ";
        $files = $this->db->fetchAll($sql, [$softwareId]);

        // Enhance each file with template-compatible data
        foreach ($files as &$file) {
            // Add fileType object for template compatibility
            $file['fileType'] = [
                'name' => $file['type_name']
            ];

            // Add file_size from disk or database
            $file['file_size'] = $file['filesize'] ?? 0;

            // Format upload date
            if ($file['uploaddate']) {
                $file['uploaddate_formatted'] = date('M j, Y g:i A', (int) $file['uploaddate']);
            } else {
                $file['uploaddate_formatted'] = null;
            }

            // Add uploader info (if needed in future)
            if ($file['uploader']) {
                $user = $this->db->fetchOne(
                    "SELECT username, userdesc FROM users WHERE username = :username",
                    ['username' => $file['uploader']]
                );
                $file['uploader_user'] = [
                    'username' => $file['uploader'],
                    'display_name' => $user['userdesc'] ?? $file['uploader']
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
            SELECT i.id, i.label, i.function, st.statusdesc as status_name,
                   it.name as type_name, l.name as location_name
            FROM items i
            LEFT JOIN statustypes st ON i.status = st.id
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN locations l ON i.locationid = l.id
            WHERE i.id NOT IN (
                SELECT itemid FROM item2soft WHERE softid = ?
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
        $sql = "INSERT OR IGNORE INTO item2soft (softid, itemid) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $itemId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and item
     */
    public function dissociateItem(int $softwareId, int $itemId): bool
    {
        $sql = "DELETE FROM item2soft WHERE softid = ? AND itemid = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $itemId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Add association between software and invoice
     */
    public function associateInvoice(int $softwareId, int $invoiceId): bool
    {
        $sql = "INSERT OR IGNORE INTO soft2inv (softid, invid) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $invoiceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and invoice
     */
    public function dissociateInvoice(int $softwareId, int $invoiceId): bool
    {
        $sql = "DELETE FROM soft2inv WHERE softid = ? AND invid = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $invoiceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Add association between software and contract
     */
    public function associateContract(int $softwareId, int $contractId): bool
    {
        $sql = "INSERT OR IGNORE INTO contract2soft (softid, contractid) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $contractId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and contract
     */
    public function dissociateContract(int $softwareId, int $contractId): bool
    {
        $sql = "DELETE FROM contract2soft WHERE softid = ? AND contractid = ?";
        $stmt = $this->db->execute($sql, [$softwareId, $contractId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Add association between software and file
     */
    public function associateFile(int $softwareId, int $fileId): bool
    {
        $sql = "INSERT OR IGNORE INTO software2file (softwareid, fileid) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $fileId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and file
     */
    public function dissociateFile(int $softwareId, int $fileId): bool
    {
        $sql = "DELETE FROM software2file WHERE softwareid = ? AND fileid = ?";
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
            FROM tag2software t2s
            INNER JOIN tags t ON t2s.tagid = t.id
            WHERE t2s.softwareid = ?
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
        $sql = "INSERT OR IGNORE INTO tag2software (softwareid, tagid) VALUES (?, ?)";
        $stmt = $this->db->execute($sql, [$softwareId, $tagId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove association between software and tag
     */
    public function dissociateTag(int $softwareId, int $tagId): bool
    {
        $sql = "DELETE FROM tag2software WHERE softwareid = ? AND tagid = ?";
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