<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class ContractSubtypeModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM contractsubtypes ORDER BY name");
    }

    public function getByTypeId(int $typeId): array
    {
        return $this->db->fetchAll("SELECT * FROM contractsubtypes WHERE contypeid = ? ORDER BY name", [$typeId]);
    }
}
