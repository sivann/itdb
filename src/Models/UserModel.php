<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

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
            $conditions[] = "(username LIKE :search OR realname LIKE :search OR comments LIKE :search)";
            $params['search'] = $search;
        }

        if (isset($filters['usertype'])) {
            $conditions[] = "usertype = :usertype";
            $params['usertype'] = (int) $filters['usertype'];
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
                SELECT userid, COUNT(*) as count
                FROM items
                WHERE userid IS NOT NULL
                GROUP BY userid
            ) item_count ON u.id = item_count.userid
            $whereClause
            ORDER BY u.username
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $users = $this->db->fetchAll($sql, $params);

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
        return $result ? $result[0] : null;
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
                SELECT userid, COUNT(*) as count
                FROM items
                WHERE userid = :id
                GROUP BY userid
            ) item_count ON u.id = item_count.userid
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
            INSERT INTO users (username, realname, usertype, password, comments)
            VALUES (:username, :realname, :usertype, :password, :comments)
        ";

        $params = [
            'username' => $data['username'],
            'realname' => $data['realname'] ?? null,
            'usertype' => $data['usertype'] ?? 0,
            'password' => $data['password'] ?? null,
            'comments' => $data['comments'] ?? null
        ];

        $this->db->execute($sql, $params);
        return $this->db->getLastInsertId();
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE users SET
                username = :username,
                realname = :realname,
                usertype = :usertype,
                comments = :comments
            WHERE id = :id
        ";

        $params = [
            'username' => $data['username'],
            'realname' => $data['realname'] ?? null,
            'usertype' => $data['usertype'] ?? 0,
            'comments' => $data['comments'] ?? null,
            'id' => $id
        ];

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update user password
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->execute($sql, [
            'password' => $hashedPassword,
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
            "SELECT COUNT(*) FROM items WHERE userid = :id",
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
            SELECT i.*, it.name as itemtype_name, st.statusdesc as status_name, l.name as location_name
            FROM items i
            LEFT JOIN itemtypes it ON i.itemtypeid = it.id
            LEFT JOIN statustypes st ON i.status = st.id
            LEFT JOIN locations l ON i.locationid = l.id
            WHERE i.userid = :userid
            ORDER BY i.id DESC
            LIMIT :limit
        ";

        return $this->db->fetchAll($sql, ['userid' => $userId, 'limit' => $limit]);
    }

    /**
     * Verify user password
     */
    public function verifyPassword(int $userId, string $password): bool
    {
        $user = $this->find($userId);
        if (!$user || !isset($user['password'])) {
            return false;
        }

        // In production, this would use password_verify() for hashed passwords
        // For now, doing simple comparison as per existing code
        return $user['password'] === $password;
    }
}