<?php

declare(strict_types=1);

namespace App\Models;


class Rack extends BaseModel
{
    protected $table = 'racks';

    protected $fillable = [
        'location_id',
        'size_units',
        'depth_mm',
        'comments',
        'model',
        'label',
        'reverse_numbering',
        'location_area_id'
    ];



    /**
     * Get display name for rack
     */
    public function getDisplayName(): string
    {
        if ($this->label) {
            return $this->label;
        }

        if ($this->model) {
            return $this->model;
        }

        return "Rack #{$this->id}";
    }

    /**
     * Get rack capacity info
     */
    public function getCapacity(): array
    {
        $totalUnits = $this->size_units ?: 42; // Default to 42U if not specified
        // Note: Would need DatabaseManager to calculate actual usage
        $usedUnits = 0; // Placeholder - implement with DatabaseManager

        return [
            'total' => $totalUnits,
            'used' => $usedUnits,
            'available' => $totalUnits - $usedUnits,
            'utilization_percent' => $totalUnits > 0 ? round(($usedUnits / $totalUnits) * 100, 1) : 0
        ];
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'location_id' => 'required|integer|exists:locations,id',
            'size_units' => 'integer|min:1|max:100',
            'depth_mm' => 'integer|min:1',
            'comments' => 'string',
            'model' => 'string|max:100',
            'label' => 'string|max:100',
            'reverse_numbering' => 'integer',
            'location_area_id' => 'numeric'
        ];
    }
}