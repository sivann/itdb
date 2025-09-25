<?php

declare(strict_types=1);

namespace App\Models;


class ContractType extends BaseModel
{
    protected $table = 'contracttypes';

    protected $fillable = [
        'name'
    ];



    public function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:100',
        ];
    }
}