<?php

declare(strict_types=1);

namespace App\Models;


class FileType extends BaseModel
{
    protected $table = 'filetypes';

    protected $fillable = [
        'typedesc'
    ];



    public function getValidationRules(): array
    {
        return [
            'typedesc' => 'required|string|max:100',
        ];
    }
}