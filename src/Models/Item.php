<?php

declare(strict_types=1);

namespace App\Models;


class Item extends BaseModel
{
    protected $table = 'items';

    protected $fillable = [
        'function', // title equivalent
        'itemtypeid', // type
        'status',
        'manufacturerid', // manufacturer
        'model',
        'sn', // serial
        'label', // assettag equivalent
        'comments', // description
        'maintenanceinfo', // notes equivalent
        'userid',
        'locationid',
        'locareaid',
        'rackid',
        'rackposition', // rackrow equivalent
        'purchasedate',
        'warrantymonths',
        'ipv4', // ip
        'hd', // hdd
        'cpu',
        'ram', // memory
        'comments'
    ];



    /**
     * Get formatted purchase date
     */
    public function getPurchaseDateFormatted(): ?string
    {
        return $this->formatDate($this->purchasedate);
    }

    /**
     * Get warranty expiration date
     */
    public function getWarrantyExpiration(): ?int
    {
        if (!$this->purchasedate || !$this->warrantymonths) {
            return null;
        }

        // Add warranty months to purchase date
        return strtotime("+{$this->warrantymonths} months", $this->purchasedate);
    }

    /**
     * Get warranty status
     */
    public function getWarrantyStatus(): array
    {
        if (!$this->purchasedate || !$this->warrantymonths) {
            return ['status' => 'unknown', 'days' => null, 'expired' => false];
        }

        $expiration = $this->getWarrantyExpiration();
        $now = time();
        $daysRemaining = floor(($expiration - $now) / 86400);

        return [
            'status' => $daysRemaining > 0 ? 'active' : 'expired',
            'days' => $daysRemaining,
            'expired' => $daysRemaining <= 0,
            'expiration_date' => date('Y-m-d', $expiration)
        ];
    }

    /**
     * Get display title (function or model + sn)
     */
    public function getDisplayTitle(): string
    {
        if ($this->function) {
            return $this->function;
        }

        $parts = array_filter([$this->model, $this->sn]);
        return implode(' - ', $parts) ?: 'Untitled Item';
    }

    /**
     * Check if item is active
     */
    public function isActive(): bool
    {
        return $this->status !== 0;
    }

    /**
     * Get rack position string
     */
    public function getRackPosition(): ?string
    {
        if (!$this->rackid) {
            return null;
        }

        if ($this->rackposition) {
            return "Row {$this->rackposition}";
        }

        return null;
    }

    /**
     * Validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'title' => 'string|max:255',
            'type' => 'required|integer',
            'status' => 'required|integer',
            'manufacturer' => 'string|max:100',
            'model' => 'string|max:100',
            'serial' => 'string|max:100',
            'assettag' => 'string|max:50',
            'description' => 'string',
            'userid' => 'integer',
            'locationid' => 'integer',
            'warranty' => 'integer|min:0',
            'ip' => 'string|max:50',
            'os' => 'string|max:100',
        ];
    }

}