<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class LicenseTypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM license_types ORDER BY name");
    }
}
