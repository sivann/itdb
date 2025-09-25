<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\StatusTypeModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class StatusTypeController extends BaseController
{
    private AuthService $authService;
    private StatusTypeModel $statusTypeModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        StatusTypeModel $statusTypeModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->statusTypeModel = $statusTypeModel;
    }

    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        $queryParams = $this->getQueryParams($request);
        $search = $queryParams['search'] ?? '';

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $result = $this->statusTypeModel->getPaginated(1, 100, $filters);
        $statusTypes = $result['data'];

        return $this->render($response, 'admin/status-types/index.twig', [
            'status_types' => $statusTypes,
            'search' => $search,
            'user' => $user,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        $id = (int) $args['id'];
        $statusType = $this->statusTypeModel->find($id);

        if (!$statusType) {
            $this->addFlashMessage('error', 'Status type not found.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.index');
        }

        return $this->render($response, 'admin/status-types/show.twig', [
            'status_type' => $statusType,
            'user' => $user,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        return $this->render($response, 'admin/status-types/create.twig', [
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.create');
        }

        $data = $this->getParsedBody($request);

        if (empty($data['statusdesc'])) {
            $this->addFlashMessage('error', 'Status description is required.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.create');
        }

        try {
            $statusTypeId = $this->statusTypeModel->create([
                'statusdesc' => $this->sanitizeString($data['statusdesc']),
            ]);

            $this->logUserAction('status_type_created', ['type_id' => $statusTypeId, 'name' => $data['statusdesc']]);
            $this->addFlashMessage('success', 'Status type created successfully.');

            return $this->redirectToRoute($request, $response, 'admin.status-types.show', ['id' => $statusTypeId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create status type', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create status type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.create');
        }
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        $id = (int) $args['id'];
        $statusType = $this->statusTypeModel->find($id);

        if (!$statusType) {
            $this->addFlashMessage('error', 'Status type not found.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.index');
        }

        return $this->render($response, 'admin/status-types/edit.twig', [
            'status_type' => $statusType,
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        $id = (int) $args['id'];
        $statusType = $this->statusTypeModel->find($id);

        if (!$statusType) {
            $this->addFlashMessage('error', 'Status type not found.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        if (empty($data['statusdesc'])) {
            $this->addFlashMessage('error', 'Status description is required.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.edit', ['id' => $id]);
        }

        try {
            $this->statusTypeModel->update($id, [
                'statusdesc' => $this->sanitizeString($data['statusdesc']),
            ]);

            $this->logUserAction('status_type_updated', ['type_id' => $id, 'name' => $data['statusdesc']]);
            $this->addFlashMessage('success', 'Status type updated successfully.');

            return $this->redirectToRoute($request, $response, 'admin.status-types.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update status type', [
                'type_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update status type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.edit', ['id' => $id]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        $id = (int) $args['id'];
        $statusType = $this->statusTypeModel->find($id);

        if (!$statusType) {
            $this->addFlashMessage('error', 'Status type not found.');
            return $this->redirectToRoute($request, $response, 'admin.status-types.index');
        }

        try {
            $typeName = $statusType['statusdesc'];
            $this->statusTypeModel->delete($id);

            $this->logUserAction('status_type_deleted', ['type_id' => $id, 'name' => $typeName]);
            $this->addFlashMessage('success', 'Status type deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete status type', [
                'type_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete status type. It may be in use by existing items.');
        }

        return $this->redirectToRoute($request, $response, 'admin.status-types.index');
    }
}