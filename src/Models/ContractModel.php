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
                    contractor.title as contractor_name,
                    vendor.title as vendor_name,
                    parent.title as parent_title
             FROM contracts c
             LEFT JOIN contracttypes ct ON c.type = ct.id
             LEFT JOIN agents contractor ON c.contractorid = contractor.id
             LEFT JOIN agents vendor ON c.vendorid = vendor.id
             LEFT JOIN contracts parent ON c.parentid = parent.id
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
            $whereConditions[] = "(c.title LIKE :search OR c.number LIKE :search OR c.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['type'])) {
            $whereConditions[] = "c.type = :type";
            $params['type'] = (int) $filters['type'];
        }

        if (!empty($filters['contractor'])) {
            $whereConditions[] = "c.contractorid = :contractor";
            $params['contractor'] = (int) $filters['contractor'];
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $whereConditions[] = "(c.currentenddate IS NULL OR c.currentenddate > :current_time)";
                $params['current_time'] = time();
            } elseif ($filters['status'] === 'expired') {
                $whereConditions[] = "c.currentenddate <= :current_time";
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
                   contractor.title as contractor_name,
                   vendor.title as vendor_name,
                   parent.title as parent_title
            FROM contracts c
            LEFT JOIN contracttypes ct ON c.type = ct.id
            LEFT JOIN agents contractor ON c.contractorid = contractor.id
            LEFT JOIN agents vendor ON c.vendorid = vendor.id
            LEFT JOIN contracts parent ON c.parentid = parent.id
            $whereClause
            ORDER BY c.startdate DESC
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

    /**
     * Create a new contract
     */
    public function create(array $data): int
    {
        $allowedFields = [
            'type', 'parentid', 'title', 'number', 'description', 'comments',
            'totalcost', 'contractorid', 'vendorid', 'startdate', 'currentenddate',
            'renewals', 'subtype'
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
            'type', 'parentid', 'title', 'number', 'description', 'comments',
            'totalcost', 'contractorid', 'vendorid', 'startdate', 'currentenddate',
            'renewals', 'subtype'
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
        $sql = "SELECT id, title FROM contracts WHERE parentid IS NULL";
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
        $sql = "SELECT DISTINCT contractorid FROM contracts WHERE contractorid IS NOT NULL";

        $result = $this->db->fetchAll($sql);
        return array_column($result, 'contractorid');
    }

    /**
     * Check if contract has children (sub-contracts)
     */
    public function hasChildren(int $id): bool
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts WHERE parentid = :id",
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
            "SELECT COUNT(*) FROM contracts WHERE parentid = :id",
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
                   contractor.title as contractor_name
            FROM contracts c
            LEFT JOIN agents contractor ON c.contractorid = contractor.id
            WHERE c.title LIKE :query
               OR c.number LIKE :query
               OR c.description LIKE :query
        ";

        $params = ['query' => '%' . $query . '%'];

        // Add ID search if query is numeric
        if (is_numeric($query)) {
            $sql .= " OR c.id = :id";
            $params['id'] = (int) $query;
        }

        $sql .= " ORDER BY c.startdate DESC LIMIT :limit";
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
        if ($contract['startdate']) {
            $contract['start_date_formatted'] = date('Y-m-d', $contract['startdate']);
        }

        if ($contract['currentenddate']) {
            $contract['end_date_formatted'] = date('Y-m-d', $contract['currentenddate']);
            $contract['is_active'] = $contract['currentenddate'] > time();
        } else {
            $contract['is_active'] = true;
        }

        // Calculate status for templates
        $currentTime = time();

        if ($contract['currentenddate']) {
            $daysUntilEnd = ceil(($contract['currentenddate'] - $currentTime) / 86400);

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