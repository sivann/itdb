<?php

declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Services\DatabaseManager;

/**
 * Database seeder for test data
 */
class DatabaseSeeder
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Seed all test data
     */
    public function seedAll(): array
    {
        $ids = [];

        $ids['users'] = $this->seedUsers();
        $ids['locations'] = $this->seedLocations();
        $ids['item_types'] = $this->seedItemTypes();
        $ids['status_types'] = $this->seedStatusTypes();
        $ids['agents'] = $this->seedAgents();
        $ids['file_types'] = $this->seedFileTypes();
        $ids['contract_types'] = $this->seedContractTypes();

        return $ids;
    }

    /**
     * Seed users
     */
    public function seedUsers(): array
    {
        $users = [
            [
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'email' => 'admin@example.com',
                'user_type' => 2, // Admin
                'created_at' => time()
            ],
            [
                'username' => 'user',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'email' => 'user@example.com',
                'user_type' => 1, // Regular user
                'created_at' => time()
            ],
            [
                'username' => 'readonly',
                'password' => password_hash('readonly123', PASSWORD_DEFAULT),
                'email' => 'readonly@example.com',
                'user_type' => 0, // Read-only
                'created_at' => time()
            ]
        ];

        $ids = [];
        foreach ($users as $user) {
            $ids[] = $this->db->insert('users', $user);
        }

        return $ids;
    }

    /**
     * Seed locations
     */
    public function seedLocations(): array
    {
        $locations = [
            ['name' => 'Datacenter A', 'floor' => 'Ground', 'address' => '123 Main St'],
            ['name' => 'Datacenter B', 'floor' => 'Basement', 'address' => '456 Oak Ave'],
            ['name' => 'Office', 'floor' => '3rd Floor', 'address' => '789 Elm St'],
        ];

        $ids = [];
        foreach ($locations as $location) {
            $ids[] = $this->db->insert('locations', $location);
        }

        return $ids;
    }

    /**
     * Seed item types
     */
    public function seedItemTypes(): array
    {
        $types = [
            ['name' => 'Server', 'description' => 'Physical or virtual server'],
            ['name' => 'Network Switch', 'description' => 'Network switching equipment'],
            ['name' => 'Router', 'description' => 'Network routing equipment'],
            ['name' => 'Firewall', 'description' => 'Security appliance'],
            ['name' => 'Storage', 'description' => 'Storage array or NAS'],
            ['name' => 'Workstation', 'description' => 'Desktop computer'],
            ['name' => 'Laptop', 'description' => 'Portable computer'],
            ['name' => 'Monitor', 'description' => 'Display monitor'],
            ['name' => 'UPS', 'description' => 'Uninterruptible power supply'],
        ];

        $ids = [];
        foreach ($types as $type) {
            $ids[] = $this->db->insert('item_types', $type);
        }

        return $ids;
    }

    /**
     * Seed status types
     */
    public function seedStatusTypes(): array
    {
        $statuses = [
            ['name' => 'Active', 'color' => 'success'],
            ['name' => 'Inactive', 'color' => 'secondary'],
            ['name' => 'Maintenance', 'color' => 'warning'],
            ['name' => 'Failed', 'color' => 'danger'],
            ['name' => 'Decommissioned', 'color' => 'dark'],
            ['name' => 'In Storage', 'color' => 'info'],
        ];

        $ids = [];
        foreach ($statuses as $status) {
            $ids[] = $this->db->insert('status_types', $status);
        }

        return $ids;
    }

    /**
     * Seed agents (manufacturers, vendors, contractors)
     */
    public function seedAgents(): array
    {
        $agents = [
            [
                'name' => 'Dell Technologies',
                'email' => 'sales@dell.com',
                'website' => 'https://www.dell.com',
                'agent_type' => 1, // Manufacturer
            ],
            [
                'name' => 'HP Inc.',
                'email' => 'info@hp.com',
                'website' => 'https://www.hp.com',
                'agent_type' => 1,
            ],
            [
                'name' => 'Cisco Systems',
                'email' => 'support@cisco.com',
                'website' => 'https://www.cisco.com',
                'agent_type' => 1,
            ],
            [
                'name' => 'Microsoft',
                'email' => 'licensing@microsoft.com',
                'website' => 'https://www.microsoft.com',
                'agent_type' => 2, // Vendor
            ],
            [
                'name' => 'CDW Corporation',
                'email' => 'sales@cdw.com',
                'website' => 'https://www.cdw.com',
                'agent_type' => 2,
            ],
        ];

        $ids = [];
        foreach ($agents as $agent) {
            $ids[] = $this->db->insert('agents', $agent);
        }

        return $ids;
    }

    /**
     * Seed file types
     */
    public function seedFileTypes(): array
    {
        $types = [
            ['name' => 'Manual', 'description' => 'User manual or documentation', 'extensions' => 'pdf,doc,docx'],
            ['name' => 'Invoice', 'description' => 'Purchase invoice', 'extensions' => 'pdf,xls,xlsx'],
            ['name' => 'Photo', 'description' => 'Equipment photo', 'extensions' => 'jpg,jpeg,png'],
            ['name' => 'License', 'description' => 'Software license document', 'extensions' => 'pdf,txt'],
            ['name' => 'Configuration', 'description' => 'Configuration backup', 'extensions' => 'cfg,conf,txt'],
        ];

        $ids = [];
        foreach ($types as $type) {
            $ids[] = $this->db->insert('file_types', $type);
        }

        return $ids;
    }

    /**
     * Seed contract types
     */
    public function seedContractTypes(): array
    {
        $types = [
            ['name' => 'Maintenance', 'description' => 'Hardware maintenance contract'],
            ['name' => 'Support', 'description' => 'Software support contract'],
            ['name' => 'Service', 'description' => 'Professional services contract'],
            ['name' => 'Warranty', 'description' => 'Extended warranty'],
            ['name' => 'Lease', 'description' => 'Equipment lease agreement'],
        ];

        $ids = [];
        foreach ($types as $type) {
            $ids[] = $this->db->insert('contract_types', $type);
        }

        return $ids;
    }
}
