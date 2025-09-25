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

    public function __construct(LoggerInterface $logger, Environment $twig, AuthService $authService)
    {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
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
        // For now, return default settings
        // In a real implementation, these would be loaded from database or config file
        return [
            'company_title' => $_ENV['APP_NAME'] ?? 'IT Database',
            'date_format' => 'dmy',
            'currency' => '€',
            'language' => 'en',
            'timezone' => 'UTC',
            'maintenance_mode' => false,
            'email_notifications' => true,
            'use_ldap' => filter_var($_ENV['LDAP_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'ldap_server' => $_ENV['LDAP_HOST'] ?? '',
            'ldap_dn' => $_ENV['LDAP_BASE_DN'] ?? '',
            'ldap_search_dn' => $_ENV['LDAP_BASE_DN'] ?? '',
            'ldap_user_filter' => '(& (uid=*) (IsActive=TRUE))',
        ];
    }

    /**
     * Save system settings
     */
    private function saveSystemSettings(array $settings): void
    {
        // For now, this is a placeholder
        // In a real implementation, settings would be saved to database or config file

        // Could save to a settings table in database:
        // foreach ($settings as $key => $value) {
        //     Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        // }

        // Or save to a config file, environment variables, etc.
        $this->logger->info('Settings would be saved', $settings);
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
}