<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Item entity model - represents a single item record with business logic.
 * Note: Currently unused. Controllers use ItemModel for data access.
 */
class Item extends BaseModel
{
    protected $table = 'items';

    protected $fillable = [
        'function', // title equivalent
        'item_type_id', // type
        'status',
        'manufacturer_id', // manufacturer
        'model',
        'sn', // serial
        'label', // assettag equivalent
        'comments', // description
        'maintenance_info', // notes equivalent
        'user_id',
        'location_id',
        'location_area_id',
        'rack_id',
        'rack_position', // rackrow equivalent
        'purchase_date',
        'warranty_months',
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
        return $this->formatDate($this->purchase_date);
    }

    /**
     * Get warranty expiration date
     */
    public function getWarrantyExpiration(): ?int
    {
        if (!$this->purchase_date || !$this->warranty_months) {
            return null;
        }

        // Add warranty months to purchase date
        return strtotime("+{$this->warranty_months} months", $this->purchase_date);
    }

    /**
     * Get warranty status
     */
    public function getWarrantyStatus(): array
    {
        if (!$this->purchase_date || !$this->warranty_months) {
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
        if (!$this->rack_id) {
            return null;
        }

        if ($this->rack_position) {
            return "Row {$this->rack_position}";
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
            'user_id' => 'integer',
            'location_id' => 'integer',
            'warranty' => 'integer|min:0',
            'ip' => 'string|max:50',
            'os' => 'string|max:100',
        ];
    }

}