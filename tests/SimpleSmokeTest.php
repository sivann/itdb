<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use PDO;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use App\Services\DatabaseManager;
use App\Models\RackModel;

/**
 * Simple smoke test to verify database operations work
 */
class SimpleSmokeTest extends TestCase
{
    public function testDatabaseConnectionWorks(): void
    {
        $pdo = new PDO('sqlite:src/data/itdb.db');
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $db = new DatabaseManager($pdo, $logger);

        $count = $db->fetchColumn('SELECT COUNT(*) FROM racks');

        $this->assertIsNumeric($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testRackModelWorks(): void
    {
        $pdo = new PDO('sqlite:src/data/itdb.db');
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $db = new DatabaseManager($pdo, $logger);
        $rackModel = new RackModel($db);

        $racks = $rackModel->getAll();

        $this->assertIsArray($racks);
    }

    public function testRackHasCorrectColumns(): void
    {
        $pdo = new PDO('sqlite:src/data/itdb.db');

        $rack = $pdo->query('SELECT * FROM racks LIMIT 1')->fetch(PDO::FETCH_ASSOC);

        if ($rack) {
            // Verify new column names exist (post-migration)
            $this->assertArrayHasKey('size_units', $rack);
            $this->assertArrayHasKey('depth_mm', $rack);

            // Verify old column names don't exist
            $this->assertArrayNotHasKey('usize', $rack);
            $this->assertArrayNotHasKey('depth', $rack);
        } else {
            $this->markTestSkipped('No racks in database to test');
        }
    }
}
