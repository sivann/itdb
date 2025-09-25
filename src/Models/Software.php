<?php

declare(strict_types=1);

namespace App\Models;


class Software extends BaseModel
{
    protected $table = 'software';

    protected $fillable = [
        'stitle',           // title field
        'sversion',         // version field
        'sinfo',            // description/info field
        'slicenseinfo',     // license info field
        'licqty',           // license quantity field
        'lictype',          // license type field
        'stype',            // software type field
        'manufacturerid',   // manufacturer id field
        'invoiceid',        // invoice id field
        'purchdate',        // purchase date field
    ];



    /**
     * Get display title
     */
    public function getDisplayTitle(): string
    {
        $parts = array_filter([$this->stitle, $this->sversion]);
        return implode(' v', $parts) ?: 'Untitled Software';
    }

    /**
     * Get license status (simplified - no expiration tracking in this table)
     */
    public function getLicenseStatus(): array
    {
        return [
            'status' => 'active',
            'days' => null,
            'expired' => false
        ];
    }


    /**
     * Check if software has available licenses
     */
    public function hasAvailableLicenses(): bool
    {
        return ($this->licqty ?? 0) > 0;
    }

    /**
     * Check if license is expired (simplified - always false since no expiration tracking)
     */
    public function isLicenseExpired(): bool
    {
        return false;
    }

    /**
     * Get formatted purchase date
     */
    public function getPurchaseDateFormatted(): ?string
    {
        return $this->formatDate($this->purchdate);
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'stitle' => 'required|string|max:255',
            'sversion' => 'string|max:50',
            'sinfo' => 'string',
            'slicenseinfo' => 'string|max:255',
            'licqty' => 'integer|min:1',
            'lictype' => 'integer',
            'stype' => 'string|max:100',
            'manufacturerid' => 'integer',
        ];
    }

}