<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\UserModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class AuditLogController extends BaseController
{
    private AuthService $authService;
    private AuditLogModel $auditLogModel;
    private UserModel $userModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        AuditLogModel $auditLogModel,
        UserModel $userModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->auditLogModel = $auditLogModel;
        $this->userModel = $userModel;
    }

    /**
     * Display audit log listing
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $queryParams = $this->getQueryParams($request);

        // Build filters
        $filters = [];
        if (!empty($queryParams['asset_type'])) {
            $filters['asset_type'] = $queryParams['asset_type'];
        }
        if (!empty($queryParams['user_id'])) {
            $filters['user_id'] = (int) $queryParams['user_id'];
        }
        if (!empty($queryParams['action'])) {
            $filters['action'] = $queryParams['action'];
        }
        if (!empty($queryParams['date_from'])) {
            $filters['date_from'] = $queryParams['date_from'];
        }
        if (!empty($queryParams['date_to'])) {
            $filters['date_to'] = $queryParams['date_to'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($queryParams['per_page'] ?? 50)));

        // Get audit logs
        $result = $this->auditLogModel->getPaginated($page, $perPage, $filters);

        // Get filter options
        $assetTypes = $this->auditLogModel->getAssetTypes();
        $actions = $this->auditLogModel->getActions();
        $users = $this->userModel->getAll();

        return $this->render($response, 'admin/audit-log/index.twig', [
            'audit_logs' => $result['data'],
            'pagination' => [
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'last_page' => $result['total_pages'],
                'from' => ($result['page'] - 1) * $result['per_page'] + 1,
                'to' => min($result['page'] * $result['per_page'], $result['total'])
            ],
            'filters' => [
                'asset_types' => $assetTypes,
                'actions' => $actions,
                'users' => $users
            ],
            'current_filters' => $filters,
            'query' => $queryParams,
            'user' => $user,
        ]);
    }

    /**
     * Clean old audit logs
     */
    public function clean(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.audit-log.index');
        }

        $data = $this->getParsedBody($request);
        $daysToKeep = max(30, min(3650, (int) ($data['days_to_keep'] ?? 365)));

        try {
            $deletedCount = $this->auditLogModel->cleanOldLogs($daysToKeep);

            $this->logUserAction('audit_log_cleaned', [
                'days_to_keep' => $daysToKeep,
                'deleted_count' => $deletedCount
            ]);

            $this->addFlashMessage('success', "Successfully cleaned {$deletedCount} old audit log entries.");

        } catch (\Exception $e) {
            $this->logger->error('Failed to clean audit logs', [
                'error' => $e->getMessage(),
                'days_to_keep' => $daysToKeep
            ]);
            $this->addFlashMessage('error', 'Failed to clean audit logs. Please try again.');
        }

        return $this->redirectToRoute($request, $response, 'admin.audit-log.index');
    }

    /**
     * Export audit logs to CSV
     */
    public function export(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $queryParams = $this->getQueryParams($request);

        // Build filters (same as index)
        $filters = [];
        if (!empty($queryParams['asset_type'])) {
            $filters['asset_type'] = $queryParams['asset_type'];
        }
        if (!empty($queryParams['user_id'])) {
            $filters['user_id'] = (int) $queryParams['user_id'];
        }
        if (!empty($queryParams['action'])) {
            $filters['action'] = $queryParams['action'];
        }
        if (!empty($queryParams['date_from'])) {
            $filters['date_from'] = $queryParams['date_from'];
        }
        if (!empty($queryParams['date_to'])) {
            $filters['date_to'] = $queryParams['date_to'];
        }

        // Get all matching logs (with reasonable limit)
        $result = $this->auditLogModel->getPaginated(1, 10000, $filters);
        $logs = $result['data'];

        // Create CSV content
        $csvData = [];
        $csvData[] = ['ID', 'Timestamp', 'User', 'Asset Type', 'Asset ID', 'Action', 'Details', 'IP Address'];

        foreach ($logs as $log) {
            $csvData[] = [
                $log['id'],
                $log['formatted_timestamp'],
                $log['user_display'],
                $log['asset_type'],
                $log['asset_id'],
                $log['action'],
                json_encode($log['details_array']),
                $log['ip_address'] ?? ''
            ];
        }

        // Generate CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Log export action
        $this->logUserAction('audit_log_exported', [
            'filter_count' => count($logs),
            'filters' => $filters
        ]);

        $filename = 'audit_log_' . date('Y-m-d_H-i-s') . '.csv';

        $response->getBody()->write($csvContent);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string) strlen($csvContent));
    }
}