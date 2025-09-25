<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContractTypeModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ContractTypeController extends BaseController
{
    private AuthService $authService;
    private ContractTypeModel $contractTypeModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        ContractTypeModel $contractTypeModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->contractTypeModel = $contractTypeModel;
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

        $result = $this->contractTypeModel->getPaginated(1, 100, $filters);
        $contractTypes = $result['data'];

        return $this->render($response, 'admin/contract-types/index.twig', [
            'contract_types' => $contractTypes,
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
        $contractType = $this->contractTypeModel->find($id);

        if (!$contractType) {
            $this->addFlashMessage('error', 'Contract type not found.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.index');
        }

        return $this->render($response, 'admin/contract-types/show.twig', [
            'contract_type' => $contractType,
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

        return $this->render($response, 'admin/contract-types/create.twig', [
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
            return $this->redirectToRoute($request, $response, 'admin.contract-types.create');
        }

        $data = $this->getParsedBody($request);

        if (empty($data['name'])) {
            $this->addFlashMessage('error', 'Contract type name is required.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.create');
        }

        try {
            $contractTypeId = $this->contractTypeModel->create([
                'name' => $this->sanitizeString($data['name']),
            ]);

            $this->logUserAction('contract_type_created', ['type_id' => $contractTypeId, 'name' => $data['name']]);
            $this->addFlashMessage('success', 'Contract type created successfully.');

            return $this->redirectToRoute($request, $response, 'admin.contract-types.show', ['id' => $contractTypeId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create contract type', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create contract type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.create');
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
        $contractType = $this->contractTypeModel->find($id);

        if (!$contractType) {
            $this->addFlashMessage('error', 'Contract type not found.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.index');
        }

        return $this->render($response, 'admin/contract-types/edit.twig', [
            'contract_type' => $contractType,
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
        $contractType = $this->contractTypeModel->find($id);

        if (!$contractType) {
            $this->addFlashMessage('error', 'Contract type not found.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        if (empty($data['name'])) {
            $this->addFlashMessage('error', 'Contract type name is required.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.edit', ['id' => $id]);
        }

        try {
            $this->contractTypeModel->update($id, [
                'name' => $this->sanitizeString($data['name']),
            ]);

            $this->logUserAction('contract_type_updated', ['type_id' => $id, 'name' => $data['name']]);
            $this->addFlashMessage('success', 'Contract type updated successfully.');

            return $this->redirectToRoute($request, $response, 'admin.contract-types.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update contract type', [
                'type_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update contract type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.edit', ['id' => $id]);
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
        $contractType = $this->contractTypeModel->find($id);

        if (!$contractType) {
            $this->addFlashMessage('error', 'Contract type not found.');
            return $this->redirectToRoute($request, $response, 'admin.contract-types.index');
        }

        try {
            $typeName = $contractType['name'];
            $this->contractTypeModel->delete($id);

            $this->logUserAction('contract_type_deleted', ['type_id' => $id, 'name' => $typeName]);
            $this->addFlashMessage('success', 'Contract type deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete contract type', [
                'type_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete contract type. It may be in use by existing contracts.');
        }

        return $this->redirectToRoute($request, $response, 'admin.contract-types.index');
    }
}