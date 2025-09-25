<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AgentModel;
use App\Models\AgentTypeModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use PDOException;

class AgentController extends BaseController
{
    private AuthService $authService;
    private AgentModel $agentModel;
    private AgentTypeModel $agentTypeModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        AgentModel $agentModel,
        AgentTypeModel $agentTypeModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->agentModel = $agentModel;
        $this->agentTypeModel = $agentTypeModel;
    }

    /**
     * List all agents
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);

        // Build filters
        $filters = [];
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }
        if (!empty($queryParams['type'])) {
            $filters['type'] = $queryParams['type'];
        }

        // Get paginated agents
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = 20;

        $result = $this->agentModel->getPaginated($page, $perPage, $filters);

        return $this->render($response, 'agents/index.twig', [
            'user' => $user,
            'agents' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'search' => $queryParams['search'] ?? '',
            'type_filter' => $queryParams['type'] ?? '',
        ]);
    }

    /**
     * Show agent details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $agent = $this->agentModel->findWithTypes($id);
        if (!$agent) {
            $this->addFlashMessage('error', 'Agent not found');
            return $this->redirectToRoute($request, $response, 'agents.index');
        }

        return $this->render($response, 'agents/edit.twig', [
            'mode' => 'view',
            'user' => $user,
            'agent' => $agent,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get available agent types
        $availableAgentTypes = $this->agentTypeModel->getActive();

        return $this->render($response, 'agents/edit.twig', [
            'mode' => 'create',
            'user' => $user,
            'available_agent_types' => $availableAgentTypes,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new agent
     */
    public function store(Request $request, Response $response): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'agents.create');
        }

        $data = $this->getParsedBody($request);

        // Validation
        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $agentId = $this->agentModel->create([
                'type' => !empty($data['type']) ? (int) $data['type'] : null,
                'title' => $this->sanitizeString($data['title'] ?? ''),
                'contactinfo' => $this->sanitizeString($data['contactinfo'] ?? ''),
                'contacts' => $this->sanitizeString($data['contacts'] ?? ''),
                'urls' => $this->sanitizeString($data['urls'] ?? ''),
            ]);

            // Handle agent types
            if (!empty($data['agent_types']) && is_array($data['agent_types'])) {
                $typeIds = array_map('intval', $data['agent_types']);
                $this->agentModel->setAgentTypes($agentId, $typeIds);
            }

            $this->logUserAction('agent_created', ['agent_id' => $agentId]);
            $this->addFlashMessage('success', 'Agent created successfully');
            return $this->redirectToRoute($request, $response, 'agents.edit', ['id' => $agentId]);

        } catch (PDOException $e) {
            $this->logger->error('Database error creating agent', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', $e->getMessage());
            return $this->redirectToRoute($request, $response, 'agents.create');
        } catch (\Exception $e) {
            $this->logger->error('Error creating agent', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error creating agent: ' . $e->getMessage());
            return $this->redirectToRoute($request, $response, 'agents.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $agent = $this->agentModel->findWithTypes($id);
        if (!$agent) {
            $this->addFlashMessage('error', 'Agent not found');
            return $this->redirectToRoute($request, $response, 'agents.index');
        }

        // Get agent's current types
        $agentTypes = $this->agentModel->getAgentTypes($id);

        // Get available agent types
        $availableAgentTypes = $this->agentTypeModel->getActive();

        return $this->render($response, 'agents/edit.twig', [
            'mode' => 'edit',
            'user' => $user,
            'agent' => $agent,
            'agent_types' => $agentTypes,
            'available_agent_types' => $availableAgentTypes,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update agent
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'agents.index');
        }

        $id = (int) $args['id'];
        $data = $this->getParsedBody($request);

        $agent = $this->agentModel->find($id);
        if (!$agent) {
            $this->addFlashMessage('error', 'Agent not found');
            return $this->redirectToRoute($request, $response, 'agents.index');
        }

        // Validation
        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $this->agentModel->update($id, [
                'type' => !empty($data['type']) ? (int) $data['type'] : null,
                'title' => $this->sanitizeString($data['title'] ?? ''),
                'contactinfo' => $this->sanitizeString($data['contactinfo'] ?? ''),
                'contacts' => $this->sanitizeString($data['contacts'] ?? ''),
                'urls' => $this->sanitizeString($data['urls'] ?? ''),
            ]);

            // Handle agent types
            if (isset($data['agent_types']) && is_array($data['agent_types'])) {
                $typeIds = array_map('intval', $data['agent_types']);
                $this->agentModel->setAgentTypes($id, $typeIds);
            } else {
                // If no agent types selected, clear all
                $this->agentModel->setAgentTypes($id, []);
            }

            $this->logUserAction('agent_updated', ['agent_id' => $id]);
            $this->addFlashMessage('success', 'Agent updated successfully');
            return $this->redirectToRoute($request, $response, 'agents.show', ['id' => $id]);

        } catch (PDOException $e) {
            $this->logger->error('Database error updating agent', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', $e->getMessage());
            return $this->redirectToRoute($request, $response, 'agents.edit', ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Error updating agent', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating agent: ' . $e->getMessage());
            return $this->redirectToRoute($request, $response, 'agents.edit', ['id' => $id]);
        }
    }

    /**
     * Delete agent
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'agents.index');
        }

        $id = (int) $args['id'];

        $agent = $this->agentModel->find($id);
        if (!$agent) {
            $this->addFlashMessage('error', 'Agent not found');
            return $this->redirectToRoute($request, $response, 'agents.index');
        }

        // Check if agent can be deleted
        $canDelete = $this->agentModel->canDelete($id);
        if (!$canDelete['can_delete']) {
            $message = 'Cannot delete this agent because it is referenced by: ' . implode(', ', $canDelete['references']);
            $this->addFlashMessage('error', $message);
            return $this->redirectToRoute($request, $response, 'agents.show', ['id' => $id]);
        }

        try {
            $this->logUserAction('agent_deleted', ['agent_id' => $agent['id'], 'title' => $agent['title']]);
            $this->agentModel->delete($id);

            $this->addFlashMessage('success', 'Agent deleted successfully');
            return $this->redirectToRoute($request, $response, 'agents.index');

        } catch (PDOException $e) {
            $this->logger->error('Database error deleting agent', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', $e->getMessage());
            return $this->redirectToRoute($request, $response, 'agents.show', ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting agent', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting agent: ' . $e->getMessage());
            return $this->redirectToRoute($request, $response, 'agents.show', ['id' => $id]);
        }
    }
}