<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class ItemModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Get paginated items with optional filters
     */
    public function getPaginated(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $conditions[] = "(function LIKE ? OR model LIKE ? OR sn LIKE ? OR label LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (!empty($filters['type'])) {
            $conditions[] = "itemtypeid = ?";
            $params[] = (int) $filters['type'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = (int) $filters['status'];
        }

        if (!empty($filters['location'])) {
            $conditions[] = "locationid = ?";
            $params[] = (int) $filters['location'];
        }

        if (!empty($filters['user'])) {
            $conditions[] = "userid = ?";
            $params[] = (int) $filters['user'];
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        // Get total count
        $countSql = "SELECT COUNT(*) FROM items {$whereClause}";
        $total = $this->db->fetchColumn($countSql, $params);

        // Get items with basic info
        $sortBy = $filters['sort'] ?? 'id';
        $sortOrder = $filters['order'] ?? 'desc';

        // Validate sort column to prevent SQL injection
        $allowedSorts = ['id', 'function', 'model', 'sn', 'status', 'locationid', 'userid'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT i.*,
                   u.username,
                   it.name as itemtype_name,
                   st.statusdesc as status_name,
                   l.name as location_name,
                   a.title as manufacturer_name
            FROM items i
            LEFT JOIN users u ON i.userid = u.id
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN statustypes st ON i.status = st.id
            LEFT JOIN locations l ON i.locationid = l.id
            LEFT JOIN agents a ON i.manufacturerid = a.id
            {$whereClause}
            ORDER BY i.{$sortBy} {$sortOrder}
            LIMIT ? OFFSET ?
        ";

        $params[] = $perPage;
        $params[] = $offset;

        $items = $this->db->fetchAll($sql, $params);

        // Transform items for display
        $transformedItems = array_map(function ($item) {
            // Calculate warranty
            if ($item['purchasedate'] && $item['warrantymonths']) {
                $purchaseDate = new \DateTime(date('Y-m-d', $item['purchasedate']));
                $warrantyEnd = $purchaseDate->add(new \DateInterval('P' . $item['warrantymonths'] . 'M'));
                $now = new \DateTime();
                $diff = $now->diff($warrantyEnd);
                $item['warranty_status'] = $diff->invert ? 'Expired' : 'Active';
                $item['warranty_days_left'] = $diff->invert ? 0 : $diff->days;
            } else {
                $item['warranty_status'] = 'N/A';
                $item['warranty_days_left'] = null;
            }

            // Format last updated
            if ($item['updated_at']) {
                $item['last_updated'] = date('Y-m-d', $item['updated_at']);
            } else {
                $item['last_updated'] = 'N/A';
            }

            return $item;
        }, $items);

        return [
            'data' => $transformedItems,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Find item by ID with related data
     */
    public function findWithRelations(int $id): ?array
    {
        $sql = "
            SELECT i.*,
                   u.username,
                   it.name as itemtype_name,
                   st.statusdesc as status_name,
                   l.name as location_name,
                   r.label as rack_name,
                   a.title as manufacturer_name
            FROM items i
            LEFT JOIN users u ON i.userid = u.id
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN statustypes st ON i.status = st.id
            LEFT JOIN locations l ON i.locationid = l.id
            LEFT JOIN racks r ON i.rackid = r.id
            LEFT JOIN agents a ON i.manufacturerid = a.id
            WHERE i.id = ?
            LIMIT 1
        ";

        $result = $this->db->fetchAll($sql, [$id]);
        if (!$result) {
            return null;
        }

        $item = $result[0];

        // Load all associations with count structure
        return $this->enrichItemWithAssociations($item);
    }

    /**
     * Create new item
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO items (
                function, itemtypeid, status, manufacturerid, model, sn, label,
                comments, maintenanceinfo, userid, locationid, locareaid, rackid,
                rackposition, purchasedate, warrantymonths, ipv4, hd, cpu, ram, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $params = [
            $data['function'] ?? null,
            $data['itemtypeid'] ?? null,
            $data['status'] ?? 1,
            $data['manufacturerid'] ?? null,
            $data['model'] ?? null,
            $data['sn'] ?? null,
            $data['label'] ?? null,
            $data['comments'] ?? null,
            $data['maintenanceinfo'] ?? null,
            $data['userid'] ?? null,
            $data['locationid'] ?? null,
            $data['locareaid'] ?? null,
            $data['rackid'] ?? null,
            $data['rackposition'] ?? null,
            $data['purchasedate'] ?? null,
            $data['warrantymonths'] ?? null,
            $data['ipv4'] ?? null,
            $data['hd'] ?? null,
            $data['cpu'] ?? null,
            $data['ram'] ?? null,
            time()
        ];

        $this->db->execute($sql, $params);
        return $this->db->getLastInsertId();
    }

    /**
     * Update item
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE items SET
                function = ?, itemtypeid = ?, status = ?, manufacturerid = ?,
                model = ?, sn = ?, label = ?, comments = ?, maintenanceinfo = ?,
                userid = ?, locationid = ?, locareaid = ?, rackid = ?, rackposition = ?,
                purchasedate = ?, warrantymonths = ?, ipv4 = ?, hd = ?, cpu = ?, ram = ?, updated_at = ?
            WHERE id = ?
        ";

        $params = [
            $data['function'] ?? null,
            $data['itemtypeid'] ?? null,
            $data['status'] ?? 1,
            $data['manufacturerid'] ?? null,
            $data['model'] ?? null,
            $data['sn'] ?? null,
            $data['label'] ?? null,
            $data['comments'] ?? null,
            $data['maintenanceinfo'] ?? null,
            $data['userid'] ?? null,
            $data['locationid'] ?? null,
            $data['locareaid'] ?? null,
            $data['rackid'] ?? null,
            $data['rackposition'] ?? null,
            $data['purchasedate'] ?? null,
            $data['warrantymonths'] ?? null,
            $data['ipv4'] ?? null,
            $data['hd'] ?? null,
            $data['cpu'] ?? null,
            $data['ram'] ?? null,
            time(),
            $id
        ];

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete item
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM items WHERE id = ?";
        $stmt = $this->db->execute($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if item can be deleted
     */
    public function canDelete(int $id): array
    {
        // Items can usually be deleted, but check for any dependencies
        // Add checks here for any relationships that would prevent deletion
        return [
            'can_delete' => true,
            'references' => []
        ];
    }

    /**
     * Get filter options for dropdowns
     */
    public function getFilterOptions(): array
    {
        $itemTypes = $this->db->fetchAll("SELECT id, name FROM itemtypes ORDER BY name");
        $statusTypes = $this->db->fetchAll("SELECT id, statusdesc as name FROM statustypes ORDER BY statusdesc");
        $locations = $this->db->fetchAll("SELECT id, name FROM locations ORDER BY name");
        $users = $this->db->fetchAll("SELECT id, username FROM users ORDER BY username");

        // Get hardware manufacturers (agents with hardware_manufacturer type)
        $manufacturers = $this->db->fetchAll("
            SELECT DISTINCT a.id, a.title
            FROM agents a
            INNER JOIN agent_agent_type aat ON a.id = aat.agent_id
            INNER JOIN agent_types at ON aat.agent_type_id = at.id
            WHERE at.code = 'hardware_manufacturer'
            ORDER BY a.title
        ");

        return [
            'item_types' => $itemTypes,
            'status_types' => $statusTypes,
            'locations' => $locations,
            'users' => $users,
            'manufacturers' => $manufacturers
        ];
    }

    /**
     * Find item by ID (simple)
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM items WHERE id = ? LIMIT 1";
        $result = $this->db->fetchAll($sql, [$id]);
        return $result ? $result[0] : null;
    }

    /**
     * Search items for API endpoints
     */
    public function search(string $query = '', ?int $excludeSoftware = null, int $limit = 20): array
    {
        $conditions = [];
        $params = [];

        // If query is less than 2 characters, just show recent items
        if (strlen($query) >= 2) {
            $conditions[] = "(i.function LIKE :search OR i.model LIKE :search OR i.sn LIKE :search OR i.label LIKE :search OR i.comments LIKE :search" .
                           (is_numeric($query) ? " OR i.id = :search_id" : "") . ")";
            $params['search'] = "%{$query}%";
            if (is_numeric($query)) {
                $params['search_id'] = (int) $query;
            }
        }

        // Exclude items associated with specific software
        if ($excludeSoftware) {
            $conditions[] = "i.id NOT IN (
                SELECT itemid FROM item2soft WHERE softid = :exclude_software
            )";
            $params['exclude_software'] = $excludeSoftware;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT i.*,
                   u.username,
                   it.name as itemtype_name,
                   st.statusdesc as status_name,
                   l.name as location_name
            FROM items i
            LEFT JOIN users u ON i.userid = u.id
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN statustypes st ON i.status = st.id
            LEFT JOIN locations l ON i.locationid = l.id
            $whereClause
            ORDER BY i.id DESC
            LIMIT :limit
        ";

        $params['limit'] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Check if serial number exists
     */
    public function serialNumberExists(string $sn, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM items WHERE sn = :sn";
        $params = ['sn' => $sn];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Check if label/asset tag exists
     */
    public function labelExists(string $label, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM items WHERE label = :label";
        $params = ['label' => $label];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Get total count of items
     */
    public function getCount(): int
    {
        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM items");
    }

    // ================================
    // ASSOCIATION METHODS
    // ================================

    /**
     * Get all tags associated with an item
     */
    public function getAssociatedTags(int $itemId): array
    {
        $sql = "
            SELECT t.id, t.name, t.color
            FROM tag2item t2i
            INNER JOIN tags t ON t2i.tagid = t.id
            WHERE t2i.itemid = ?
            ORDER BY t.name ASC
        ";
        return $this->db->fetchAll($sql, [$itemId]);
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
     * Associate a tag with an item
     */
    public function associateTag(int $itemId, int $tagId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO tag2item (itemid, tagid) VALUES (?, ?)";
            $stmt = $this->db->execute($sql, [$itemId, $tagId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove tag association from an item
     */
    public function dissociateTag(int $itemId, int $tagId): bool
    {
        $sql = "DELETE FROM tag2item WHERE itemid = ? AND tagid = ?";
        $stmt = $this->db->execute($sql, [$itemId, $tagId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all software associated with an item
     */
    public function getAssociatedSoftware(int $itemId): array
    {
        $sql = "
            SELECT s.id, s.stitle as name, s.sversion as version, lt.name as license_type
            FROM item2soft i2s
            INNER JOIN software s ON i2s.softid = s.id
            LEFT JOIN license_types lt ON s.slicensetype = lt.id
            WHERE i2s.itemid = ?
            ORDER BY s.stitle ASC
        ";
        return $this->db->fetchAll($sql, [$itemId]);
    }

    /**
     * Associate software with an item
     */
    public function associateSoftware(int $itemId, int $softwareId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO item2soft (itemid, softid) VALUES (?, ?)";
            $stmt = $this->db->execute($sql, [$itemId, $softwareId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove software association from an item
     */
    public function dissociateSoftware(int $itemId, int $softwareId): bool
    {
        $sql = "DELETE FROM item2soft WHERE itemid = ? AND softid = ?";
        $stmt = $this->db->execute($sql, [$itemId, $softwareId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all invoices associated with an item
     */
    public function getAssociatedInvoices(int $itemId): array
    {
        $sql = "
            SELECT i.id, i.date, i.totalcost, i.comments,
                   a.title as vendor_title
            FROM item2inv i2i
            INNER JOIN invoices i ON i2i.invid = i.id
            LEFT JOIN agents a ON i.vendorid = a.id
            WHERE i2i.itemid = ?
            ORDER BY i.date DESC
        ";
        $invoices = $this->db->fetchAll($sql, [$itemId]);

        // Format data
        foreach ($invoices as &$invoice) {
            $invoice['date_formatted'] = $invoice['date'] ? date('Y-m-d', $invoice['date']) : 'N/A';
            $invoice['total_formatted'] = number_format($invoice['totalcost'] ?? 0, 2);
        }

        return $invoices;
    }

    /**
     * Associate invoice with an item
     */
    public function associateInvoice(int $itemId, int $invoiceId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO item2inv (itemid, invid) VALUES (?, ?)";
            $stmt = $this->db->execute($sql, [$itemId, $invoiceId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove invoice association from an item
     */
    public function dissociateInvoice(int $itemId, int $invoiceId): bool
    {
        $sql = "DELETE FROM item2inv WHERE itemid = ? AND invid = ?";
        $stmt = $this->db->execute($sql, [$itemId, $invoiceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all contracts associated with an item
     */
    public function getAssociatedContracts(int $itemId): array
    {
        $sql = "
            SELECT c.id, c.title, c.startdate, c.currentenddate as enddate,
                   a.title as contractor_name
            FROM contract2item c2i
            INNER JOIN contracts c ON c2i.contractid = c.id
            LEFT JOIN agents a ON c.contractorid = a.id
            WHERE c2i.itemid = ?
            ORDER BY c.startdate DESC
        ";
        $contracts = $this->db->fetchAll($sql, [$itemId]);

        // Format data
        foreach ($contracts as &$contract) {
            $contract['startdate'] = $contract['startdate'] ? date('Y-m-d', $contract['startdate']) : 'N/A';
            $contract['enddate'] = $contract['enddate'] ? date('Y-m-d', $contract['enddate']) : 'N/A';
        }

        return $contracts;
    }

    /**
     * Associate contract with an item
     */
    public function associateContract(int $itemId, int $contractId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO contract2item (itemid, contractid) VALUES (?, ?)";
            $stmt = $this->db->execute($sql, [$itemId, $contractId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove contract association from an item
     */
    public function dissociateContract(int $itemId, int $contractId): bool
    {
        $sql = "DELETE FROM contract2item WHERE itemid = ? AND contractid = ?";
        $stmt = $this->db->execute($sql, [$itemId, $contractId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all files associated with an item
     */
    public function getAssociatedFiles(int $itemId): array
    {
        $sql = "
            SELECT f.id, f.fname, f.title, f.filesize as file_size,
                   f.uploaddate, ft.typedesc as filetype_name
            FROM item2file i2f
            INNER JOIN files f ON i2f.fileid = f.id
            LEFT JOIN filetypes ft ON f.ftype = ft.id
            WHERE i2f.itemid = ?
            ORDER BY f.uploaddate DESC
        ";
        $files = $this->db->fetchAll($sql, [$itemId]);

        // Format data
        foreach ($files as &$file) {
            $file['uploaddate_formatted'] = $file['uploaddate'] ? date('Y-m-d', $file['uploaddate']) : 'N/A';
        }

        return $files;
    }

    /**
     * Associate file with an item
     */
    public function associateFile(int $itemId, int $fileId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO item2file (itemid, fileid) VALUES (?, ?)";
            $stmt = $this->db->execute($sql, [$itemId, $fileId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove file association from an item
     */
    public function dissociateFile(int $itemId, int $fileId): bool
    {
        $sql = "DELETE FROM item2file WHERE itemid = ? AND fileid = ?";
        $stmt = $this->db->execute($sql, [$itemId, $fileId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all related items (using itemlink table)
     */
    public function getRelatedItems(int $itemId): array
    {
        $sql = "
            SELECT i.id, i.label, i.function, it.name as type_name,
                   l.name as location_name, u.username
            FROM itemlink il
            INNER JOIN items i ON (il.itemid2 = i.id AND il.itemid1 = ?)
                                OR (il.itemid1 = i.id AND il.itemid2 = ?)
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN locations l ON i.locationid = l.id
            LEFT JOIN users u ON i.userid = u.id
            WHERE i.id != ?
            ORDER BY i.label ASC
        ";
        return $this->db->fetchAll($sql, [$itemId, $itemId, $itemId]);
    }

    /**
     * Associate item with another item
     */
    public function associateItem(int $itemId, int $relatedItemId): bool
    {
        try {
            // Check if association already exists in either direction
            $sql = "SELECT COUNT(*) FROM itemlink
                    WHERE (itemid1 = ? AND itemid2 = ?)
                       OR (itemid1 = ? AND itemid2 = ?)";
            $exists = $this->db->fetchColumn($sql, [$itemId, $relatedItemId, $relatedItemId, $itemId]);

            if ($exists > 0) {
                return false; // Already associated
            }

            $sql = "INSERT INTO itemlink (itemid1, itemid2) VALUES (?, ?)";
            $stmt = $this->db->execute($sql, [$itemId, $relatedItemId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove item association
     */
    public function dissociateItem(int $itemId, int $relatedItemId): bool
    {
        $sql = "DELETE FROM itemlink
                WHERE (itemid1 = ? AND itemid2 = ?)
                   OR (itemid1 = ? AND itemid2 = ?)";
        $stmt = $this->db->execute($sql, [$itemId, $relatedItemId, $relatedItemId, $itemId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Enrich item with association counts and data
     */
    public function enrichItemWithAssociations(array $item): array
    {
        $itemId = (int) $item['id'];

        // Get association counts
        $tagsData = $this->getAssociatedTags($itemId);
        $softwareData = $this->getAssociatedSoftware($itemId);
        $invoicesData = $this->getAssociatedInvoices($itemId);
        $contractsData = $this->getAssociatedContracts($itemId);
        $filesData = $this->getAssociatedFiles($itemId);
        $relatedItemsData = $this->getRelatedItems($itemId);

        // Structure data with counts for template badges
        $item['tags'] = ['count' => count($tagsData), 'data' => $tagsData];
        $item['software'] = ['count' => count($softwareData), 'data' => $softwareData];
        $item['invoices'] = ['count' => count($invoicesData), 'data' => $invoicesData];
        $item['contracts'] = ['count' => count($contractsData), 'data' => $contractsData];
        $item['files'] = ['count' => count($filesData), 'data' => $filesData];
        $item['related_items'] = ['count' => count($relatedItemsData), 'data' => $relatedItemsData];

        return $item;
    }
}
