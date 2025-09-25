<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseService
{
    protected LoggerInterface $logger;
    protected ValidatorInterface $validator;
    protected ?DatabaseManager $db = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->validator = Validation::createValidator();
    }

    /**
     * Set the database manager (for services that need it)
     */
    public function setDatabaseManager(DatabaseManager $db): void
    {
        $this->db = $db;
    }

    /**
     * Begin database transaction
     */
    protected function beginTransaction(): void
    {
        if ($this->db) {
            $this->db->beginTransaction();
        }
    }

    /**
     * Commit database transaction
     */
    protected function commitTransaction(): void
    {
        if ($this->db) {
            $this->db->commit();
        }
    }

    /**
     * Rollback database transaction
     */
    protected function rollbackTransaction(): void
    {
        if ($this->db) {
            $this->db->rollback();
        }
    }

    /**
     * Execute code within a database transaction
     */
    protected function executeInTransaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback();
            $this->commitTransaction();
            return $result;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            $this->logger->error('Transaction failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate data against rules
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if ($this->isRequired($rule) && empty($value)) {
                $errors[] = "The {$field} field is required.";
                continue;
            }

            if (!empty($value)) {
                $fieldErrors = $this->validateField($field, $value, $rule);
                $errors = array_merge($errors, $fieldErrors);
            }
        }

        return $errors;
    }

    /**
     * Check if field is required
     */
    private function isRequired(string $rule): bool
    {
        return strpos($rule, 'required') !== false;
    }

    /**
     * Validate individual field
     */
    private function validateField(string $field, $value, string $rule): array
    {
        $errors = [];
        $rules = explode('|', $rule);

        foreach ($rules as $singleRule) {
            if (strpos($singleRule, ':') !== false) {
                [$ruleName, $parameter] = explode(':', $singleRule, 2);
            } else {
                $ruleName = $singleRule;
                $parameter = null;
            }

            switch ($ruleName) {
                case 'string':
                    if (!is_string($value)) {
                        $errors[] = "The {$field} must be a string.";
                    }
                    break;

                case 'integer':
                    if (!is_numeric($value) || (int)$value != $value) {
                        $errors[] = "The {$field} must be an integer.";
                    }
                    break;

                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "The {$field} must be a valid email address.";
                    }
                    break;

                case 'min':
                    if (strlen($value) < (int)$parameter) {
                        $errors[] = "The {$field} must be at least {$parameter} characters.";
                    }
                    break;

                case 'max':
                    if (strlen($value) > (int)$parameter) {
                        $errors[] = "The {$field} may not be greater than {$parameter} characters.";
                    }
                    break;

                case 'unique':
                    if ($this->isValueUnique($parameter, $field, $value)) {
                        $errors[] = "The {$field} has already been taken.";
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Check if value is unique in database
     */
    private function isValueUnique(string $table, string $field, $value): bool
    {
        if (!$this->db) {
            return false; // If no DB connection, assume not unique
        }

        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$table} WHERE {$field} = ?",
            [$value]
        );

        return $count > 0;
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } elseif (is_numeric($value)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a single string value
     */
    protected function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    /**
     * Log service action
     */
    protected function logAction(string $action, array $data = []): void
    {
        $this->logger->info("Service action: {$action}", [
            'service' => static::class,
            'action' => $action,
            'data' => $data
        ]);
    }

    /**
     * Log service error
     */
    protected function logError(string $message, ?\Exception $exception = null, array $context = []): void
    {
        $logData = [
            'service' => static::class,
            'message' => $message,
            'context' => $context
        ];

        if ($exception) {
            $logData['exception'] = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        $this->logger->error($message, $logData);
    }

    /**
     * Calculate pagination metadata
     */
    protected function calculatePagination(int $total, int $page = 1, int $perPage = 25): array
    {
        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total)
        ];
    }

    /**
     * Convert timestamp to user timezone
     */
    protected function convertToUserTimezone(int $timestamp, string $timezone = 'UTC'): \DateTime
    {
        $date = new \DateTime('@' . $timestamp);
        $date->setTimezone(new \DateTimeZone($timezone));
        return $date;
    }

    /**
     * Generate unique identifier
     */
    protected function generateUniqueId(): string
    {
        return uniqid() . '_' . time() . '_' . mt_rand(1000, 9999);
    }

    /**
     * Clean filename for safe storage
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);

        // Replace unsafe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);

        // Ensure filename is not empty
        if (empty($filename) || $filename === '.') {
            $filename = 'file_' . time();
        }

        return $filename;
    }
}