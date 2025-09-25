<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AgentTypeModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class AgentTypeController extends BaseController
{
    private AuthService $authService;
    private AgentTypeModel $agentTypeModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        AgentTypeModel $agentTypeModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->agentTypeModel = $agentTypeModel;
    }

    /**
     * List all agent types
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $agentTypes = $this->agentTypeModel->getAll();

        return $this->render($response, 'admin/agent-types/index.twig', [
            'user' => $user,
            'agent_types' => $agentTypes,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        return $this->render($response, 'admin/agent-types/edit.twig', [
            'mode' => 'create',
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new agent type
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.create');
        }

        $data = $this->getParsedBody($request);

        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }
        if (empty($data['code'])) {
            $errors[] = 'Code is required';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            // Check for duplicate code
            if ($this->agentTypeModel->codeExists($data['code'])) {
                $this->addFlashMessage('error', 'Code already exists');
                return $this->redirectToRoute($request, $response, 'admin.agent-types.create');
            }

            $agentTypeId = $this->agentTypeModel->create([
                'name' => $this->sanitizeString($data['name']),
                'code' => $this->sanitizeString($data['code']),
                'description' => $this->sanitizeString($data['description'] ?? ''),
                'active' => !empty($data['is_active']) ? 1 : 0,
                'sort_order' => !empty($data['display_order']) ? (int) $data['display_order'] : 0,
            ]);

            $this->logUserAction('agent_type_created', ['agent_type_id' => $agentTypeId]);
            $this->addFlashMessage('success', 'Agent type created successfully');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.edit', ['id' => $agentTypeId]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating agent type', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error creating agent type');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $id = (int) $args['id'];
        $agentType = $this->agentTypeModel->find($id);

        if (!$agentType) {
            $this->addFlashMessage('error', 'Agent type not found');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.index');
        }

        return $this->render($response, 'admin/agent-types/edit.twig', [
            'mode' => 'edit',
            'user' => $user,
            'agent_type' => $agentType,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update agent type
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.index');
        }

        $id = (int) $args['id'];
        $data = $this->getParsedBody($request);

        $agentType = $this->agentTypeModel->find($id);
        if (!$agentType) {
            $this->addFlashMessage('error', 'Agent type not found');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.index');
        }

        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }
        if (empty($data['code'])) {
            $errors[] = 'Code is required';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            // Check for duplicate code (excluding current record)
            if ($this->agentTypeModel->codeExists($data['code'], $id)) {
                $this->addFlashMessage('error', 'Code already exists');
                return $this->redirectToRoute($request, $response, 'admin.agent-types.edit', ['id' => $id]);
            }

            $this->agentTypeModel->update($id, [
                'name' => $this->sanitizeString($data['name']),
                'code' => $this->sanitizeString($data['code']),
                'description' => $this->sanitizeString($data['description'] ?? ''),
                'active' => !empty($data['is_active']) ? 1 : 0,
                'sort_order' => !empty($data['display_order']) ? (int) $data['display_order'] : 0,
            ]);

            $this->logUserAction('agent_type_updated', ['agent_type_id' => $id]);
            $this->addFlashMessage('success', 'Agent type updated successfully');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.edit', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating agent type', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating agent type');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.edit', ['id' => $id]);
        }
    }

    /**
     * Delete agent type
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.index');
        }

        $id = (int) $args['id'];

        $agentType = $this->agentTypeModel->findWithCounts($id);
        if (!$agentType) {
            $this->addFlashMessage('error', 'Agent type not found');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.index');
        }

        // Check if agent type is in use
        if ($agentType->agents->count() > 0) {
            $this->addFlashMessage('error', 'Cannot delete agent type that is assigned to agents. Reassign agents first.');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.edit', ['id' => $id]);
        }

        try {
            $this->logUserAction('agent_type_deleted', ['agent_type_id' => $id, 'name' => $agentType['name']]);
            $this->agentTypeModel->delete($id);

            $this->addFlashMessage('success', 'Agent type deleted successfully');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.index');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting agent type', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting agent type');
            return $this->redirectToRoute($request, $response, 'admin.agent-types.edit', ['id' => $id]);
        }
    }

    /**
     * Show agent type (redirects to edit)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $queryParams = $this->getQueryParams($request);

        // Preserve query parameters when redirecting
        $queryString = http_build_query($queryParams);
        $redirectUrl = '/admin/agent-types/' . $id . '/edit' . ($queryString ? '?' . $queryString : '');

        return $response->withStatus(302)->withHeader('Location', $redirectUrl);
    }
}