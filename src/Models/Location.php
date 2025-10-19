<?php

declare(strict_types=1);

namespace App\Models;


/**
 * Location entity model - represents a single location record with business logic.
 * Note: Currently unused. Controllers use LocationModel for data access.
 */
class Location extends BaseModel
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'floor',
        'floor_plan_filename'
    ];



    /**
     * Get floor plan path
     */
    public function getFloorPlanPath(): ?string
    {
        if (!$this->floor_plan_filename) {
            return null;
        }

        $floorPlanPath = $_ENV['FLOORPLAN_PATH'] ?? './storage/floorplans';
        return $floorPlanPath . '/' . $this->floor_plan_filename;
    }

    /**
     * Check if floor plan exists
     */
    public function hasFloorPlan(): bool
    {
        return $this->floor_plan_filename && file_exists($this->getFloorPlanPath());
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'floor' => 'string|max:50',
            'floor_plan_filename' => 'string|max:255'
        ];
    }
}