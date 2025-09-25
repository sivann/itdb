<?php

// Simple test script to verify database connection and models work

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\DatabaseManager;
use App\Models\AgentModel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

try {
    // Create logger
    $logger = new Logger('test');
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

    // Create PDO connection
    $pdo = new PDO('sqlite:' . __DIR__ . '/src/data/itdb.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Create database manager
    $db = new DatabaseManager($pdo, $logger);

    // Create agent model
    $agentModel = new AgentModel($db);

    // Test basic functionality
    echo "Testing database connection...\n";

    // Test count
    $count = $db->count('agents');
    echo "Agent count: $count\n";

    // Test getting agents
    $result = $agentModel->getPaginated(1, 5);
    echo "Paginated agents: " . count($result['data']) . " agents\n";

    foreach ($result['data'] as $agent) {
        echo "- {$agent['title']} (ID: {$agent['id']})\n";
    }

    echo "\n✅ Test passed! Database and models are working.\n";

} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}