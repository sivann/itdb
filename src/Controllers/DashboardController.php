<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ItemModel;
use App\Models\SoftwareModel;
use App\Models\ContractModel;
use App\Services\AuthService;
use App\Services\DatabaseService;
use App\Services\DatabaseManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class DashboardController extends BaseController
{
    private AuthService $authService;
    private DatabaseService $databaseService;
    private DatabaseManager $db;
    private ItemModel $itemModel;
    private SoftwareModel $softwareModel;
    private ContractModel $contractModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        DatabaseService $databaseService,
        DatabaseManager $db,
        ItemModel $itemModel,
        SoftwareModel $softwareModel,
        ContractModel $contractModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->databaseService = $databaseService;
        $this->db = $db;
        $this->itemModel = $itemModel;
        $this->softwareModel = $softwareModel;
        $this->contractModel = $contractModel;
    }

    /**
     * Show dashboard/home page
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get dashboard statistics
        $stats = $this->getDashboardStats();

        // Get recent items (simplified)
        $recentItems = $this->getRecentItems(10);

        // Get recent software (simplified)
        $recentSoftware = $this->getRecentSoftware(5);

        // Get items assigned to current user
        $myItems = [];
        if ($user) {
            $myItems = $this->getUserItems($user->id ?? 0, 10);
        }

        // Skip complex warranty/license expiration for now
        $expiringWarranties = [];
        $expiringLicenses = [];

        // Get system information
        $systemInfo = [
            'version' => $this->getSystemVersion(),
            'database_version' => $this->databaseService->getCurrentVersion(),
            'php_version' => PHP_VERSION,
            'database_stats' => $this->databaseService->getDatabaseStats(),
        ];

        return $this->render($response, 'dashboard/index.twig', [
            'user' => $user,
            'stats' => $stats,
            'recent_items' => $recentItems,
            'recent_software' => $recentSoftware,
            'my_items' => $myItems,
            'expiring_warranties' => $expiringWarranties,
            'expiring_licenses' => $expiringLicenses,
            'system_info' => $systemInfo,
        ]);
    }

    /**
     * Show about page
     */
    public function about(Request $request, Response $response): Response
    {
        $systemInfo = [
            'app_name' => $_ENV['APP_NAME'] ?? 'ITDB',
            'version' => $this->getSystemVersion(),
            'php_version' => PHP_VERSION,
            'slim_version' => $this->getSlimVersion(),
            'database_info' => $this->getDatabaseInfo(),
            'server_info' => $this->getServerInfo(),
        ];

        return $this->render($response, 'dashboard/about.twig', [
            'system_info' => $systemInfo,
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array
    {
        try {
            $stats = [
                'items' => [
                    'total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM items"),
                    'active' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM items WHERE status = 1"),
                    'under_warranty' => 0, // Skip warranty calculation for now
                    'recent' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM items WHERE date_received >= ?", [time() - (30 * 86400)]),
                ],
                'software' => [
                    'total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM software"),
                    'with_licenses' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM software WHERE licqty IS NOT NULL AND licqty > 0"),
                    'active_licenses' => 0, // Skip active license calculation for now
                    'expiring_soon' => 0, // Skip expiring license calculation for now
                ],
                'users' => [
                    'total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM users"),
                    'active' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE usertype > 0"),
                ],
                'storage' => $this->getStorageStats(),
            ];

            // Add contract stats
            $contractCount = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM contracts");
            $activeContracts = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM contracts WHERE currentenddate > ? OR currentenddate IS NULL", [time()]);
            $stats['contracts'] = [
                'total' => $contractCount,
                'active' => $activeContracts,
            ];

            // Add invoice stats if table exists
            try {
                $invoiceCount = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM invoices");
                $totalAmount = (float) ($this->db->fetchColumn("SELECT SUM(total) FROM invoices") ?? 0);
                $stats['invoices'] = [
                    'total' => $invoiceCount,
                    'total_amount' => $totalAmount,
                ];
            } catch (\Exception $e) {
                // Invoices table might not exist
                $stats['invoices'] = ['total' => 0, 'total_amount' => 0];
            }

            return $stats;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get dashboard stats', [
                'error' => $e->getMessage()
            ]);

            // Return empty stats on error
            return [
                'items' => ['total' => 0, 'active' => 0, 'under_warranty' => 0, 'recent' => 0],
                'software' => ['total' => 0, 'with_licenses' => 0, 'active_licenses' => 0, 'expiring_soon' => 0],
                'users' => ['total' => 0, 'active' => 0],
                'storage' => ['files' => 0, 'size' => 0],
                'contracts' => ['total' => 0, 'active' => 0],
                'invoices' => ['total' => 0, 'total_amount' => 0],
            ];
        }
    }

    /**
     * Get storage statistics
     */
    private function getStorageStats(): array
    {
        try {
            $uploadPath = $_ENV['UPLOAD_PATH'] ?? './storage/uploads';

            if (!is_dir($uploadPath)) {
                return ['files' => 0, 'size' => 0];
            }

            $files = glob($uploadPath . '/*');
            $totalSize = 0;

            foreach ($files as $file) {
                if (is_file($file)) {
                    $totalSize += filesize($file);
                }
            }

            return [
                'files' => count($files),
                'size' => $totalSize,
                'size_formatted' => $this->formatBytes($totalSize),
            ];

        } catch (\Exception $e) {
            return ['files' => 0, 'size' => 0, 'size_formatted' => '0 B'];
        }
    }

    /**
     * Get system version
     */
    private function getSystemVersion(): string
    {
        // Try to read from VERSION file (legacy ITDB)
        $versionFile = __DIR__ . '/../../VERSION';
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }

        // Try to get from composer.json
        $composerFile = __DIR__ . '/../../composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? '2.0.0-dev';
        }

        return '2.0.0-dev';
    }

    /**
     * Get Slim framework version
     */
    private function getSlimVersion(): string
    {
        $composerLock = __DIR__ . '/../../composer.lock';
        if (file_exists($composerLock)) {
            $lockData = json_decode(file_get_contents($composerLock), true);
            foreach ($lockData['packages'] ?? [] as $package) {
                if ($package['name'] === 'slim/slim') {
                    return $package['version'];
                }
            }
        }

        return 'Unknown';
    }

    /**
     * Get database information
     */
    private function getDatabaseInfo(): array
    {
        try {
            return [
                'type' => 'SQLite',
                'version' => $this->databaseService->getCurrentVersion(),
                'file' => $_ENV['DB_DATABASE'] ?? 'Unknown',
                'size' => $this->getDatabaseSize(),
                'tables' => count($this->databaseService->getSchemaInfo()),
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'Unknown',
                'version' => 'Unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database file size
     */
    private function getDatabaseSize(): string
    {
        $dbFile = $_ENV['DB_DATABASE'] ?? '';
        if (file_exists($dbFile)) {
            return $this->formatBytes(filesize($dbFile));
        }

        return 'Unknown';
    }

    /**
     * Get server information
     */
    private function getServerInfo(): array
    {
        return [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'php_sapi' => php_sapi_name(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * API endpoint for dashboard statistics
     */
    public function apiStats(Request $request, Response $response): Response
    {
        $stats = $this->getDashboardStats();

        return $this->json($response, [
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * API endpoint for recent activity
     */
    public function apiRecentActivity(Request $request, Response $response): Response
    {
        $limit = (int) ($request->getQueryParams()['limit'] ?? 10);
        $limit = min(50, max(1, $limit)); // Clamp between 1 and 50

        $recentItems = $this->getRecentItems($limit);

        return $this->json($response, [
            'success' => true,
            'data' => $recentItems,
        ]);
    }

    /**
     * Get recent items (simplified)
     */
    private function getRecentItems(int $limit = 10): array
    {
        $sql = "SELECT i.*, u.username as user_name, st.statusdesc as status_name
                FROM items i
                LEFT JOIN users u ON i.userid = u.id
                LEFT JOIN statustypes st ON i.status = st.id
                ORDER BY i.id DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Get recent software (simplified)
     */
    private function getRecentSoftware(int $limit = 5): array
    {
        $sql = "SELECT * FROM software ORDER BY id DESC LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Get user's assigned items
     */
    private function getUserItems(int $userId, int $limit = 10): array
    {
        $sql = "SELECT i.*, st.statusdesc as status_name
                FROM items i
                LEFT JOIN statustypes st ON i.status = st.id
                WHERE i.userid = :user_id
                ORDER BY i.id DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, ['user_id' => $userId, 'limit' => $limit]);
    }
}