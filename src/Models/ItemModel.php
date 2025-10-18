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
            $conditions[] = "(function LIKE ? OR model LIKE ? OR serial_number LIKE ? OR label LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (!empty($filters['type'])) {
            $conditions[] = "item_type_id = ?";
            $params[] = (int) $filters['type'];
        }

        if (!empty($filters['status_id'])) {
            $conditions[] = "status_id = ?";
            $params[] = (int) $filters['status_id'];
        }

        if (!empty($filters['location'])) {
            $conditions[] = "location_id = ?";
            $params[] = (int) $filters['location'];
        }

        if (!empty($filters['user'])) {
            $conditions[] = "user_id = ?";
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
        $allowedSorts = ['id', 'function', 'model', 'serial_number', 'status_id', 'location_id', 'user_id'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT i.*,
                   u.username,
                   it.name as itemtype_name,
                   st.name as status_name,
                   l.name as location_name,
                   a.name as manufacturer_name
            FROM items i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN status_types st ON i.status_id = st.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN agents a ON i.manufacturer_id = a.id
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
            if ($item['purchase_date'] && $item['warranty_months']) {
                $purchaseDate = new \DateTime(date('Y-m-d', $item['purchase_date']));
                $warrantyEnd = $purchaseDate->add(new \DateInterval('P' . $item['warranty_months'] . 'M'));
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
                   st.name as status_name,
                   l.name as location_name,
                   r.label as rack_name,
                   a.name as manufacturer_name
            FROM items i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN status_types st ON i.status_id = st.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN racks r ON i.rack_id = r.id
            LEFT JOIN agents a ON i.manufacturer_id = a.id
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
                function, item_type_id, status_id, manufacturer_id, model, serial_number, serial_number_2, serial_number_3, label,
                comments, maintenance_info, user_id, location_id, location_area_id, rack_id,
                rack_position, rack_position_depth, purchase_date, warranty_months, warranty_info, ipv4_address, ipv6_address, mac_addresses, dns_name, 
                hard_drive, cpu, ram, cpu_count, cores_per_cpu, port_count, is_rack_mountable, is_part, origin, purchase_price, remote_admin_ip, panel_port, switch_port, switch_id, certificate_of_authenticity, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $params = [
            $data['function'] ?? null,
            $data['item_type_id'] ?? null,
            $data['status_id'] ?? 1,
            $data['manufacturer_id'] ?? null,
            $data['model'] ?? null,
            $data['serial_number'] ?? null,
            $data['serial_number_2'] ?? null,
            $data['serial_number_3'] ?? null,
            $data['label'] ?? null,
            $data['comments'] ?? null,
            $data['maintenance_info'] ?? null,
            $data['user_id'] ?? null,
            $data['location_id'] ?? null,
            $data['location_area_id'] ?? null,
            $data['rack_id'] ?? null,
            $data['rack_position'] ?? null,
            $data['rack_position_depth'] ?? null,
            $data['purchase_date'] ?? null,
            $data['warranty_months'] ?? null,
            $data['warranty_info'] ?? null,
            $data['ipv4_address'] ?? null,
            $data['ipv6_address'] ?? null,
            $data['mac_addresses'] ?? null,
            $data['dns_name'] ?? null,
            $data['hard_drive'] ?? null,
            $data['cpu'] ?? null,
            $data['ram'] ?? null,
            $data['cpu_count'] ?? null,
            $data['cores_per_cpu'] ?? null,
            $data['port_count'] ?? null,
            $data['is_rack_mountable'] ?? null,
            $data['is_part'] ?? null,
            $data['origin'] ?? null,
            $data['purchase_price'] ?? null,
            $data['remote_admin_ip'] ?? null,
            $data['panel_port'] ?? null,
            $data['switch_port'] ?? null,
            $data['switch_id'] ?? null,
            $data['certificate_of_authenticity'] ?? null,
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
                function = ?, item_type_id = ?, status_id = ?, manufacturer_id = ?, model = ?, serial_number = ?, serial_number_2 = ?, serial_number_3 = ?, label = ?,
                comments = ?, maintenance_info = ?, user_id = ?, location_id = ?, location_area_id = ?, rack_id = ?,
                rack_position = ?, rack_position_depth = ?, purchase_date = ?, warranty_months = ?, warranty_info = ?, ipv4_address = ?, ipv6_address = ?, mac_addresses = ?, dns_name = ?,
                hard_drive = ?, cpu = ?, ram = ?, cpu_count = ?, cores_per_cpu = ?, port_count = ?, is_rack_mountable = ?, is_part = ?, origin = ?, purchase_price = ?, remote_admin_ip = ?, panel_port = ?, switch_port = ?, switch_id = ?, certificate_of_authenticity = ?, updated_at = ?
            WHERE id = ?
        ";

        $params = [
            $data['function'] ?? null,
            $data['item_type_id'] ?? null,
            $data['status_id'] ?? 1,
            $data['manufacturer_id'] ?? null,
            $data['model'] ?? null,
            $data['serial_number'] ?? null,
            $data['serial_number_2'] ?? null,
            $data['serial_number_3'] ?? null,
            $data['label'] ?? null,
            $data['comments'] ?? null,
            $data['maintenance_info'] ?? null,
            $data['user_id'] ?? null,
            $data['location_id'] ?? null,
            $data['location_area_id'] ?? null,
            $data['rack_id'] ?? null,
            $data['rack_position'] ?? null,
            $data['rack_position_depth'] ?? null,
            $data['purchase_date'] ?? null,
            $data['warranty_months'] ?? null,
            $data['warranty_info'] ?? null,
            $data['ipv4_address'] ?? null,
            $data['ipv6_address'] ?? null,
            $data['mac_addresses'] ?? null,
            $data['dns_name'] ?? null,
            $data['hard_drive'] ?? null,
            $data['cpu'] ?? null,
            $data['ram'] ?? null,
            $data['cpu_count'] ?? null,
            $data['cores_per_cpu'] ?? null,
            $data['port_count'] ?? null,
            $data['is_rack_mountable'] ?? null,
            $data['is_part'] ?? null,
            $data['origin'] ?? null,
            $data['purchase_price'] ?? null,
            $data['remote_admin_ip'] ?? null,
            $data['panel_port'] ?? null,
            $data['switch_port'] ?? null,
            $data['switch_id'] ?? null,
            $data['certificate_of_authenticity'] ?? null,
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
        $itemTypes = $this->db->fetchAll("SELECT id, name FROM item_types ORDER BY name");
        $statusTypes = $this->db->fetchAll("SELECT id, name FROM status_types ORDER BY name");
        $locations = $this->db->fetchAll("SELECT id, name FROM locations ORDER BY name");
        $users = $this->db->fetchAll("SELECT id, username FROM users ORDER BY username");
        $racks = $this->db->fetchAll("SELECT id, label, model, size_units, location_id FROM racks ORDER BY label");

        // Get hardware manufacturers (agents with hardware_manufacturer type)
        $manufacturers = $this->db->fetchAll("
            SELECT DISTINCT a.id, a.name
            FROM agents a
            INNER JOIN agent_agent_type aat ON a.id = aat.agent_id
            INNER JOIN agent_types at ON aat.agent_type_id = at.id
            WHERE at.code = 'hardware_manufacturer'
            ORDER BY a.name
        ");

        return [
            'item_types' => $itemTypes,
            'status_types' => $statusTypes,
            'locations' => $locations,
            'users' => $users,
            'racks' => $racks,
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
            $conditions[] = "(i.function LIKE :search OR i.model LIKE :search OR i.serial_number LIKE :search OR i.label LIKE :search OR i.comments LIKE :search" .
                           (is_numeric($query) ? " OR i.id = :search_id" : "") . ")";
            $params['search'] = "%{$query}%";
            if (is_numeric($query)) {
                $params['search_id'] = (int) $query;
            }
        }

        // Exclude items associated with specific software
        if ($excludeSoftware) {
            $conditions[] = "i.id NOT IN (
                SELECT item_id FROM items_software WHERE software_id = :exclude_software
            )";
            $params['exclude_software'] = $excludeSoftware;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT i.*,
                   u.username,
                   it.name as itemtype_name,
                   st.name as status_name,
                   l.name as location_name
            FROM items i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN status_types st ON i.status_id = st.id
            LEFT JOIN locations l ON i.location_id = l.id
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
    public function serialNumberExists(string $serial_number, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM items WHERE serial_number = :serial_number";
        $params = ['serial_number' => $serial_number];

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
            FROM items_tags t2i
            INNER JOIN tags t ON t2i.tag_id = t.id
            WHERE t2i.item_id = ?
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
            $sql = "INSERT OR IGNORE INTO items_tags (item_id, tag_id) VALUES (?, ?)";
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
        $sql = "DELETE FROM items_tags WHERE item_id = ? AND tag_id = ?";
        $stmt = $this->db->execute($sql, [$itemId, $tagId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all software associated with an item
     */
    public function getAssociatedSoftware(int $itemId): array
    {
        $sql = "
            SELECT s.id, s.title as name, s.version, lt.name as license_type, a.name as manufacturer_name
            FROM items_software i2s
            INNER JOIN software s ON i2s.software_id = s.id
            LEFT JOIN license_types lt ON s.license_type_id = lt.id
            LEFT JOIN agents a ON s.manufacturer_id = a.id
            WHERE i2s.item_id = ?
            ORDER BY s.title ASC
        ";
        return $this->db->fetchAll($sql, [$itemId]);
    }

    /**
     * Associate software with an item
     */
    public function associateSoftware(int $itemId, int $softwareId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO items_software (item_id, software_id) VALUES (?, ?)";
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
        $sql = "DELETE FROM items_software WHERE item_id = ? AND software_id = ?";
        $stmt = $this->db->execute($sql, [$itemId, $softwareId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all invoices associated with an item
     */
    public function getAssociatedInvoices(int $itemId): array
    {
        $sql = "
            SELECT i.id, i.invoice_number as number, i.invoice_date, i.total_cost, i.comments,
                   a.name as vendor_title
            FROM items_invoices i2i
            INNER JOIN invoices i ON i2i.invoice_id = i.id
            LEFT JOIN agents a ON i.vendor_id = a.id
            WHERE i2i.item_id = ?
            ORDER BY i.invoice_date DESC
        ";
        $invoices = $this->db->fetchAll($sql, [$itemId]);

        // Format data
        foreach ($invoices as &$invoice) {
            $invoice['date_formatted'] = $invoice['invoice_date'] ? date('Y-m-d', $invoice['invoice_date']) : 'N/A';
            $invoice['total_formatted'] = number_format($invoice['total_cost'] ?? 0, 2);
        }

        return $invoices;
    }

    /**
     * Associate invoice with an item
     */
    public function associateInvoice(int $itemId, int $invoiceId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO items_invoices (item_id, invoice_id) VALUES (?, ?)";
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
        $sql = "DELETE FROM items_invoices WHERE item_id = ? AND invoice_id = ?";
        $stmt = $this->db->execute($sql, [$itemId, $invoiceId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all contracts associated with an item
     */
    public function getAssociatedContracts(int $itemId): array
    {
        $sql = "
            SELECT c.id, c.title, c.start_date, c.end_date,
                   a.name as contractor_name
            FROM contracts_items c2i
            INNER JOIN contracts c ON c2i.contract_id = c.id
            LEFT JOIN agents a ON c.contractor_id = a.id
            WHERE c2i.item_id = ?
            ORDER BY c.start_date DESC
        ";
        $contracts = $this->db->fetchAll($sql, [$itemId]);

        // Format data
        foreach ($contracts as &$contract) {
            $contract['start_date'] = $contract['start_date'] ? date('Y-m-d', $contract['start_date']) : 'N/A';
            $contract['end_date'] = $contract['end_date'] ? date('Y-m-d', $contract['end_date']) : 'N/A';
        }

        return $contracts;
    }

    /**
     * Associate contract with an item
     */
    public function associateContract(int $itemId, int $contractId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO contracts_items (item_id, contract_id) VALUES (?, ?)";
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
        $sql = "DELETE FROM contracts_items WHERE item_id = ? AND contract_id = ?";
        $stmt = $this->db->execute($sql, [$itemId, $contractId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all files associated with an item
     */
    public function getAssociatedFiles(int $itemId): array
    {
        $sql = "
            SELECT f.id, f.filename_stored, f.title, f.file_size,
                   f.uploaded_at, ft.name as filetype_name
            FROM items_files i2f
            INNER JOIN files f ON i2f.file_id = f.id
            LEFT JOIN file_types ft ON f.file_type_id = ft.id
            WHERE i2f.item_id = ?
            ORDER BY f.uploaded_at DESC
        ";
        $files = $this->db->fetchAll($sql, [$itemId]);

        // Format data
        foreach ($files as &$file) {
            $file['uploaddate_formatted'] = $file['uploaded_at'] ? date('Y-m-d', (int)$file['uploaded_at']) : 'N/A';
        }

        return $files;
    }

    /**
     * Associate file with an item
     */
    public function associateFile(int $itemId, int $fileId): bool
    {
        try {
            $sql = "INSERT OR IGNORE INTO items_files (item_id, file_id) VALUES (?, ?)";
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
        $sql = "DELETE FROM items_files WHERE item_id = ? AND file_id = ?";
        $stmt = $this->db->execute($sql, [$itemId, $fileId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all related items (using itemlink table)
     */
    public function getRelatedItems(int $itemId): array
    {
        $sql = "
            SELECT i.id, i.label, i.function, i.model, it.name as type_name,
                   l.name as location_name, u.username, a.name as manufacturer_name
            FROM itemlink il
            INNER JOIN items i ON (il.itemid2 = i.id AND il.itemid1 = ?)
                                OR (il.itemid1 = i.id AND il.itemid2 = ?)
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN agents a ON i.manufacturer_id = a.id
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
