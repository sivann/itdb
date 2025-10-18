<?php

declare(strict_types=1);

namespace App\Models;


class ContractType extends BaseModel
{
    protected $table = 'contract_types';

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