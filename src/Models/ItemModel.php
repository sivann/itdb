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
                   la.name as locationarea_name,
                   r.name as rack_name,
                   a.title as manufacturer_name
            FROM items i
            LEFT JOIN users u ON i.userid = u.id
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN statustypes st ON i.status = st.id
            LEFT JOIN locations l ON i.locationid = l.id
            LEFT JOIN locareas la ON i.locareaid = la.id
            LEFT JOIN racks r ON i.rackid = r.id
            LEFT JOIN agents a ON i.manufacturerid = a.id
            WHERE i.id = ?
            LIMIT 1
        ";

        $result = $this->db->fetchAll($sql, [$id]);
        return $result ? $result[0] : null;
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
        $users = $this->db->fetchAll("SELECT id, username FROM users WHERE usertype > 0 ORDER BY username");

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
}
