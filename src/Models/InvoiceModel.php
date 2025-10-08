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
            "SELECT COUNT(*) FROM item2inv WHERE invid = ?", [$id]
        );

        $invoice['software_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM soft2inv WHERE invid = ?", [$id]
        );

        $invoice['contracts_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM contract2inv WHERE invid = ?", [$id]
        );

        $invoice['files_count'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoice2file WHERE invoiceid = ?", [$id]
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
            INNER JOIN item2inv ii ON i.id = ii.itemid
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN locations l ON i.locationid = l.id
            LEFT JOIN users u ON i.userid = u.id
            WHERE ii.invid = :invoice_id
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
                   a.title as manufacturer_name,
                   s.slicensetype as license_type_name
            FROM software s
            INNER JOIN soft2inv si ON s.id = si.softid
            LEFT JOIN agents a ON s.manufacturerid = a.id
            WHERE si.invid = :invoice_id
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
                   a.title as contractor_name
            FROM contracts c
            INNER JOIN contract2inv ci ON c.id = ci.contractid
            LEFT JOIN agents a ON c.contractorid = a.id
            WHERE ci.invid = :invoice_id
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
                   ft.typedesc as filetype_name
            FROM files f
            INNER JOIN invoice2file i2f ON f.id = i2f.fileid
            LEFT JOIN filetypes ft ON f.type = ft.id
            WHERE i2f.invoiceid = :invoice_id
            ORDER BY f.uploaddate DESC
        ";

        $files = $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);

        // Format dates for display
        foreach ($files as &$file) {
            if (!empty($file['uploaddate'])) {
                // If uploaddate is already formatted string, use it directly
                // Otherwise convert from timestamp
                if (is_numeric($file['uploaddate'])) {
                    $file['uploaddate_formatted'] = date('Y-m-d H:i', (int)$file['uploaddate']);
                } else {
                    $file['uploaddate_formatted'] = $file['uploaddate'];
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
            $this->db->insert('item2inv', ['invid' => $invoiceId, 'itemid' => $itemId]);
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
        $this->db->delete('item2inv', ['invid' => $invoiceId, 'itemid' => $itemId]);
    }

    /**
     * Add software association
     */
    public function addSoftwareAssociation(int $invoiceId, int $softwareId): void
    {
        try {
            $this->db->insert('soft2inv', ['invid' => $invoiceId, 'softid' => $softwareId]);
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
        $this->db->delete('soft2inv', ['invid' => $invoiceId, 'softid' => $softwareId]);
    }

    /**
     * Add contract association
     */
    public function addContractAssociation(int $invoiceId, int $contractId): void
    {
        try {
            $this->db->insert('contract2inv', ['invid' => $invoiceId, 'contractid' => $contractId]);
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
        $this->db->delete('contract2inv', ['invid' => $invoiceId, 'contractid' => $contractId]);
    }

    /**
     * Add file association
     */
    public function addFileAssociation(int $invoiceId, int $fileId): void
    {
        try {
            $this->db->insert('invoice2file', ['invoiceid' => $invoiceId, 'fileid' => $fileId]);
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
        $this->db->delete('invoice2file', ['invoiceid' => $invoiceId, 'fileid' => $fileId]);
    }
}