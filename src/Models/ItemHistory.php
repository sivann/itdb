<?php

declare(strict_types=1);

namespace App\Models;


class ItemHistory extends BaseModel
{
    protected $table = 'itemhistory';

    protected $fillable = [
        'itemid',
        'userid',
        'action',
        'description',
        'date',
        'ip'
    ];



    /**
     * Get formatted date
     */
    public function getDateFormatted(): ?string
    {
        return $this->formatDate($this->date);
    }
}