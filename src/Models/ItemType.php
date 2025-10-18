<?php

declare(strict_types=1);

namespace App\Models;


class ItemType extends BaseModel
{
    protected $table = 'itemtypes';

    protected $fillable = [
        'typedesc',
        'has_software'
    ];



    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'typedesc' => 'required|string|max:100',
            'has_software' => 'boolean',
        ];
    }
}