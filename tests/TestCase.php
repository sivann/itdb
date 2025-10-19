<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Services\DatabaseManager;
use DI\Container;
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;
    protected DatabaseManager $db;
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Initialize database schema
        $this->initializeTestDatabase();

        // Create container with test dependencies
        $this->container = new Container();

        // Create a null logger for tests (no actual logging)
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler(new \Monolog\Handler\NullHandler());

        $this->db = new DatabaseManager($this->pdo, $logger);
        $this->container->set(DatabaseManager::class, $this->db);
    }

    protected function tearDown(): void
    {
        // No need to manually clean up - PHP will handle it
        // In-memory database is automatically destroyed

        parent::tearDown();
    }

    /**
     * Initialize test database with schema
     */
    protected function initializeTestDatabase(): void
    {
        // Read the schema from your actual database or a schema file
        $schema = $this->getTestSchema();
        $this->pdo->exec($schema);
    }

    /**
     * Get test database schema
     * This should match your production schema
     */
    protected function getTestSchema(): string
    {
        return <<<SQL
        -- Users table
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            email TEXT,
            user_type INTEGER DEFAULT 0,
            created_at INTEGER,
            updated_at INTEGER
        );

        -- Locations table
        CREATE TABLE locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            floor TEXT,
            address TEXT,
            comments TEXT
        );

        -- Location areas table
        CREATE TABLE location_areas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            location_id INTEGER,
            name TEXT NOT NULL,
            comments TEXT,
            FOREIGN KEY (location_id) REFERENCES locations(id)
        );

        -- Item types table
        CREATE TABLE item_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT
        );

        -- Status types table
        CREATE TABLE status_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            color TEXT
        );

        -- Agents table (manufacturers, vendors, contractors)
        CREATE TABLE agents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            address TEXT,
            phone TEXT,
            email TEXT,
            website TEXT,
            comments TEXT,
            agent_type INTEGER DEFAULT 0
        );

        -- Invoices table
        CREATE TABLE invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_date INTEGER,
            vendor_id INTEGER,
            buyer_id INTEGER,
            comments TEXT,
            total_cost REAL,
            FOREIGN KEY (vendor_id) REFERENCES agents(id),
            FOREIGN KEY (buyer_id) REFERENCES agents(id)
        );

        -- Racks table
        CREATE TABLE racks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            location_id INTEGER,
            location_area_id INTEGER,
            label TEXT,
            model TEXT,
            size_units INTEGER DEFAULT 42,
            depth_mm INTEGER,
            comments TEXT,
            reverse_numbering INTEGER DEFAULT 0,
            FOREIGN KEY (location_id) REFERENCES locations(id),
            FOREIGN KEY (location_area_id) REFERENCES location_areas(id)
        );

        -- Items table
        CREATE TABLE items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            serial_number TEXT,
            asset_tag TEXT,
            item_type_id INTEGER,
            manufacturer_id INTEGER,
            model TEXT,
            status_id INTEGER,
            location_id INTEGER,
            rack_id INTEGER,
            rack_position INTEGER,
            rack_units INTEGER DEFAULT 1,
            user_id INTEGER,
            purchase_date INTEGER,
            warranty_expiry INTEGER,
            price REAL,
            comments TEXT,
            created_at INTEGER,
            updated_at INTEGER,
            FOREIGN KEY (item_type_id) REFERENCES item_types(id),
            FOREIGN KEY (manufacturer_id) REFERENCES agents(id),
            FOREIGN KEY (status_id) REFERENCES status_types(id),
            FOREIGN KEY (location_id) REFERENCES locations(id),
            FOREIGN KEY (rack_id) REFERENCES racks(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        -- Software table
        CREATE TABLE software (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            version TEXT,
            sinfo TEXT,
            license_key TEXT,
            licqty INTEGER DEFAULT 1,
            license_type INTEGER DEFAULT 0,
            stype INTEGER,
            manufacturer_id INTEGER,
            invoice_id INTEGER,
            purchdate INTEGER,
            comments TEXT,
            FOREIGN KEY (manufacturer_id) REFERENCES agents(id),
            FOREIGN KEY (invoice_id) REFERENCES invoices(id)
        );

        -- Files table
        CREATE TABLE files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename_stored TEXT NOT NULL,
            filename_original TEXT NOT NULL,
            file_type_id INTEGER,
            file_size INTEGER,
            uploaded_at INTEGER,
            uploaded_by INTEGER,
            comments TEXT,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        );

        -- File types table
        CREATE TABLE file_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            extensions TEXT
        );

        -- Contracts table
        CREATE TABLE contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            contract_type_id INTEGER,
            contractor_id INTEGER,
            start_date INTEGER,
            end_date INTEGER,
            value REAL,
            comments TEXT,
            FOREIGN KEY (contractor_id) REFERENCES agents(id)
        );

        -- Contract types table
        CREATE TABLE contract_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT
        );

        -- Junction tables
        CREATE TABLE items_files (
            item_id INTEGER,
            file_id INTEGER,
            PRIMARY KEY (item_id, file_id),
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
        );

        CREATE TABLE items_invoices (
            item_id INTEGER,
            invoice_id INTEGER,
            PRIMARY KEY (item_id, invoice_id),
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        );

        CREATE TABLE software_invoices (
            software_id INTEGER,
            invoice_id INTEGER,
            PRIMARY KEY (software_id, invoice_id),
            FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE CASCADE,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        );

        CREATE TABLE software_items (
            software_id INTEGER,
            item_id INTEGER,
            PRIMARY KEY (software_id, item_id),
            FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
        );

        CREATE TABLE invoices_files (
            invoice_id INTEGER,
            file_id INTEGER,
            PRIMARY KEY (invoice_id, file_id),
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
            FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
        );

        CREATE TABLE contracts_invoices (
            contractid INTEGER,
            invoice_id INTEGER,
            PRIMARY KEY (contractid, invoice_id),
            FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE CASCADE,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        );

        -- Item history table
        CREATE TABLE itemhistory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER,
            user_id INTEGER,
            action TEXT,
            description TEXT,
            date INTEGER,
            ip TEXT,
            FOREIGN KEY (item_id) REFERENCES items(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        SQL;
    }

    /**
     * Seed test data
     */
    protected function seedTestData(): void
    {
        // Insert basic test data
        $this->db->insert('users', [
            'username' => 'testuser',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'test@example.com',
            'user_type' => 1
        ]);

        $this->db->insert('locations', [
            'name' => 'Test Location',
            'floor' => 'Ground'
        ]);

        $this->db->insert('item_types', [
            'name' => 'Server',
            'description' => 'Test server type'
        ]);

        $this->db->insert('status_types', [
            'name' => 'Active',
            'color' => 'success'
        ]);

        $this->db->insert('agents', [
            'name' => 'Test Manufacturer',
            'agent_type' => 1
        ]);
    }

    /**
     * Create a test item
     */
    protected function createTestItem(array $overrides = []): int
    {
        $defaults = [
            'name' => 'Test Item',
            'serial_number' => 'TEST-' . rand(1000, 9999),
            'item_type_id' => 1,
            'status_id' => 1
        ];

        return $this->db->insert('items', array_merge($defaults, $overrides));
    }

    /**
     * Create a test rack
     */
    protected function createTestRack(array $overrides = []): int
    {
        $defaults = [
            'label' => 'TEST-RACK-' . rand(1, 999),
            'size_units' => 42,
            'depth_mm' => 800,
            'location_id' => 1
        ];

        return $this->db->insert('racks', array_merge($defaults, $overrides));
    }
}
