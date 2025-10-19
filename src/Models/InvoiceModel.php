<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

/**
 * Invoice data access model - handles all database operations for invoices (CRUD, queries, associations).
 */
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
                    vendor.name as vendor_name,
                    buyer.name as buyer_name
             FROM invoices i
             LEFT JOIN agents vendor ON i.vendor_id = vendor.id
             LEFT JOIN agents buyer ON i.buyer_id = buyer.id
             WHERE i.id = :id",
            ['id' => $id]
        );

        if ($invoice) {
            return $this->transformInvoiceForTemplate($invoice);
        }

        return null;
    }

    /**
     * Find invoice with all associations loaded
     */
    public function findWithAssociations(int $id): ?array
    {
        $invoice = $this->find($id);
        if (!$invoice) {
            return null;
        }

        // Get association counts
        $invoice['items_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM items_invoices WHERE invoice_id = ?", [$id]
        );

        $invoice['software_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM software_invoices WHERE invoice_id = ?", [$id]
        );

        $invoice['contracts_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM contracts_invoices WHERE invoice_id = ?", [$id]
        );

        $invoice['files_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices_files WHERE invoice_id = ?", [$id]
        );

        // Get full association data
        $invoice['items'] = $this->getAssociatedItems($id);
        $invoice['software'] = $this->getAssociatedSoftware($id);
        $invoice['contracts'] = $this->getAssociatedContracts($id);
        $invoice['files'] = $this->getAssociatedFiles($id);

        return $invoice;
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
            $whereConditions[] = "(i.comments LIKE :search OR i.id LIKE :search OR vendor.name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['vendor'])) {
            $whereConditions[] = "i.vendor_id = :vendor";
            $params['vendor'] = (int) $filters['vendor'];
        }

        if (!empty($filters['buyer'])) {
            $whereConditions[] = "i.buyer_id = :buyer";
            $params['buyer'] = (int) $filters['buyer'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "i.invoice_date >= :date_from";
            $params['date_from'] = strtotime($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "i.invoice_date <= :date_to";
            $params['date_to'] = strtotime($filters['date_to']);
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM invoices i $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get invoices with limit
        $sql = "
            SELECT i.*,
                   vendor.name as vendor_name,
                   buyer.name as buyer_name
            FROM invoices i
            LEFT JOIN agents vendor ON i.vendor_id = vendor.id
            LEFT JOIN agents buyer ON i.buyer_id = buyer.id
            $whereClause
            ORDER BY i.invoice_date DESC, i.id DESC
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
            'invoice_date', 'vendor_id', 'buyer_id', 'comments', 'total_cost', 'invoice_number', 'title'
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
            'invoice_date', 'vendor_id', 'buyer_id', 'comments', 'total_cost', 'invoice_number', 'title'
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
        return $this->db->fetchAll("SELECT * FROM invoices ORDER BY invoice_date DESC");
    }

    /**
     * Check if invoice can be deleted (not referenced by other records)
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check items that reference this invoice
        $itemCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE invoice_id = :id",
            ['id' => $id]
        );
        if ($itemCount > 0) {
            $references[] = "$itemCount item(s)";
        }

        // Check software that reference this invoice
        $softwareCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM software WHERE invoice_id = :id",
            ['id' => $id]
        );
        if ($softwareCount > 0) {
            $references[] = "$softwareCount software record(s)";
        }

        // Check contracts that reference this invoice (if applicable)
        try {
            $contractCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM contracts_invoices WHERE invoice_id = :id",
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
                   vendor.name as vendor_name,
                   buyer.name as buyer_name
            FROM invoices i
            LEFT JOIN agents vendor ON i.vendor_id = vendor.id
            LEFT JOIN agents buyer ON i.buyer_id = buyer.id
            WHERE i.vendor_id = :vendor_id
            ORDER BY i.invoice_date DESC
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
            'total_amount' => (float) ($this->db->fetchColumn("SELECT SUM(total_cost) FROM invoices") ?? 0),
            'this_year' => (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM invoices WHERE invoice_date >= :year_start",
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
        if ($invoice['invoice_date']) {
            $invoice['date_formatted'] = date('Y-m-d', $invoice['invoice_date']);
        }

        // Format cost
        if ($invoice['total_cost']) {
            $invoice['total_formatted'] = number_format($invoice['total_cost'], 2);
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

    /**
     * Get items associated with an invoice
     */
    public function getAssociatedItems(int $invoiceId): array
    {
        $sql = "
            SELECT i.*,
                   it.name as type_name,
                   l.name as location_name,
                   u.username
            FROM items i
            INNER JOIN items_invoices ii ON i.id = ii.item_id
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN users u ON i.user_id = u.id
            WHERE ii.invoice_id = :invoice_id
            ORDER BY i.id DESC
        ";

        return $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);
    }

    /**
     * Get software associated with an invoice
     */
    public function getAssociatedSoftware(int $invoiceId): array
    {
        $sql = "
            SELECT s.*,
                   a.name as manufacturer_name,
                   s.license_type_id as license_type_name
            FROM software s
            INNER JOIN software_invoices si ON s.id = si.software_id
            LEFT JOIN agents a ON s.manufacturer_id = a.id
            WHERE si.invoice_id = :invoice_id
            ORDER BY s.id DESC
        ";

        return $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);
    }

    /**
     * Get contracts associated with an invoice
     */
    public function getAssociatedContracts(int $invoiceId): array
    {
        $sql = "
            SELECT c.*,
                   a.name as contractor_name
            FROM contracts c
            INNER JOIN contracts_invoices ci ON c.id = ci.contract_id
            LEFT JOIN agents a ON c.contractor_id = a.id
            WHERE ci.invoice_id = :invoice_id
            ORDER BY c.id DESC
        ";

        return $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);
    }

    /**
     * Get files associated with an invoice
     */
    public function getAssociatedFiles(int $invoiceId): array
    {
        $sql = "
            SELECT f.*,
                   ft.name as filetype_name
            FROM files f
            INNER JOIN invoices_files i2f ON f.id = i2f.file_id
            LEFT JOIN file_types ft ON f.file_type_id = ft.id
            WHERE i2f.invoice_id = :invoice_id
            ORDER BY f.uploaded_at DESC
        ";

        $files = $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);

        // Format dates for display
        foreach ($files as &$file) {
            if (!empty($file['uploaded_at'])) {
                // If uploaded_at is already formatted string, use it directly
                // Otherwise convert from timestamp
                if (is_numeric($file['uploaded_at'])) {
                    $file['uploaddate_formatted'] = date('Y-m-d H:i', (int)$file['uploaded_at']);
                } else {
                    $file['uploaddate_formatted'] = $file['uploaded_at'];
                }
            }
        }

        return $files;
    }

    /**
     * Add item association
     */
    public function addItemAssociation(int $invoiceId, int $itemId): void
    {
        try {
            $this->db->insert('items_invoices', ['invoice_id' => $invoiceId, 'item_id' => $itemId]);
        } catch (\Exception $e) {
            // If already exists (duplicate key), just ignore
            if (strpos($e->getMessage(), 'UNIQUE constraint') === false &&
                strpos($e->getMessage(), 'PRIMARY KEY') === false) {
                throw $e;
            }
        }
    }

    /**
     * Remove item association
     */
    public function removeItemAssociation(int $invoiceId, int $itemId): void
    {
        $this->db->delete('items_invoices', ['invoice_id' => $invoiceId, 'item_id' => $itemId]);
    }

    /**
     * Add software association
     */
    public function addSoftwareAssociation(int $invoiceId, int $softwareId): void
    {
        try {
            $this->db->insert('software_invoices', ['invoice_id' => $invoiceId, 'software_id' => $softwareId]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint') === false &&
                strpos($e->getMessage(), 'PRIMARY KEY') === false) {
                throw $e;
            }
        }
    }

    /**
     * Remove software association
     */
    public function removeSoftwareAssociation(int $invoiceId, int $softwareId): void
    {
        $this->db->delete('software_invoices', ['invoice_id' => $invoiceId, 'software_id' => $softwareId]);
    }

    /**
     * Add contract association
     */
    public function addContractAssociation(int $invoiceId, int $contractId): void
    {
        try {
            $this->db->insert('contracts_invoices', ['invoice_id' => $invoiceId, 'contract_id' => $contractId]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint') === false &&
                strpos($e->getMessage(), 'PRIMARY KEY') === false) {
                throw $e;
            }
        }
    }

    /**
     * Remove contract association
     */
    public function removeContractAssociation(int $invoiceId, int $contractId): void
    {
        $this->db->delete('contracts_invoices', ['invoice_id' => $invoiceId, 'contract_id' => $contractId]);
    }

    /**
     * Add file association
     */
    public function addFileAssociation(int $invoiceId, int $fileId): void
    {
        try {
            $this->db->insert('invoices_files', ['invoice_id' => $invoiceId, 'file_id' => $fileId]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint') === false &&
                strpos($e->getMessage(), 'PRIMARY KEY') === false) {
                throw $e;
            }
        }
    }

    /**
     * Remove file association
     */
    public function removeFileAssociation(int $invoiceId, int $fileId): void
    {
        $this->db->delete('invoices_files', ['invoice_id' => $invoiceId, 'file_id' => $fileId]);
    }
}