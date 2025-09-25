<?php

declare(strict_types=1);

namespace App\Models;


class Location extends BaseModel
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'floor',
        'floorplanfn'
    ];



    /**
     * Get floor plan path
     */
    public function getFloorPlanPath(): ?string
    {
        if (!$this->floorplanfn) {
            return null;
        }

        $floorPlanPath = $_ENV['FLOORPLAN_PATH'] ?? './storage/floorplans';
        return $floorPlanPath . '/' . $this->floorplanfn;
    }

    /**
     * Check if floor plan exists
     */
    public function hasFloorPlan(): bool
    {
        return $this->floorplanfn && file_exists($this->getFloorPlanPath());
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'floor' => 'string|max:50',
            'floorplanfn' => 'string|max:255'
        ];
    }
}