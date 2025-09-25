<?php

declare(strict_types=1);

namespace App\Models;


class Agent extends BaseModel
{
    protected $table = 'agents';

    protected $fillable = [
        'type',
        'title',
        'contactinfo',
        'contacts',
        'urls',
    ];



    /**
     * Check if agent is vendor
     */
    public function isVendor(): bool
    {
        // Use bitwise system
        return ($this->type & 1) > 0;
    }

    /**
     * Check if agent is buyer
     */
    public function isBuyer(): bool
    {
        // Use bitwise system
        return ($this->type & 8) > 0;
    }

    /**
     * Check if agent is hardware manufacturer
     */
    public function isHardwareManufacturer(): bool
    {
        // Use bitwise system
        return ($this->type & 4) > 0;
    }

    /**
     * Check if agent is software manufacturer
     */
    public function isSoftwareManufacturer(): bool
    {
        // Use bitwise system
        return ($this->type & 2) > 0;
    }

    /**
     * Check if agent is contractor
     */
    public function isContractor(): bool
    {
        // Use bitwise system
        return ($this->type & 16) > 0;
    }

    /**
     * Get agent type description
     */
    public function getTypeDescription(): string
    {
        $types = [];
        if ($this->isVendor()) $types[] = 'Vendor';
        if ($this->isBuyer()) $types[] = 'Buyer';
        if ($this->isHardwareManufacturer()) $types[] = 'HW Manufacturer';
        if ($this->isSoftwareManufacturer()) $types[] = 'SW Manufacturer';
        if ($this->isContractor()) $types[] = 'Contractor';

        return implode(', ', $types);
    }

}