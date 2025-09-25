<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class AgentModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find an agent by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM agents WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get all agents
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM agents ORDER BY title");
    }

    /**
     * Get agents with pagination and filtering
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $whereConditions[] = "(title LIKE :search OR contactinfo LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['type'])) {
            $typeValue = (int) $filters['type'];

            // Map bitwise values to agent type codes for new system
            $typeMapping = [
                1 => 'vendor',
                2 => 'software_manufacturer',
                4 => 'hardware_manufacturer',
                8 => 'buyer',
                16 => 'contractor'
            ];

            if (isset($typeMapping[$typeValue])) {
                $typeCode = $typeMapping[$typeValue];
                $whereConditions[] = "(EXISTS(
                    SELECT 1 FROM agent_agent_type aat
                    JOIN agent_types at ON aat.agent_type_id = at.id
                    WHERE aat.agent_id = agents.id AND at.code = :type_code
                ) OR (agents.type & :type_value) > 0)";
                $params['type_code'] = $typeCode;
                $params['type_value'] = $typeValue;
            }
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM agents $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get agents with limit
        $sql = "
            SELECT agents.*,
                   GROUP_CONCAT(at.name, ', ') as agent_types
            FROM agents
            LEFT JOIN agent_agent_type aat ON agents.id = aat.agent_id
            LEFT JOIN agent_types at ON aat.agent_type_id = at.id
            $whereClause
            GROUP BY agents.id
            ORDER BY agents.title
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $agents = $this->db->fetchAll($sql, $params);

        // Process each agent to add type description
        foreach ($agents as &$agent) {
            // If no modern types, generate description from legacy bitwise type
            if (empty($agent['agent_types']) || $agent['agent_types'] === '') {
                $agent['type_description'] = $this->getLegacyTypeDescription((int) $agent['type']);
            } else {
                $agent['type_description'] = $agent['agent_types'];
            }
        }

        return [
            'data' => $agents,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create a new agent
     */
    public function create(array $data): int
    {
        $allowedFields = ['type', 'title', 'contactinfo', 'contacts', 'urls'];
        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('agents', $insertData);
    }

    /**
     * Update an agent
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['type', 'title', 'contactinfo', 'contacts', 'urls'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->db->update('agents', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete an agent
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('agents', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Get agent types for an agent
     */
    public function getAgentTypes(int $agentId): array
    {
        return $this->db->fetchAll(
            "SELECT at.* FROM agent_types at
             JOIN agent_agent_type aat ON at.id = aat.agent_type_id
             WHERE aat.agent_id = :agent_id
             ORDER BY at.sort_order, at.name",
            ['agent_id' => $agentId]
        );
    }

    /**
     * Set agent types for an agent
     */
    public function setAgentTypes(int $agentId, array $typeIds): void
    {
        $this->db->transaction(function($db) use ($agentId, $typeIds) {
            // Remove existing associations
            $db->execute(
                "DELETE FROM agent_agent_type WHERE agent_id = :agent_id",
                ['agent_id' => $agentId]
            );

            // Add new associations
            foreach ($typeIds as $typeId) {
                $db->execute(
                    "INSERT INTO agent_agent_type (agent_id, agent_type_id) VALUES (:agent_id, :type_id)",
                    ['agent_id' => $agentId, 'type_id' => $typeId]
                );
            }
        });
    }

    /**
     * Get agents by type
     */
    public function getByType(string $typeCode): array
    {
        $sql = "
            SELECT a.*
            FROM agents a
            INNER JOIN agent_agent_type aat ON a.id = aat.agent_id
            INNER JOIN agent_types at ON aat.agent_type_id = at.id
            WHERE at.code = :type_code
            ORDER BY a.title
        ";

        return $this->db->fetchAll($sql, [
            'type_code' => $typeCode
        ]);
    }

    /**
     * Get vendors (for dropdowns)
     */
    public function getVendors(): array
    {
        return $this->getByType('vendor');
    }

    /**
     * Get buyers (for dropdowns)
     */
    public function getBuyers(): array
    {
        return $this->getByType('buyer');
    }

    /**
     * Get contractors (for dropdowns)
     */
    public function getContractors(): array
    {
        return $this->getByType('contractor');
    }

    /**
     * Get hardware manufacturers (for dropdowns)
     */
    public function getHardwareManufacturers(): array
    {
        return $this->getByType('hardware_manufacturer');
    }

    /**
     * Get software manufacturers (for dropdowns)
     */
    public function getSoftwareManufacturers(): array
    {
        return $this->getByType('software_manufacturer');
    }

    /**
     * Check if agent can be deleted (not referenced by other records)
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check items
        $itemCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE manufacturerid = :id",
            ['id' => $id]
        );
        if ($itemCount > 0) {
            $references[] = "$itemCount item(s)";
        }

        // Check software
        $softwareCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM software WHERE manufacturerid = :id",
            ['id' => $id]
        );
        if ($softwareCount > 0) {
            $references[] = "$softwareCount software record(s)";
        }

        // Check invoices (vendor)
        $invoiceVendorCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices WHERE vendorid = :id",
            ['id' => $id]
        );
        if ($invoiceVendorCount > 0) {
            $references[] = "$invoiceVendorCount invoice(s) as vendor";
        }

        // Check invoices (buyer)
        $invoiceBuyerCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices WHERE buyerid = :id",
            ['id' => $id]
        );
        if ($invoiceBuyerCount > 0) {
            $references[] = "$invoiceBuyerCount invoice(s) as buyer";
        }

        // Check contracts (contractor)
        $contractContractorCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE contractorid = :id",
            ['id' => $id]
        );
        if ($contractContractorCount > 0) {
            $references[] = "$contractContractorCount contract(s) as contractor";
        }

        // Check contracts (vendor)
        $contractVendorCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE vendorid = :id",
            ['id' => $id]
        );
        if ($contractVendorCount > 0) {
            $references[] = "$contractVendorCount contract(s) as vendor";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    /**
     * Get agent with type description (for display)
     */
    public function findWithTypes(int $id): ?array
    {
        $agent = $this->db->fetchOne(
            "SELECT agents.*,
                    GROUP_CONCAT(at.name, ', ') as type_description
             FROM agents
             LEFT JOIN agent_agent_type aat ON agents.id = aat.agent_id
             LEFT JOIN agent_types at ON aat.agent_type_id = at.id
             WHERE agents.id = :id
             GROUP BY agents.id",
            ['id' => $id]
        );

        // If no modern types, generate description from legacy bitwise type
        if ($agent && (empty($agent['type_description']) || $agent['type_description'] === '')) {
            $agent['type_description'] = $this->getLegacyTypeDescription((int) $agent['type']);
        }

        return $agent;
    }

    /**
     * Generate type description from legacy bitwise value
     */
    private function getLegacyTypeDescription(int $type): string
    {
        $types = [];
        if ($type & 1) $types[] = 'Vendor';
        if ($type & 2) $types[] = 'SW Manufacturer';
        if ($type & 4) $types[] = 'HW Manufacturer';
        if ($type & 8) $types[] = 'Buyer';
        if ($type & 16) $types[] = 'Contractor';

        return implode(', ', $types);
    }

}