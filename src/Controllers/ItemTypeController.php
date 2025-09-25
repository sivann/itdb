<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ItemTypeModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ItemTypeController extends BaseController
{
    private AuthService $authService;
    private ItemTypeModel $itemTypeModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        ItemTypeModel $itemTypeModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->itemTypeModel = $itemTypeModel;
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

        $result = $this->itemTypeModel->getPaginated(1, 100, $filters);
        $itemTypes = $result['data'];

        return $this->render($response, 'admin/item-types/index.twig', [
            'item_types' => $itemTypes,
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
        $itemType = $this->itemTypeModel->find($id);

        if (!$itemType) {
            $this->addFlashMessage('error', 'Item type not found.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.index');
        }

        return $this->render($response, 'admin/item-types/show.twig', [
            'item_type' => $itemType,
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

        return $this->render($response, 'admin/item-types/create.twig', [
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
            return $this->redirectToRoute($request, $response, 'admin.item-types.create');
        }

        $data = $this->getParsedBody($request);

        if (empty($data['typedesc'])) {
            $this->addFlashMessage('error', 'Type description is required.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.create');
        }

        try {
            $itemTypeId = $this->itemTypeModel->create([
                'name' => $this->sanitizeString($data['typedesc']),
                'typedesc' => $this->sanitizeString($data['typedesc']),
                'hassoftware' => (int) ($data['hassoftware'] ?? 0),
            ]);

            $this->logUserAction('item_type_created', ['type_id' => $itemTypeId, 'name' => $data['typedesc']]);
            $this->addFlashMessage('success', 'Item type created successfully.');

            return $this->redirectToRoute($request, $response, 'admin.item-types.show', ['id' => $itemTypeId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create item type', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create item type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.create');
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
        $itemType = $this->itemTypeModel->find($id);

        if (!$itemType) {
            $this->addFlashMessage('error', 'Item type not found.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.index');
        }

        return $this->render($response, 'admin/item-types/edit.twig', [
            'item_type' => $itemType,
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
        $itemType = $this->itemTypeModel->find($id);

        if (!$itemType) {
            $this->addFlashMessage('error', 'Item type not found.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        if (empty($data['typedesc'])) {
            $this->addFlashMessage('error', 'Type description is required.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.edit', ['id' => $id]);
        }

        try {
            $this->itemTypeModel->update($id, [
                'name' => $this->sanitizeString($data['typedesc']),
                'typedesc' => $this->sanitizeString($data['typedesc']),
                'hassoftware' => (int) ($data['hassoftware'] ?? 0),
            ]);

            $this->logUserAction('item_type_updated', ['type_id' => $id, 'name' => $data['typedesc']]);
            $this->addFlashMessage('success', 'Item type updated successfully.');

            return $this->redirectToRoute($request, $response, 'admin.item-types.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update item type', [
                'type_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update item type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.edit', ['id' => $id]);
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
        $itemType = $this->itemTypeModel->find($id);

        if (!$itemType) {
            $this->addFlashMessage('error', 'Item type not found.');
            return $this->redirectToRoute($request, $response, 'admin.item-types.index');
        }

        try {
            $typeName = $itemType['typedesc'] ?? $itemType['name'];
            $this->itemTypeModel->delete($id);

            $this->logUserAction('item_type_deleted', ['type_id' => $id, 'name' => $typeName]);
            $this->addFlashMessage('success', 'Item type deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete item type', [
                'type_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete item type. It may be in use by existing items.');
        }

        return $this->redirectToRoute($request, $response, 'admin.item-types.index');
    }
}