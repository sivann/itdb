<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LicenseTypeModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class LicenseTypeController extends BaseController
{
    private AuthService $authService;
    private LicenseTypeModel $licenseTypeModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        LicenseTypeModel $licenseTypeModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->licenseTypeModel = $licenseTypeModel;
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $queryParams = $this->getQueryParams($request);
        $search = $queryParams['search'] ?? '';

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $result = $this->licenseTypeModel->getPaginated(1, 100, $filters);
        $licenseTypes = $result['data'];

        return $this->render($response, 'admin/license-types/index.twig', [
            'license_types' => $licenseTypes,
            'search' => $search,
            'user' => $user,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $id = (int) $args['id'];
        $licenseType = $this->licenseTypeModel->find($id);

        if (!$licenseType) {
            $this->addFlashMessage('error', 'License type not found.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.index');
        }

        return $this->render($response, 'admin/license-types/show.twig', [
            'license_type' => $licenseType,
            'user' => $user,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        return $this->render($response, 'admin/license-types/create.twig', [
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.create');
        }

        $data = $this->getParsedBody($request);

        if (empty($data['name'])) {
            $this->addFlashMessage('error', 'License type name is required.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.create');
        }

        try {
            $licenseTypeId = $this->licenseTypeModel->create([
                'name' => $this->sanitizeString($data['name']),
                'description' => $this->sanitizeString($data['description'] ?? ''),
            ]);

            $this->logUserAction('license_type_created', ['type_id' => $licenseTypeId, 'name' => $data['name']]);
            $this->addFlashMessage('success', 'License type created successfully.');

            return $this->redirectToRoute($request, $response, 'admin.license-types.show', ['id' => $licenseTypeId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create license type', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create license type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.create');
        }
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $id = (int) $args['id'];
        $licenseType = $this->licenseTypeModel->find($id);

        if (!$licenseType) {
            $this->addFlashMessage('error', 'License type not found.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.index');
        }

        return $this->render($response, 'admin/license-types/edit.twig', [
            'license_type' => $licenseType,
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $id = (int) $args['id'];
        $licenseType = $this->licenseTypeModel->find($id);

        if (!$licenseType) {
            $this->addFlashMessage('error', 'License type not found.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        if (empty($data['name'])) {
            $this->addFlashMessage('error', 'License type name is required.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.edit', ['id' => $id]);
        }

        try {
            $this->licenseTypeModel->update($id, [
                'name' => $this->sanitizeString($data['name']),
                'description' => $this->sanitizeString($data['description'] ?? ''),
            ]);

            $this->logUserAction('license_type_updated', ['type_id' => $id, 'name' => $data['name']]);
            $this->addFlashMessage('success', 'License type updated successfully.');

            return $this->redirectToRoute($request, $response, 'admin.license-types.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update license type', [
                'type_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update license type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.edit', ['id' => $id]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $id = (int) $args['id'];
        $licenseType = $this->licenseTypeModel->find($id);

        if (!$licenseType) {
            $this->addFlashMessage('error', 'License type not found.');
            return $this->redirectToRoute($request, $response, 'admin.license-types.index');
        }

        try {
            $typeName = $licenseType['name'];
            $this->licenseTypeModel->delete($id);

            $this->logUserAction('license_type_deleted', ['type_id' => $id, 'name' => $typeName]);
            $this->addFlashMessage('success', 'License type deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete license type', [
                'type_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete license type. It may be in use by existing software.');
        }

        return $this->redirectToRoute($request, $response, 'admin.license-types.index');
    }
}