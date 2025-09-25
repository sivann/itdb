<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class InvoiceModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find an invoice by ID
     */
    public function find(int $id): ?array
    {
        $invoice = $this->db->fetchOne(
            "SELECT i.*,
                    vendor.title as vendor_name,
                    buyer.title as buyer_name
             FROM invoices i
             LEFT JOIN agents vendor ON i.vendorid = vendor.id
             LEFT JOIN agents buyer ON i.buyerid = buyer.id
             WHERE i.id = :id",
            ['id' => $id]
        );

        if ($invoice) {
            return $this->transformInvoiceForTemplate($invoice);
        }

        return null;
    }

    /**
     * Get paginated invoices with filtering
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $whereConditions[] = "(i.comments LIKE :search OR i.id LIKE :search OR vendor.title LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['vendor'])) {
            $whereConditions[] = "i.vendorid = :vendor";
            $params['vendor'] = (int) $filters['vendor'];
        }

        if (!empty($filters['buyer'])) {
            $whereConditions[] = "i.buyerid = :buyer";
            $params['buyer'] = (int) $filters['buyer'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "i.date >= :date_from";
            $params['date_from'] = strtotime($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "i.date <= :date_to";
            $params['date_to'] = strtotime($filters['date_to']);
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM invoices i $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get invoices with limit
        $sql = "
            SELECT i.*,
                   vendor.title as vendor_name,
                   buyer.title as buyer_name
            FROM invoices i
            LEFT JOIN agents vendor ON i.vendorid = vendor.id
            LEFT JOIN agents buyer ON i.buyerid = buyer.id
            $whereClause
            ORDER BY i.date DESC, i.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $invoices = $this->db->fetchAll($sql, $params);

        // Transform invoices for template
        $transformedInvoices = array_map([$this, 'transformInvoiceForTemplate'], $invoices);

        return [
            'data' => $transformedInvoices,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create a new invoice
     */
    public function create(array $data): int
    {
        $allowedFields = [
            'date', 'vendorid', 'buyerid', 'comments', 'totalcost'
        ];

        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('invoices', $insertData);
    }

    /**
     * Update an invoice
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'date', 'vendorid', 'buyerid', 'comments', 'totalcost'
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->db->update('invoices', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete an invoice
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('invoices', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Get all invoices for dropdown (simple list)
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM invoices ORDER BY date DESC");
    }

    /**
     * Check if invoice can be deleted (not referenced by other records)
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check items that reference this invoice
        $itemCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE invoiceid = :id",
            ['id' => $id]
        );
        if ($itemCount > 0) {
            $references[] = "$itemCount item(s)";
        }

        // Check software that reference this invoice
        $softwareCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM software WHERE invoiceid = :id",
            ['id' => $id]
        );
        if ($softwareCount > 0) {
            $references[] = "$softwareCount software record(s)";
        }

        // Check contracts that reference this invoice (if applicable)
        try {
            $contractCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM contract2inv WHERE invoiceid = :id",
                ['id' => $id]
            );
            if ($contractCount > 0) {
                $references[] = "$contractCount contract association(s)";
            }
        } catch (\Exception $e) {
            // contract2inv table might not exist
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    /**
     * Get invoices by vendor
     */
    public function getByVendor(int $vendorId): array
    {
        $sql = "
            SELECT i.*,
                   vendor.title as vendor_name,
                   buyer.title as buyer_name
            FROM invoices i
            LEFT JOIN agents vendor ON i.vendorid = vendor.id
            LEFT JOIN agents buyer ON i.buyerid = buyer.id
            WHERE i.vendorid = :vendor_id
            ORDER BY i.date DESC
        ";

        $invoices = $this->db->fetchAll($sql, ['vendor_id' => $vendorId]);
        return array_map([$this, 'transformInvoiceForTemplate'], $invoices);
    }

    /**
     * Get invoice statistics
     */
    public function getStats(): array
    {
        return [
            'total_invoices' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM invoices"),
            'total_amount' => (float) ($this->db->fetchColumn("SELECT SUM(totalcost) FROM invoices") ?? 0),
            'this_year' => (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM invoices WHERE date >= :year_start",
                ['year_start' => mktime(0, 0, 0, 1, 1, date('Y'))]
            ),
        ];
    }

    /**
     * Transform invoice data for template compatibility
     */
    public function transformInvoiceForTemplate(array $invoice): array
    {
        // Add computed fields that templates expect
        $invoice['vendor'] = $invoice['vendor_name'] ?
            (object)['title' => $invoice['vendor_name']] : null;

        $invoice['buyer'] = $invoice['buyer_name'] ?
            (object)['title' => $invoice['buyer_name']] : null;

        // Format dates for display
        if ($invoice['date']) {
            $invoice['date_formatted'] = date('Y-m-d', $invoice['date']);
        }

        // Format cost
        if ($invoice['totalcost']) {
            $invoice['total_formatted'] = number_format($invoice['totalcost'], 2);
        }

        return $invoice;
    }

    /**
     * Get total count of invoices
     */
    public function getCount(): int
    {
        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM invoices");
    }
}