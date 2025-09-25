<?php

declare(strict_types=1);

namespace App\Models;


class Invoice extends BaseModel
{
    protected $table = 'invoices';

    protected $fillable = [
        'title',
        'description',
        'invoicedate',
        'total',
        'vendorid',
        'notes'
    ];



    /**
     * Get formatted invoice date
     */
    public function getInvoiceDateFormatted(): ?string
    {
        return $this->formatDate($this->invoicedate);
    }

    /**
     * Get formatted total amount
     */
    public function getTotalFormatted(): string
    {
        return number_format($this->total, 2);
    }
}