<?php

declare(strict_types=1);

namespace App\Models;


class LocationArea extends BaseModel
{
    protected $table = 'locareas';

    protected $fillable = [
        'location_id',
        'areaname',
        'description'
    ];

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'location_id' => 'required|integer|exists:locations,id',
            'areaname' => 'required|string|max:100',
            'description' => 'string'
        ];
    }
}