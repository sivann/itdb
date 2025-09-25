<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Legacy BaseModel for backward compatibility
 * Note: This is a placeholder class for models that haven't been converted to the new DatabaseManager pattern
 * Most methods are stubbed to prevent fatal errors but won't actually work
 */
abstract class BaseModel
{
    protected $attributes = [];
    protected $fillable = [];
    protected $table = null;

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return $this->table ?? strtolower(class_basename($this));
    }

    /**
     * Get fillable attributes
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Get attribute value
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Stub methods for compatibility - these won't work without DatabaseManager
     */
    public static function createSafe(array $attributes): self
    {
        throw new \Exception('Legacy model methods not supported. Convert to DatabaseManager pattern.');
    }

    public function fillSafe(array $attributes): self
    {
        throw new \Exception('Legacy model methods not supported. Convert to DatabaseManager pattern.');
    }

    public function updateSafe(array $attributes): bool
    {
        throw new \Exception('Legacy model methods not supported. Convert to DatabaseManager pattern.');
    }

    public function save(): bool
    {
        throw new \Exception('Legacy model methods not supported. Convert to DatabaseManager pattern.');
    }

    /**
     * Utility method: Get formatted date attribute
     */
    public static function formatDate($value, string $format = 'Y-m-d'): ?string
    {
        if (!$value) {
            return null;
        }

        if (is_numeric($value)) {
            return date($format, (int) $value);
        }

        $timestamp = strtotime($value);
        return $timestamp ? date($format, $timestamp) : null;
    }

    /**
     * Utility method: Get human readable time difference
     */
    public static function timeAgo($value): ?string
    {
        if (!$value) {
            return null;
        }

        $timestamp = is_numeric($value) ? (int) $value : strtotime($value);
        if (!$timestamp) {
            return null;
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('Y-m-d', $timestamp);
        }
    }
}