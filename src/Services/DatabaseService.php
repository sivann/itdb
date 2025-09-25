<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;

class DatabaseService extends BaseService
{
    public function __construct(LoggerInterface $logger, DatabaseManager $db)
    {
        parent::__construct($logger);
        $this->setDatabaseManager($db);
    }

    /**
     * Test database connection
     */
    public function testConnection(): bool
    {
        try {
            $this->db->getPdo();
            return true;
        } catch (\Exception $e) {
            $this->logError('Database connection failed', $e);
            return false;
        }
    }

    /**
     * Get database schema information
     */
    public function getSchemaInfo(): array
    {
        try {
            $pdo = $this->db->getPdo();

            if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $tables = $this->db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

                $schema = [];
                foreach ($tables as $table) {
                    $tableName = $table['name'];
                    $columns = $this->db->fetchAll("PRAGMA table_info($tableName)");

                    $schema[$tableName] = [
                        'columns' => array_map(function($col) {
                            return [
                                'name' => $col['name'],
                                'type' => $col['type'],
                                'nullable' => !$col['notnull'],
                                'default' => $col['dflt_value'],
                                'primary_key' => (bool) $col['pk']
                            ];
                        }, $columns)
                    ];
                }

                return $schema;
            }

            return [];
        } catch (\Exception $e) {
            $this->logError('Failed to get schema info', $e);
            return [];
        }
    }

    /**
     * Execute raw SQL query
     */
    public function executeQuery(string $sql, array $bindings = []): array
    {
        try {
            return $this->db->fetchAll($sql, $bindings);
        } catch (\Exception $e) {
            $this->logError('Query execution failed', $e, ['sql' => $sql, 'bindings' => $bindings]);
            throw $e;
        }
    }

    /**
     * Get table row count
     */
    public function getTableRowCount(string $table): int
    {
        try {
            return $this->db->count($table);
        } catch (\Exception $e) {
            $this->logError('Failed to get row count', $e, ['table' => $table]);
            return 0;
        }
    }

    /**
     * Backup database to file
     */
    public function backupDatabase(string $backupPath): bool
    {
        try {
            $pdo = $this->db->getPdo();

            if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                // For SQLite, we need to get the database file path
                $dbName = $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
                if (empty($dbName)) {
                    // Fallback - try to extract from DSN
                    $config = $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
                    // This is a simplified approach - in reality you'd need to parse the DSN
                    return false;
                }
                return copy($dbName, $backupPath);
            }

            // For other database types, we'd need to implement proper backup logic
            return false;
        } catch (\Exception $e) {
            $this->logError('Database backup failed', $e, ['backup_path' => $backupPath]);
            return false;
        }
    }

    /**
     * Get database statistics
     */
    public function getDatabaseStats(): array
    {
        try {
            $schema = $this->getSchemaInfo();
            $stats = [
                'total_tables' => count($schema),
                'tables' => []
            ];

            foreach ($schema as $tableName => $tableInfo) {
                $stats['tables'][$tableName] = [
                    'columns' => count($tableInfo['columns']),
                    'rows' => $this->getTableRowCount($tableName)
                ];
            }

            return $stats;
        } catch (\Exception $e) {
            $this->logError('Failed to get database stats', $e);
            return [];
        }
    }

    /**
     * Check if migration is needed by comparing schema versions
     */
    public function needsMigration(): bool
    {
        try {
            // Check if settings table exists and has dbversion
            $result = $this->executeQuery("SELECT dbversion FROM settings LIMIT 1");

            if (empty($result)) {
                return true;
            }

            $currentVersion = $result[0]['dbversion'] ?? 0;
            $targetVersion = 6; // From original ITDB code

            return $currentVersion < $targetVersion;
        } catch (\Exception $e) {
            // If we can't check version, assume migration is needed
            return true;
        }
    }

    /**
     * Get current database version
     */
    public function getCurrentVersion(): int
    {
        try {
            $result = $this->executeQuery("SELECT dbversion FROM settings LIMIT 1");
            return $result[0]['dbversion'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Update database version
     */
    public function updateVersion(int $version): bool
    {
        try {
            $this->db->execute("UPDATE settings SET dbversion = ?", [$version]);
            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update database version', $e, ['version' => $version]);
            return false;
        }
    }
}