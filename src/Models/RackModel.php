<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

/**
 * Rack data access model - handles all database operations for racks (CRUD, queries, layout).
 */
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
                    la.name as location_area_name
             FROM racks r
             LEFT JOIN locations l ON r.location_id = l.id
             LEFT JOIN location_areas la ON r.location_area_id = la.id
             WHERE r.id = :id",
            ['id' => $id]
        );

        if ($rack) {
            $rack = $this->transformRackForTemplate($rack);

            // Get items in this rack with rack_position_depth
            $rack['items'] = $this->db->fetchAll(
                "SELECT i.id, i.label, i.function, i.rack_position, i.rack_position_depth, i.rack_units,
                        i.model, i.status_id, a.name as manufacturer_name
                 FROM items i
                 LEFT JOIN agents a ON i.manufacturer_id = a.id
                 WHERE i.rack_id = :rack_id
                 ORDER BY i.rack_position",
                ['rack_id' => $id]
            );

            return $rack;
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
            $whereConditions[] = "r.location_id = :location";
            $params['location'] = (int) $filters['location'];
        }

        if (!empty($filters['area'])) {
            $whereConditions[] = "r.location_area_id = :area";
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
                   la.name as location_area_name,
                   COALESCE(item_count.count, 0) as items_count,
                   COALESCE(item_count.occupation, 0) as occupation
            FROM racks r
            LEFT JOIN locations l ON r.location_id = l.id
            LEFT JOIN location_areas la ON r.location_area_id = la.id
            LEFT JOIN (
                SELECT rack_id, COUNT(*) as count, SUM(rack_units) as occupation
                FROM items
                WHERE rack_id IS NOT NULL
                GROUP BY rack_id
            ) item_count ON r.id = item_count.rack_id
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
            'location_id', 'location_area_id', 'label', 'model', 'size_units', 'depth_mm',
            'comments', 'reverse_numbering'
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
            'location_id', 'location_area_id', 'label', 'model', 'size_units', 'depth_mm',
            'comments', 'reverse_numbering'
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
            "SELECT COUNT(*) FROM items WHERE rack_id = :id",
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

        $uSize = (int) $rack['size_units'] ?: 42; // Default to 42U if not specified

        // Get items in this rack
        $items = $this->db->fetchAll(
            "SELECT id, label, function, rack_position, rack_units FROM items WHERE rack_id = :rack_id ORDER BY rack_position",
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
            $position = (int) $item['rack_position'];
            $size = (int) ($item['rack_units'] ?: 1);

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
        $rack['location'] = $rack['location_name'] ? [
            'id' => $rack['location_id'],
            'name' => $rack['location_name'],
            'title' => $rack['location_name']
        ] : null;

        $rack['locationArea'] = $rack['location_area_name'] ? [
            'name' => $rack['location_area_name'],
            'title' => $rack['location_area_name']
        ] : null;

        // Add display name
        $rack['display_name'] = $rack['label'] ?: 'Rack #' . $rack['id'];

        // Ensure numeric fields are properly typed
        $rack['size_units'] = (int) ($rack['size_units'] ?: 0);
        $rack['depth_mm'] = (int) ($rack['depth_mm'] ?: 0);
        $rack['reverse_numbering'] = (int) ($rack['reverse_numbering'] ?: 0);

        // Calculate occupation percentage
        $occupation = (int) ($rack['occupation'] ?? 0);
        $sizeUnits = $rack['size_units'];
        $rack['occupation_percent'] = $sizeUnits > 0 ? (int) (($occupation / $sizeUnits) * 100) : 0;
        $rack['occupation'] = $occupation;

        return $rack;
    }
}