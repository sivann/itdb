<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class LocationModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find a location by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM locations WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get all locations
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM locations ORDER BY name");
    }

    /**
     * Get paginated locations with filtering
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $whereConditions[] = "(name LIKE :search OR floor LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['floor'])) {
            $whereConditions[] = "floor = :floor";
            $params['floor'] = $filters['floor'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM locations $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get locations with limit
        $sql = "
            SELECT *
            FROM locations
            $whereClause
            ORDER BY name
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $locations = $this->db->fetchAll($sql, $params);

        return [
            'data' => $locations,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create a new location
     */
    public function create(array $data): int
    {
        $allowedFields = ['name', 'floor', 'floorplanfn'];
        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('locations', $insertData);
    }

    /**
     * Update a location
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name', 'floor', 'floorplanfn'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->db->update('locations', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete a location
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('locations', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Check if location can be deleted
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check items using this location
        $itemCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE locationid = :id",
            ['id' => $id]
        );
        if ($itemCount > 0) {
            $references[] = "$itemCount item(s)";
        }

        // Check racks using this location
        $rackCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM racks WHERE locationid = :id",
            ['id' => $id]
        );
        if ($rackCount > 0) {
            $references[] = "$rackCount rack(s)";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    /**
     * Get distinct floors
     */
    public function getFloors(): array
    {
        $result = $this->db->fetchAll("
            SELECT DISTINCT floor
            FROM locations
            WHERE floor IS NOT NULL AND floor != ''
            ORDER BY floor
        ");

        return array_column($result, 'floor');
    }

    /**
     * Find location with relationships (items, racks, areas)
     */
    public function findWithRelations(int $id): ?array
    {
        $location = $this->find($id);
        if (!$location) {
            return null;
        }

        // Get items count
        $location['items_count'] = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE locationid = :id",
            ['id' => $id]
        );

        // Get racks count
        $location['racks_count'] = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM racks WHERE locationid = :id",
            ['id' => $id]
        );

        // Get areas count
        $location['areas_count'] = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM locareas WHERE locationid = :id",
            ['id' => $id]
        );

        return $location;
    }

    /**
     * Find location with counts
     */
    public function findWithCounts(int $id): ?array
    {
        $sql = "
            SELECT l.*,
                   COALESCE(items.count, 0) as items_count,
                   COALESCE(racks.count, 0) as racks_count,
                   COALESCE(areas.count, 0) as areas_count
            FROM locations l
            LEFT JOIN (
                SELECT locationid, COUNT(*) as count
                FROM items
                WHERE locationid = :id
                GROUP BY locationid
            ) items ON l.id = items.locationid
            LEFT JOIN (
                SELECT locationid, COUNT(*) as count
                FROM racks
                WHERE locationid = :id
                GROUP BY locationid
            ) racks ON l.id = racks.locationid
            LEFT JOIN (
                SELECT locationid, COUNT(*) as count
                FROM locareas
                WHERE locationid = :id
                GROUP BY locationid
            ) areas ON l.id = areas.locationid
            WHERE l.id = :id
            LIMIT 1
        ";

        $result = $this->db->fetchAll($sql, ['id' => $id]);
        return $result ? $result[0] : null;
    }

    /**
     * Get areas for a location
     */
    public function getAreas(int $locationId): array
    {
        return $this->db->fetchAll(
            "SELECT id, areaname as name FROM locareas WHERE locationid = :location_id ORDER BY areaname",
            ['location_id' => $locationId]
        );
    }
}