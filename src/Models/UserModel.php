<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

/**
 * User data access model - handles all database operations for users (CRUD, authentication, permissions).
 */
class UserModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Get paginated users with optional filters
     */
    public function getPaginated(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $conditions[] = "(username LIKE :search OR display_name LIKE :search)";
            $params['search'] = $search;
        }

        if (isset($filters['user_type'])) {
            $conditions[] = "user_type = :user_type";
            $params['user_type'] = (int) $filters['user_type'];
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM users $whereClause";
        $total = (int) $this->db->fetchColumn($totalSql, $params);

        // Get users with item count
        $sql = "
            SELECT u.*,
                   COALESCE(item_count.count, 0) as items_count
            FROM users u
            LEFT JOIN (
                SELECT user_id, COUNT(*) as count
                FROM items
                WHERE user_id IS NOT NULL
                GROUP BY user_id
            ) item_count ON u.id = item_count.user_id
            $whereClause
            ORDER BY u.username
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $users = $this->db->fetchAll($sql, $params);

        // Add display_name field for template compatibility
        foreach ($users as &$user) {
            $user['display_name'] = !empty($user['display_name']) ? $user['display_name'] : $user['username'];
        }

        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Find user by ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $result = $this->db->fetchAll($sql, ['id' => $id]);
        if ($result) {
            $user = $result[0];
            // Add display_name field for template compatibility
            $user['display_name'] = !empty($user['display_name']) ? $user['display_name'] : $user['username'];
            return $user;
        }
        return null;
    }

    /**
     * Find user by ID with item count
     */
    public function findWithCounts(int $id): ?array
    {
        $sql = "
            SELECT u.*,
                   COALESCE(item_count.count, 0) as items_count
            FROM users u
            LEFT JOIN (
                SELECT user_id, COUNT(*) as count
                FROM items
                WHERE user_id = :id
                GROUP BY user_id
            ) item_count ON u.id = item_count.user_id
            WHERE u.id = :id
            LIMIT 1
        ";

        $result = $this->db->fetchAll($sql, ['id' => $id]);
        return $result ? $result[0] : null;
    }

    /**
     * Create new user
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO users (username, display_name, user_type, password_hash)
            VALUES (:username, :display_name, :user_type, :password_hash)
        ";

        $params = [
            'username' => $data['username'],
            'display_name' => $data['display_name'] ?? null,
            'user_type' => $data['user_type'] ?? 0,
            'password_hash' => $data['password'] ?? null
        ];

        $this->db->execute($sql, $params);
        return $this->db->getLastInsertId();
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'username', 'display_name', 'user_type',
            'date_format', 'timezone', 'language', 'use_system_defaults'
        ];

        // Filter only allowed fields that are present in $data
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        // Build dynamic UPDATE query
        $setParts = [];
        $params = ['id' => $id];

        foreach ($updateData as $field => $value) {
            $setParts[] = "$field = :$field";
            $params[$field] = $value;
        }

        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :id";

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update user password
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
        $stmt = $this->db->execute($sql, [
            'password_hash' => $hashedPassword,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete user
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->execute($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        $params = ['username' => $username];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Check if user can be deleted (has dependencies)
     */
    public function canDelete(int $id): array
    {
        $references = [];

        // Check for items assigned to user
        $itemCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM items WHERE user_id = :id",
            ['id' => $id]
        );

        if ($itemCount > 0) {
            $references[] = "User has {$itemCount} items assigned";
        }

        return [
            'can_delete' => empty($references),
            'references' => $references
        ];
    }

    /**
     * Get all users for dropdowns
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM users ORDER BY username");
    }

    /**
     * Get recent items for a user
     */
    public function getRecentItems(int $userId, int $limit = 10): array
    {
        $sql = "
            SELECT i.*, it.name as itemtype_name, st.name as status_name, l.name as location_name
            FROM items i
            LEFT JOIN item_types it ON i.item_type_id = it.id
            LEFT JOIN status_types st ON i.status_id = st.id
            LEFT JOIN locations l ON i.location_id = l.id
            WHERE i.user_id = :user_id
            ORDER BY i.id DESC
            LIMIT :limit
        ";

        return $this->db->fetchAll($sql, ['user_id' => $userId, 'limit' => $limit]);
    }

    /**
     * Verify user password
     */
    public function verifyPassword(int $userId, string $password): bool
    {
        $user = $this->find($userId);
        if (!$user || !isset($user['password_hash'])) {
            return false;
        }

        // In production, this would use password_verify() for hashed passwords
        // For now, doing simple comparison as per existing code
        return $user['password_hash'] === $password;
    }

    /**
     * Get items assigned to a user
     */
    public function getUserItems(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT i.id, i.label, i.function, i.model, it.name as type_name, a.name as manufacturer_name
             FROM items i
             LEFT JOIN item_types it ON i.item_type_id = it.id
             LEFT JOIN agents a ON i.manufacturer_id = a.id
             WHERE i.user_id = :user_id
             ORDER BY i.id DESC
             LIMIT 100",
            ['user_id' => $userId]
        );
    }
}