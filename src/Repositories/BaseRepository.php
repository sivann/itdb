<?php

namespace App\Repositories;

use App\Services\DatabaseManager;

abstract class BaseRepository
{
    protected $dbManager;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }
}
