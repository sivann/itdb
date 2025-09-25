<?php

declare(strict_types=1);

namespace App\Models;


class Tag extends BaseModel
{
    protected $table = 'tags';

    protected $fillable = [
        'name'
    ];



    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:50|unique:tags,name',
        ];
    }

}