<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class RackModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find a rack by ID
     */
    public function find(int $id): ?array
    {
        $rack = $this->db->fetchOne(
            "SELECT r.*,
                    l.name as location_name,
                    la.areaname as location_area_name
             FROM racks r
             LEFT JOIN locations l ON r.locationid = l.id
             LEFT JOIN locareas la ON r.locareaid = la.id
             WHERE r.id = :id",
            ['id' => $id]
        );

        if ($rack) {
            return $this->transformRackForTemplate($rack);
        }

        return null;
    }

    /**
     * Get paginated racks with filtering
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $whereConditions[] = "(r.label LIKE :search OR r.model LIKE :search OR r.comments LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['location'])) {
            $whereConditions[] = "r.locationid = :location";
            $params['location'] = (int) $filters['location'];
        }

        if (!empty($filters['area'])) {
            $whereConditions[] = "r.locareaid = :area";
            $params['area'] = (int) $filters['area'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM racks r $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get racks with limit
        $sql = "
            SELECT r.*,
                   l.name as location_name,
                   la.areaname as location_area_name
            FROM racks r
            LEFT JOIN locations l ON r.locationid = l.id
            LEFT JOIN locareas la ON r.locareaid = la.id
            $whereClause
            ORDER BY r.label
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $racks = $this->db->fetchAll($sql, $params);

        // Transform racks for template
        $transformedRacks = array_map([$this, 'transformRackForTemplate'], $racks);

        return [
            'data' => $transformedRacks,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create a new rack
     */
    public function create(array $data): int
    {
        $allowedFields = [
            'locationid', 'locareaid', 'label', 'model', 'usize', 'depth',
            'comments', 'revnums'
        ];

        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('racks', $insertData);
    }

    /**
     * Update a rack
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'locationid', 'locareaid', 'label', 'model', 'usize', 'depth',
            'comments', 'revnums'
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->db->update('racks', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete a rack
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('racks', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Get all racks for dropdown (simple list)
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM racks ORDER BY label");
    }

    /**
     * Check if rack can be deleted (not referenced by other records)
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check items that reference this rack
        $itemCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE rackid = :id",
            ['id' => $id]
        );
        if ($itemCount > 0) {
            $references[] = "$itemCount item(s)";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    /**
     * Get rack U positions with usage
     */
    public function getRackLayout(int $rackId): array
    {
        $rack = $this->find($rackId);
        if (!$rack) {
            return [];
        }

        $uSize = (int) $rack['usize'] ?: 42; // Default to 42U if not specified

        // Get items in this rack
        $items = $this->db->fetchAll(
            "SELECT id, iname, rackposition, racksize FROM items WHERE rackid = :rack_id ORDER BY rackposition",
            ['rack_id' => $rackId]
        );

        // Build layout array
        $layout = [];
        for ($u = 1; $u <= $uSize; $u++) {
            $layout[$u] = [
                'position' => $u,
                'available' => true,
                'item' => null
            ];
        }

        // Mark occupied positions
        foreach ($items as $item) {
            $position = (int) $item['rackposition'];
            $size = (int) ($item['racksize'] ?: 1);

            if ($position > 0 && $position <= $uSize) {
                for ($u = $position; $u < $position + $size && $u <= $uSize; $u++) {
                    $layout[$u]['available'] = false;
                    if ($u === $position) {
                        $layout[$u]['item'] = $item;
                    }
                }
            }
        }

        return array_values($layout);
    }

    /**
     * Transform rack data for template compatibility
     */
    private function transformRackForTemplate(array $rack): array
    {
        // Add computed fields that templates expect
        $rack['location'] = $rack['location_name'] ?
            (object)['title' => $rack['location_name']] : null;

        $rack['locationArea'] = $rack['location_area_name'] ?
            (object)['title' => $rack['location_area_name']] : null;

        // Ensure numeric fields are properly typed
        $rack['usize'] = (int) ($rack['usize'] ?: 0);
        $rack['depth'] = (int) ($rack['depth'] ?: 0);
        $rack['revnums'] = (int) ($rack['revnums'] ?: 0);

        return $rack;
    }
}