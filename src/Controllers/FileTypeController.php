<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\FileTypeModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class FileTypeController extends BaseController
{
    private AuthService $authService;
    private FileTypeModel $fileTypeModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        FileTypeModel $fileTypeModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->fileTypeModel = $fileTypeModel;
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

        $result = $this->fileTypeModel->getPaginated(1, 100, $filters);
        $fileTypes = $result['data'];

        return $this->render($response, 'admin/file-types/index.twig', [
            'file_types' => $fileTypes,
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
        $fileType = $this->fileTypeModel->find($id);

        if (!$fileType) {
            $this->addFlashMessage('error', 'File type not found.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.index');
        }

        return $this->render($response, 'admin/file-types/show.twig', [
            'file_type' => $fileType,
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

        return $this->render($response, 'admin/file-types/create.twig', [
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
            return $this->redirectToRoute($request, $response, 'admin.file-types.create');
        }

        $data = $this->getParsedBody($request);

        if (empty($data['typedesc'])) {
            $this->addFlashMessage('error', 'File type description is required.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.create');
        }

        try {
            $fileTypeId = $this->fileTypeModel->create([
                'typedesc' => $this->sanitizeString($data['typedesc']),
            ]);

            $this->logUserAction('file_type_created', ['type_id' => $fileTypeId, 'name' => $data['typedesc']]);
            $this->addFlashMessage('success', 'File type created successfully.');

            return $this->redirectToRoute($request, $response, 'admin.file-types.show', ['id' => $fileTypeId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create file type', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create file type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.create');
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
        $fileType = $this->fileTypeModel->find($id);

        if (!$fileType) {
            $this->addFlashMessage('error', 'File type not found.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.index');
        }

        return $this->render($response, 'admin/file-types/edit.twig', [
            'file_type' => $fileType,
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
        $fileType = $this->fileTypeModel->find($id);

        if (!$fileType) {
            $this->addFlashMessage('error', 'File type not found.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        if (empty($data['typedesc'])) {
            $this->addFlashMessage('error', 'File type description is required.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.edit', ['id' => $id]);
        }

        try {
            $this->fileTypeModel->update($id, [
                'typedesc' => $this->sanitizeString($data['typedesc']),
            ]);

            $this->logUserAction('file_type_updated', ['type_id' => $id, 'name' => $data['typedesc']]);
            $this->addFlashMessage('success', 'File type updated successfully.');

            return $this->redirectToRoute($request, $response, 'admin.file-types.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update file type', [
                'type_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update file type. Please try again.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.edit', ['id' => $id]);
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
        $fileType = $this->fileTypeModel->find($id);

        if (!$fileType) {
            $this->addFlashMessage('error', 'File type not found.');
            return $this->redirectToRoute($request, $response, 'admin.file-types.index');
        }

        try {
            $typeName = $fileType['typedesc'];
            $this->fileTypeModel->delete($id);

            $this->logUserAction('file_type_deleted', ['type_id' => $id, 'name' => $typeName]);
            $this->addFlashMessage('success', 'File type deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete file type', [
                'type_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete file type. It may be in use by existing files.');
        }

        return $this->redirectToRoute($request, $response, 'admin.file-types.index');
    }
}