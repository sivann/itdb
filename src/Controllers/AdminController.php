<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class AdminController extends BaseController
{
    private AuthService $authService;
    private \PDO $pdo;

    public function __construct(LoggerInterface $logger, Environment $twig, AuthService $authService, \PDO $pdo)
    {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->pdo = $pdo;
    }

    /**
     * Admin dashboard/index page
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        // Redirect to types overview for now
        return $this->redirectToRoute($request, $response, 'admin.types');
    }

    /**
     * Admin types overview page
     */
    public function types(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        return $this->render($response, 'admin/types.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Import page
     */
    public function import(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        return $this->render($response, 'admin/import.twig', [
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Process import
     */
    public function processImport(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.import');
        }

        // TODO: Implement import functionality
        $this->addFlashMessage('info', 'Import functionality not yet implemented');
        return $this->redirectToRoute($request, $response, 'admin.import');
    }

    /**
     * Translations page
     */
    public function translations(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        return $this->render($response, 'admin/translations.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Settings page
     */
    public function settings(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        // Load current settings
        $settings = $this->getSystemSettings();

        return $this->render($response, 'admin/settings.twig', [
            'user' => $user,
            'settings' => $settings,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.settings');
        }

        $data = $this->getParsedBody($request);

        try {
            // Prepare settings data
            $settingsData = [
                'company_title' => $this->sanitizeString($data['company_title'] ?? 'IT Database'),
                'date_format' => in_array($data['date_format'] ?? 'dmy', ['dmy', 'mdy', 'ymd']) ? $data['date_format'] : 'dmy',
                'currency' => $this->sanitizeString($data['currency'] ?? '€'),
                'language' => in_array($data['language'] ?? 'en', ['en', 'de', 'es', 'it', 'cn']) ? $data['language'] : 'en',
                'timezone' => $this->sanitizeString($data['timezone'] ?? 'UTC'),
                'maintenance_mode' => isset($data['maintenance_mode']) ? 1 : 0,
                'email_notifications' => isset($data['email_notifications']) ? 1 : 0,
                'use_ldap' => isset($data['use_ldap']) ? 1 : 0,
                'ldap_server' => $this->sanitizeString($data['ldap_server'] ?? ''),
                'ldap_dn' => $this->sanitizeString($data['ldap_dn'] ?? ''),
                'ldap_search_dn' => $this->sanitizeString($data['ldap_search_dn'] ?? ''),
                'ldap_user_filter' => $this->sanitizeString($data['ldap_user_filter'] ?? '(& (uid=*) (IsActive=TRUE))'),
            ];

            // Save settings to database/config
            $this->saveSystemSettings($settingsData);

            $this->logUserAction('settings_updated', $settingsData);
            $this->addFlashMessage('success', 'Settings updated successfully');

        } catch (\Exception $e) {
            $this->logger->error('Error updating settings', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating settings');
        }

        return $this->redirectToRoute($request, $response, 'admin.settings');
    }

    /**
     * Get system settings
     */
    private function getSystemSettings(): array
    {
        // Read settings from database
        $sql = "SELECT companytitle, dateformat, currency, lang, timezone, useldap, ldap_server, ldap_dn FROM settings LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $dbSettings = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Map database column names to form field names
        return [
            'company_title' => $dbSettings['companytitle'] ?? 'IT Database',
            'date_format' => $dbSettings['dateformat'] ?? 'dmy',
            'currency' => $dbSettings['currency'] ?? '€',
            'language' => $dbSettings['lang'] ?? 'en',
            'timezone' => $dbSettings['timezone'] ?? 'UTC',
            'maintenance_mode' => false, // Not stored in DB yet
            'email_notifications' => true, // Not stored in DB yet
            'use_ldap' => (bool)($dbSettings['useldap'] ?? 0),
            'ldap_server' => $dbSettings['ldap_server'] ?? '',
            'ldap_dn' => $dbSettings['ldap_dn'] ?? '',
            'ldap_search_dn' => $dbSettings['ldap_dn'] ?? '',
            'ldap_user_filter' => '(& (uid=*) (IsActive=TRUE))',
        ];
    }

    /**
     * Save system settings
     */
    private function saveSystemSettings(array $settings): void
    {
        // Map form field names to database column names
        $sql = "UPDATE settings SET
                companytitle = ?,
                dateformat = ?,
                currency = ?,
                lang = ?,
                timezone = ?,
                useldap = ?,
                ldap_server = ?,
                ldap_dn = ?";

        $params = [
            $settings['company_title'] ?? 'IT Database',
            $settings['date_format'] ?? 'dmy',
            $settings['currency'] ?? '€',
            $settings['language'] ?? 'en',
            $settings['timezone'] ?? 'UTC',
            $settings['use_ldap'] ? 1 : 0,
            $settings['ldap_server'] ?? '',
            $settings['ldap_dn'] ?? ''
        ];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $this->logger->info('Settings saved to database', $settings);
    }

    /**
     * Backup/DB Log page
     */
    public function backup(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        return $this->render($response, 'admin/backup.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Tags management page
     */
    public function tags(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        // Get all tags
        $sql = "SELECT id, name, color FROM tags ORDER BY name ASC";
        $tags = $this->pdo->prepare($sql);
        $tags->execute();
        $tagsList = $tags->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render($response, 'admin/tags.twig', [
            'user' => $user,
            'tags' => $tagsList,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Create new tag
     */
    public function createTag(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.tags');
        }

        $data = $this->getParsedBody($request);

        if (empty($data['name'])) {
            $this->addFlashMessage('error', 'Tag name is required');
            return $this->redirectToRoute($request, $response, 'admin.tags');
        }

        try {
            $sql = "INSERT INTO tags (name, color) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->sanitizeString($data['name']),
                $this->sanitizeString($data['color'] ?? '#007bff')
            ]);

            $this->addFlashMessage('success', 'Tag created successfully');
            $this->logUserAction('tag_created', ['name' => $data['name']]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $this->addFlashMessage('error', 'Tag name already exists');
            } else {
                $this->logger->error('Error creating tag', ['error' => $e->getMessage()]);
                $this->addFlashMessage('error', 'Error creating tag');
            }
        }

        return $this->redirectToRoute($request, $response, 'admin.tags');
    }

    /**
     * Update tag
     */
    public function updateTag(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.tags');
        }

        $data = $this->getParsedBody($request);

        if (empty($data['name'])) {
            $this->addFlashMessage('error', 'Tag name is required');
            return $this->redirectToRoute($request, $response, 'admin.tags');
        }

        try {
            $sql = "UPDATE tags SET name = ?, color = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->sanitizeString($data['name']),
                $this->sanitizeString($data['color'] ?? '#007bff'),
                $id
            ]);

            $this->addFlashMessage('success', 'Tag updated successfully');
            $this->logUserAction('tag_updated', ['id' => $id, 'name' => $data['name']]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $this->addFlashMessage('error', 'Tag name already exists');
            } else {
                $this->logger->error('Error updating tag', ['error' => $e->getMessage()]);
                $this->addFlashMessage('error', 'Error updating tag');
            }
        }

        return $this->redirectToRoute($request, $response, 'admin.tags');
    }

    /**
     * Delete tag
     */
    public function deleteTag(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.tags');
        }

        try {
            // Get tag name for logging
            $sql = "SELECT name FROM tags WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $tagName = $stmt->fetchColumn();

            if (!$tagName) {
                $this->addFlashMessage('error', 'Tag not found');
                return $this->redirectToRoute($request, $response, 'admin.tags');
            }

            // Delete tag associations first
            $sql = "DELETE FROM tag2software WHERE tagid = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            // Delete the tag
            $sql = "DELETE FROM tags WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            $this->addFlashMessage('success', 'Tag deleted successfully');
            $this->logUserAction('tag_deleted', ['id' => $id, 'name' => $tagName]);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting tag', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting tag');
        }

        return $this->redirectToRoute($request, $response, 'admin.tags');
    }
}