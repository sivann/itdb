<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class SettingsModel
{
    private DatabaseManager $db;
    private ?array $cachedSettings = null;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Get all settings as an associative array
     */
    public function getAll(): array
    {
        if ($this->cachedSettings === null) {
            $this->cachedSettings = $this->db->fetchOne("SELECT * FROM settings LIMIT 1") ?? [];
        }

        return $this->cachedSettings;
    }

    /**
     * Get a specific setting value
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->getAll();
        return $settings[$key] ?? $default;
    }

    /**
     * Update a setting value
     */
    public function set(string $key, $value): bool
    {
        // Clear cache
        $this->cachedSettings = null;

        // Update the setting
        $rowsAffected = $this->db->update('settings', [$key => $value], []);
        return $rowsAffected > 0;
    }

    /**
     * Get file storage path from settings (validated and canonicalized)
     */
    public function getFileStoragePath(): string
    {
        $path = $this->get('file_storage_path', './public/storage/uploads');
        return $this->validateAndCanonicalizePath($path);
    }

    /**
     * Update file storage path with validation
     */
    public function setFileStoragePath(string $path): bool
    {
        // Validate the path before saving
        $validatedPath = $this->validateAndCanonicalizePath($path);
        if (!$validatedPath) {
            throw new \InvalidArgumentException('Invalid file storage path: path must be within the project directory');
        }

        return $this->set('file_storage_path', $path);
    }

    /**
     * Validate and canonicalize a file path to ensure it's within the project directory
     */
    private function validateAndCanonicalizePath(string $path): string
    {
        // Get the project root directory (assuming this file is in src/Models/)
        $projectRoot = dirname(__DIR__, 2);

        // Convert relative path to absolute if needed
        if (!$this->isAbsolutePath($path)) {
            $path = $projectRoot . '/' . $path;
        }

        // Resolve the real path
        $realPath = realpath($path);

        // If path doesn't exist yet, validate the parent directory structure
        if ($realPath === false) {
            // Try to validate the intended path by checking if it's under project root
            $normalizedPath = $this->normalizePath($path);
            $normalizedProjectRoot = $this->normalizePath($projectRoot);

            // Ensure the path starts with the project root
            if (strpos($normalizedPath, $normalizedProjectRoot) !== 0) {
                throw new \RuntimeException('File storage path must be within the project directory');
            }

            return $path;
        }

        // Get the real project root
        $realProjectRoot = realpath($projectRoot);

        // Ensure the resolved path is within the project directory
        if (strpos($realPath, $realProjectRoot) !== 0) {
            throw new \RuntimeException('File storage path must be within the project directory');
        }

        return $realPath;
    }

    /**
     * Normalize a path by resolving .. and . components
     */
    private function normalizePath(string $path): string
    {
        $parts = explode('/', str_replace('\\', '/', $path));
        $normalized = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($normalized);
            } else {
                $normalized[] = $part;
            }
        }

        $result = implode('/', $normalized);

        // Preserve leading slash for absolute paths
        if ($path[0] === '/') {
            $result = '/' . $result;
        }

        return $result;
    }

    /**
     * Check if a path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        // Unix-style absolute path
        if ($path[0] === '/') {
            return true;
        }

        // Windows-style absolute path (C:\ or C:/)
        if (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/')) {
            return true;
        }

        return false;
    }

    /**
     * Clear the settings cache
     */
    public function clearCache(): void
    {
        $this->cachedSettings = null;
    }
}
