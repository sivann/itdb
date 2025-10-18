<?php

declare(strict_types=1);

namespace App\Models;


class ItemHistory extends BaseModel
{
    protected $table = 'itemhistory';

    protected $fillable = [
        'item_id',
        'user_id',
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