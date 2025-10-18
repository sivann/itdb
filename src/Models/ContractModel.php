<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class ContractModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find a contract by ID
     */
    public function find(int $id): ?array
    {
        $contract = $this->db->fetchOne(
            "SELECT c.*,
                    ct.name as contract_type_name,
                    contractor.name as contractor_name,
                    vendor.name as vendor_name,
                    parent.title as parent_title
             FROM contracts c
             LEFT JOIN contract_types ct ON c.contract_type_id = ct.id
             LEFT JOIN agents contractor ON c.contractor_id = contractor.id
             LEFT JOIN agents vendor ON c.vendor_id = vendor.id
             LEFT JOIN contracts parent ON c.parent_contract_id = parent.id
             WHERE c.id = :id",
            ['id' => $id]
        );

        if ($contract) {
            return $this->transformContractForTemplate($contract);
        }

        return null;
    }

    /**
     * Get paginated contracts with filtering
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $whereConditions[] = "(c.title LIKE :search OR c.contract_number LIKE :search OR c.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['type'])) {
            $whereConditions[] = "c.contract_type_id = :type";
            $params['type'] = (int) $filters['type'];
        }

        if (!empty($filters['contractor'])) {
            $whereConditions[] = "c.contractor_id = :contractor";
            $params['contractor'] = (int) $filters['contractor'];
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $whereConditions[] = "(c.end_date IS NULL OR c.end_date > :current_time)";
                $params['current_time'] = time();
            } elseif ($filters['status'] === 'expired') {
                $whereConditions[] = "c.end_date <= :current_time";
                $params['current_time'] = time();
            }
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM contracts c $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get contracts with limit
        $sql = "
            SELECT c.*,
                   ct.name as contract_type_name,
                   contractor.name as contractor_name,
                   vendor.name as vendor_name,
                   parent.title as parent_title
            FROM contracts c
            LEFT JOIN contract_types ct ON c.contract_type_id = ct.id
            LEFT JOIN agents contractor ON c.contractor_id = contractor.id
            LEFT JOIN agents vendor ON c.vendor_id = vendor.id
            LEFT JOIN contracts parent ON c.parent_contract_id = parent.id
            $whereClause
            ORDER BY c.start_date DESC
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $contracts = $this->db->fetchAll($sql, $params);

        // Transform contracts for template
        $transformedContracts = array_map([$this, 'transformContractForTemplate'], $contracts);

        return [
            'data' => $transformedContracts,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

        $allowedFields = [
            'contract_type_id', 'parent_contract_id', 'title', 'contract_number', 'description', 'comments',
            'total_cost', 'contractor_id', 'vendor_id', 'start_date', 'end_date',
            'renewals', 'contract_subtype_id'
        ];

        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('contracts', $insertData);
    }

    /**
     * Update a contract
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'contract_type_id', 'parent_contract_id', 'title', 'contract_number', 'description', 'comments',
            'total_cost', 'contractor_id', 'vendor_id', 'start_date', 'end_date',
            'renewals', 'contract_subtype_id'
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->db->update('contracts', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete a contract
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('contracts', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Get contracts for parent dropdown (only top-level contracts)
     */
    public function getParentContracts(int $excludeId = null): array
    {
        $sql = "SELECT id, title FROM contracts WHERE parent_contract_id IS NULL";
        $params = [];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " ORDER BY title";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get distinct contractor IDs that have contracts
     */
    public function getContractorIds(): array
    {
        $sql = "SELECT DISTINCT contractor_id FROM contracts WHERE contractor_id IS NOT NULL";

        $result = $this->db->fetchAll($sql);
        return array_column($result, 'contractor_id');
    }

    /**
     * Check if contract has children (sub-contracts)
     */
    public function hasChildren(int $id): bool
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE parent_contract_id = :id",
            ['id' => $id]
        );

        return (int) $count > 0;
    }

    /**
     * Check if contract can be deleted (no children, not referenced by other records)
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check for child contracts
        $childCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE parent_contract_id = :id",
            ['id' => $id]
        );
        if ($childCount > 0) {
            $references[] = "$childCount sub-contract(s)";
        }

        // Check for other potential references
        // Add more checks here as needed for other tables that might reference contracts

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    /**
     * Search contracts for API
     */
    public function search(string $query, int $limit = 20): array
    {
        $sql = "
            SELECT c.*,
                   contractor.name as contractor_name
            FROM contracts c
            LEFT JOIN agents contractor ON c.contractor_id = contractor.id
            WHERE c.title LIKE :query
               OR c.contract_number LIKE :query
               OR c.description LIKE :query
        ";

        $params = ['query' => '%' . $query . '%'];

        // Add ID search if query is numeric
        if (is_numeric($query)) {
            $sql .= " OR c.id = :id";
            $params['id'] = (int) $query;
        }

        $sql .= " ORDER BY c.start_date DESC LIMIT :limit";
        $params['limit'] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Transform contract data for template compatibility
     */
    private function transformContractForTemplate(array $contract): array
    {
        // Add computed fields that templates expect
        $contract['contractor'] = $contract['contractor_name'] ?
            (object)['title' => $contract['contractor_name']] : null;

        $contract['vendor'] = $contract['vendor_name'] ?
            (object)['title' => $contract['vendor_name']] : null;

        $contract['parent'] = $contract['parent_title'] ?
            (object)['title' => $contract['parent_title']] : null;

        // Add type_name for template compatibility
        $contract['type_name'] = $contract['contract_type_name'] ?? null;

        // Format dates for display
        if ($contract['start_date']) {
            $contract['start_date_formatted'] = date('Y-m-d', $contract['start_date']);
        }

        if ($contract['end_date']) {
            $contract['end_date_formatted'] = date('Y-m-d', $contract['end_date']);
            $contract['is_active'] = $contract['end_date'] > time();
        } else {
            $contract['is_active'] = true;
        }

        // Calculate status for templates
        $currentTime = time();

        if ($contract['end_date']) {
            $daysUntilEnd = ceil(($contract['end_date'] - $currentTime) / 86400);

            if ($daysUntilEnd > 0) {
                $status = 'active';
            } else {
                $status = 'expired';
            }

            $contract['status'] = [
                'status' => $status,
                'days' => $daysUntilEnd
            ];
        } else {
            // No end date means active indefinitely
            $contract['status'] = [
                'status' => 'active',
                'days' => null
            ];
        }

        return $contract;
    }

    /**
     * Get total count of contracts
     */
    public function getCount(): int
    {
        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM contracts");
    }
}