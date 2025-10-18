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
            $whereConditions[] = "(title LIKE :search OR contact_info LIKE :search)";
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
                $whereConditions[] = "EXISTS(
                    SELECT 1 FROM agent_agent_type aat
                    JOIN agent_types at ON aat.agent_type_id = at.id
                    WHERE aat.agent_id = agents.id AND at.code = :type_code
                )";
                $params['type_code'] = $typeCode;
            }
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM agents $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get agents with limit
        $sql = "
            SELECT agents.*,
                   GROUP_CONCAT(at.name, '|||') as agent_types,
                   GROUP_CONCAT(at.code, '|||') as agent_type_codes,
                   GROUP_CONCAT(at.badge_color, '|||') as agent_type_colors
            FROM agents
            LEFT JOIN agent_agent_type aat ON agents.id = aat.agent_id
            LEFT JOIN agent_types at ON aat.agent_type_id = at.id
            $whereClause
            GROUP BY agents.id
            ORDER BY agents.name
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $agents = $this->db->fetchAll($sql, $params);

        // Process each agent to add type description and array of types
        foreach ($agents as &$agent) {
            if ($agent['agent_types']) {
                $agent['type_description'] = str_replace('|||', ', ', $agent['agent_types']);
                // Create array of type info
                $names = explode('|||', $agent['agent_types']);
                $codes = explode('|||', $agent['agent_type_codes'] ?? '');
                $colors = explode('|||', $agent['agent_type_colors'] ?? '');
                $agent['type_list'] = [];
                foreach ($names as $idx => $name) {
                    $agent['type_list'][] = [
                        'name' => $name,
                        'code' => $codes[$idx] ?? '',
                        'color' => $colors[$idx] ?? 'secondary'
                    ];
                }
            } else {
                $agent['type_description'] = '';
                $agent['type_list'] = [];
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
        $allowedFields = ['title', 'contactinfo', 'contacts', 'urls'];
        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('agents', $insertData);
    }

    /**
     * Update an agent
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['title', 'contactinfo', 'contacts', 'urls'];
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
            ORDER BY a.name
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
            "SELECT COUNT(*) FROM items WHERE manufacturer_id = :id",
            ['id' => $id]
        );
        if ($itemCount > 0) {
            $references[] = "$itemCount item(s)";
        }

        // Check software
        $softwareCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM software WHERE manufacturer_id = :id",
            ['id' => $id]
        );
        if ($softwareCount > 0) {
            $references[] = "$softwareCount software record(s)";
        }

        // Check invoices (vendor)
        $invoiceVendorCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices WHERE vendor_id = :id",
            ['id' => $id]
        );
        if ($invoiceVendorCount > 0) {
            $references[] = "$invoiceVendorCount invoice(s) as vendor";
        }

        // Check invoices (buyer)
        $invoiceBuyerCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices WHERE buyer_id = :id",
            ['id' => $id]
        );
        if ($invoiceBuyerCount > 0) {
            $references[] = "$invoiceBuyerCount invoice(s) as buyer";
        }

        // Check contracts (contractor)
        $contractContractorCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE contractor_id = :id",
            ['id' => $id]
        );
        if ($contractContractorCount > 0) {
            $references[] = "$contractContractorCount contract(s) as contractor";
        }

        // Check contracts (vendor)
        $contractVendorCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE vendor_id = :id",
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
     * Get agent relationships (items, software, invoices, contracts)
     */
    public function getAgentRelationships(int $agentId): array
    {
        $relationships = [];

        // Items (as manufacturer)
        $relationships['items'] = $this->db->fetchAll(
            "SELECT id, label, function FROM items WHERE manufacturer_id = :agent_id ORDER BY id DESC LIMIT 20",
            ['agent_id' => $agentId]
        );

        // Software (as manufacturer)
        $relationships['software'] = $this->db->fetchAll(
            "SELECT id, title, version FROM software WHERE manufacturer_id = :agent_id ORDER BY id DESC LIMIT 20",
            ['agent_id' => $agentId]
        );

        // Invoices as vendor
        $relationships['invoices_vendor'] = $this->db->fetchAll(
            "SELECT id, invoice_date, total_cost FROM invoices WHERE vendor_id = :agent_id ORDER BY id DESC LIMIT 20",
            ['agent_id' => $agentId]
        );

        // Invoices as buyer
        $relationships['invoices_buyer'] = $this->db->fetchAll(
            "SELECT id, invoice_date, total_cost FROM invoices WHERE buyer_id = :agent_id ORDER BY id DESC LIMIT 20",
            ['agent_id' => $agentId]
        );

        // Contracts (as contractor)
        $relationships['contracts'] = $this->db->fetchAll(
            "SELECT id, title, start_date, end_date FROM contracts WHERE contractor_id = :agent_id ORDER BY id DESC LIMIT 20",
            ['agent_id' => $agentId]
        );

        return $relationships;
    }

}