<?php

declare(strict_types=1);

namespace App\Models;


class StatusType extends BaseModel
{
    protected $table = 'statustypes';

    protected $fillable = [
        'statusdesc'
    ];



    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'statusdesc' => 'required|string|max:100',
        ];
    }
}