<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseManager;

class AuditLogModel
{
    private DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Create audit log entry
     */
    public function log(
        int $userId,
        string $assetType,
        int $assetId,
        string $action,
        array $details = [],
        string $ipAddress = ''
    ): int {
        return $this->db->insert('audit_log', [
            'user_id' => $userId,
            'asset_type' => $assetType,
            'asset_id' => $assetId,
            'action' => $action,
            'details' => json_encode($details),
            'timestamp' => time(),
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Get paginated audit logs
     */
    public function getPaginated(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['asset_type'])) {
            $whereConditions[] = "al.asset_type = :asset_type";
            $params['asset_type'] = $filters['asset_type'];
        }

        if (!empty($filters['user_id'])) {
            $whereConditions[] = "al.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $whereConditions[] = "al.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "al.timestamp >= :date_from";
            $params['date_from'] = strtotime($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "al.timestamp <= :date_to";
            $params['date_to'] = strtotime($filters['date_to'] . ' 23:59:59');
        }

        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

        // Get total count
        $countSql = "SELECT COUNT(*) FROM audit_log al {$whereClause}";
        $total = $this->db->fetchColumn($countSql, $params);

        // Get audit logs with user info
        $sql = "
            SELECT al.*,
                   u.username,
                   u.userdesc as display_name
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
            ORDER BY al.timestamp DESC
            LIMIT :limit OFFSET :offset
        ";
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $items = $this->db->fetchAll($sql, $params);

        // Process items to add formatted data
        $processedItems = array_map(function($item) {
            $item['formatted_timestamp'] = date('Y-m-d H:i:s', $item['timestamp']);
            $item['details_array'] = json_decode($item['details'] ?? '{}', true);
            $item['user_display'] = $item['display_name'] ?: $item['username'] ?: 'System';
            return $item;
        }, $items);

        return [
            'data' => $processedItems,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get distinct asset types for filtering
     */
    public function getAssetTypes(): array
    {
        return $this->db->fetchAll("SELECT DISTINCT asset_type FROM audit_log ORDER BY asset_type");
    }

    /**
     * Get distinct actions for filtering
     */
    public function getActions(): array
    {
        return $this->db->fetchAll("SELECT DISTINCT action FROM audit_log ORDER BY action");
    }

    /**
     * Get audit logs for specific asset
     */
    public function getForAsset(string $assetType, int $assetId, int $limit = 20): array
    {
        $sql = "
            SELECT al.*,
                   u.username,
                   u.userdesc as display_name
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.asset_type = :asset_type AND al.asset_id = :asset_id
            ORDER BY al.timestamp DESC
            LIMIT :limit
        ";

        $items = $this->db->fetchAll($sql, [
            'asset_type' => $assetType,
            'asset_id' => $assetId,
            'limit' => $limit
        ]);

        return array_map(function($item) {
            $item['formatted_timestamp'] = date('Y-m-d H:i:s', $item['timestamp']);
            $item['details_array'] = json_decode($item['details'] ?? '{}', true);
            $item['user_display'] = $item['display_name'] ?: $item['username'] ?: 'System';
            return $item;
        }, $items);
    }

    /**
     * Clean old audit logs (older than specified days)
     */
    public function cleanOldLogs(int $daysToKeep = 365): int
    {
        $cutoffTimestamp = time() - ($daysToKeep * 24 * 60 * 60);
        $stmt = $this->db->execute(
            "DELETE FROM audit_log WHERE timestamp < ?",
            [$cutoffTimestamp]
        );
        return $stmt->rowCount();
    }
}