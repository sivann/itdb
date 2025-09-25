<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;

class DatabaseManager
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;

        // Enable foreign key constraints
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        // Set error mode to exceptions
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Set default fetch mode to associative array
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Execute a prepared statement with parameters
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->error('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            // Re-throw with constraint-friendly message
            throw $this->handleConstraintError($e, $sql, $params);
        }
    }

    /**
     * Fetch a single row
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single column value
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert a record and return the last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->execute($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update records
     */
    public function update(string $table, array $data, array $where): int
    {
        $setParts = array_map(fn($col) => "$col = :$col", array_keys($data));
        $whereParts = array_map(fn($col) => "$col = :where_$col", array_keys($where));

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );

        // Prefix where parameters to avoid conflicts
        $whereParams = [];
        foreach ($where as $key => $value) {
            $whereParams["where_$key"] = $value;
        }

        $params = array_merge($data, $whereParams);
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete records
     */
    public function delete(string $table, array $where): int
    {
        $whereParts = array_map(fn($col) => "$col = :$col", array_keys($where));

        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereParts)
        );

        $stmt = $this->execute($sql, $where);
        return $stmt->rowCount();
    }

    /**
     * Count records
     */
    public function count(string $table, array $where = []): int
    {
        $sql = "SELECT COUNT(*) FROM $table";
        $params = [];

        if (!empty($where)) {
            $whereParts = array_map(fn($col) => "$col = :$col", array_keys($where));
            $sql .= " WHERE " . implode(' AND ', $whereParts);
            $params = $where;
        }

        return (int) $this->fetchColumn($sql, $params);
    }

    /**
     * Check if a record exists
     */
    public function exists(string $table, array $where): bool
    {
        return $this->count($table, $where) > 0;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Execute within transaction
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get the underlying PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Handle constraint errors and convert to user-friendly messages
     */
    private function handleConstraintError(PDOException $e, string $sql, array $params): PDOException
    {
        $message = $e->getMessage();
        $userMessage = null;

        // FOREIGN KEY constraint failed
        if (str_contains($message, 'FOREIGN KEY constraint failed')) {
            $userMessage = $this->getForeignKeyErrorMessage($sql, $params);
        }

        // UNIQUE constraint failed
        elseif (str_contains($message, 'UNIQUE constraint failed')) {
            $userMessage = $this->getUniqueConstraintErrorMessage($sql, $message);
        }

        // NOT NULL constraint failed
        elseif (str_contains($message, 'NOT NULL constraint failed')) {
            $userMessage = $this->getNotNullErrorMessage($message);
        }

        // If we have a user-friendly message, create a new exception
        if ($userMessage) {
            $newException = new PDOException($userMessage, (int) $e->getCode());
            $newException->errorInfo = $e->errorInfo;
            return $newException;
        }

        return $e;
    }

    /**
     * Generate user-friendly foreign key error messages
     */
    private function getForeignKeyErrorMessage(string $sql, array $params): string
    {
        // Determine operation type
        $operation = 'operation';
        if (str_starts_with(trim(strtoupper($sql)), 'DELETE')) {
            $operation = 'delete';
        } elseif (str_starts_with(trim(strtoupper($sql)), 'INSERT')) {
            $operation = 'create';
        } elseif (str_starts_with(trim(strtoupper($sql)), 'UPDATE')) {
            $operation = 'update';
        }

        // Extract table name from SQL
        $tableName = $this->extractTableName($sql);
        $entityName = $this->getEntityName($tableName);

        switch ($operation) {
            case 'delete':
                return "Cannot delete this $entityName because it is referenced by other records. Please remove the associations first.";

            case 'create':
            case 'update':
                return "Cannot $operation $entityName because it references a record that doesn't exist. Please check the selected values.";

            default:
                return "Cannot complete operation due to data relationship constraints.";
        }
    }

    /**
     * Generate user-friendly unique constraint error messages
     */
    private function getUniqueConstraintErrorMessage(string $sql, string $message): string
    {
        $tableName = $this->extractTableName($sql);
        $entityName = $this->getEntityName($tableName);

        // Try to extract column name from error message
        if (preg_match('/UNIQUE constraint failed: \w+\.(\w+)/', $message, $matches)) {
            $columnName = $matches[1];
            $fieldName = $this->getFieldName($columnName);
            return "A $entityName with this $fieldName already exists. Please choose a different value.";
        }

        return "This $entityName already exists. Please check for duplicates.";
    }

    /**
     * Generate user-friendly NOT NULL error messages
     */
    private function getNotNullErrorMessage(string $message): string
    {
        if (preg_match('/NOT NULL constraint failed: \w+\.(\w+)/', $message, $matches)) {
            $columnName = $matches[1];
            $fieldName = $this->getFieldName($columnName);
            return "The $fieldName field is required and cannot be empty.";
        }

        return "A required field is missing. Please fill in all required fields.";
    }

    /**
     * Extract table name from SQL query
     */
    private function extractTableName(string $sql): string
    {
        // Try to match common SQL patterns
        if (preg_match('/(?:FROM|INTO|UPDATE|DELETE FROM)\s+(\w+)/i', $sql, $matches)) {
            return $matches[1];
        }

        return 'record';
    }

    /**
     * Convert table name to user-friendly entity name
     */
    private function getEntityName(string $tableName): string
    {
        $entityNames = [
            'items' => 'item',
            'agents' => 'agent',
            'users' => 'user',
            'software' => 'software',
            'contracts' => 'contract',
            'invoices' => 'invoice',
            'itemtypes' => 'item type',
            'contracttypes' => 'contract type',
            'agent_types' => 'agent type',
            'tags' => 'tag',
            'actions' => 'action',
            'contractevents' => 'contract event',
        ];

        return $entityNames[$tableName] ?? str_replace('_', ' ', $tableName);
    }

    /**
     * Convert column name to user-friendly field name
     */
    private function getFieldName(string $columnName): string
    {
        $fieldNames = [
            'title' => 'title',
            'name' => 'name',
            'username' => 'username',
            'email' => 'email',
            'stitle' => 'software title',
            'function' => 'function',
            'model' => 'model',
            'sn' => 'serial number',
            'dnsname' => 'DNS name',
            'code' => 'code',
        ];

        return $fieldNames[$columnName] ?? str_replace('_', ' ', $columnName);
    }
}