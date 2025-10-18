<?php

declare(strict_types=1);

namespace App\Models;


class Invoice extends BaseModel
{
    protected $table = 'invoices';

    protected $fillable = [
        'title',
        'description',
        'invoice_date',
        'total_cost',
        'vendor_id',
        'notes'
    ];



    /**
     * Get formatted invoice date
     */
    public function getInvoiceDateFormatted(): ?string
    {
        return $this->formatDate($this->invoice_date);
    }

    /**
     * Get formatted total amount
     */
    public function getTotalFormatted(): string
    {
        return number_format($this->total_cost, 2);
    }
}