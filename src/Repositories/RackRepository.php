<?php

namespace App\Repositories;

use App\Models\Rack;
use PDO;

class RackRepository extends BaseRepository
{
    public function getAllRacks()
    {
        $sql = "SELECT 
                    r.id, 
                    r.usize, 
                    r.depth, 
                    r.label, 
                    l.name as location_name, 
                    la.areaname as location_area_name,
                    (SELECT COUNT(i.id) FROM items i WHERE i.rack_id = r.id) as population,
                    (SELECT SUM(i.usize) FROM items i WHERE i.rack_id = r.id) as occupation
                FROM racks r
                LEFT JOIN locations l ON r.location_id = l.id
                LEFT JOIN location_areas la ON r.location_area_id = la.id
                GROUP BY r.id";

        $stmt = $this->dbManager->getDb()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, Rack::class);
    }
}
