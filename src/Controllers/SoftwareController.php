<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SoftwareModel;
use App\Models\AgentModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class SoftwareController extends BaseController
{
    private AuthService $authService;
    private SoftwareModel $softwareModel;
    private AgentModel $agentModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        SoftwareModel $softwareModel,
        AgentModel $agentModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->softwareModel = $softwareModel;
        $this->agentModel = $agentModel;
    }

    /**
     * List all software
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);

        // Build filters for SoftwareModel
        $filters = [];
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($queryParams['per_page'] ?? 25)));

        // Get paginated software
        $result = $this->softwareModel->getPaginated($page, $perPage, $filters);

        return $this->render($response, 'software/index.twig', [
            'software' => $result['data'],
            'pagination' => [
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'last_page' => $result['total_pages'],
                'from' => ($result['page'] - 1) * $result['per_page'] + 1,
                'to' => min($result['page'] * $result['per_page'], $result['total'])
            ],
            'query' => $queryParams,
            'user' => $user,
        ]);
    }

    /**
     * Show software details (redirects to edit)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $queryParams = $this->getQueryParams($request);

        // Preserve query parameters when redirecting
        $queryString = http_build_query($queryParams);
        $redirectUrl = '/software/' . $id . '/edit' . ($queryString ? '?' . $queryString : '');

        return $response->withStatus(302)->withHeader('Location', $redirectUrl);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);
        $tab = $queryParams['tab'] ?? 'data';

        // Check if user can create software
        if (!$user || !$this->canUserCreateSoftware($user)) {
            $this->addFlashMessage('error', 'You do not have permission to create software.');
            return $this->redirectToRoute($request, $response, 'software.index');
        }

        // Load data for associations (only for non-data tabs)
        $associationData = [];
        if ($tab !== 'data') {
            // For now, skip associations in create form to avoid errors
            // This will be handled in the associations fixes
        }

        // Get software manufacturers
        $manufacturers = $this->agentModel->getSoftwareManufacturers();

        return $this->render($response, 'software/create.twig', [
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
            'current_tab' => $tab,
            'manufacturers' => $manufacturers,
        ] + $associationData);
    }

    /**
     * Store new software
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$this->canUserCreateSoftware($user)) {
            $this->addFlashMessage('error', 'You do not have permission to create software.');
            return $this->redirectToRoute($request, $response, 'software.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'software.create');
        }

        $data = $this->getParsedBody($request);

        // Basic validation
        if (empty($data['stitle'])) {
            $this->addFlashMessage('error', 'Software title is required.');
            return $this->redirectToRoute($request, $response, 'software.create');
        }

        try {
            // Prepare data for creation
            $softwareData = [
                'stitle' => $this->sanitizeString($data['stitle']),
                'sversion' => $this->sanitizeString($data['sversion'] ?? ''),
                'sinfo' => $this->sanitizeString($data['sinfo'] ?? ''),
                'slicenseinfo' => $this->sanitizeString($data['slicenseinfo'] ?? ''),
                'licqty' => !empty($data['licqty']) ? (int) $data['licqty'] : 1,
                'lictype' => !empty($data['lictype']) ? (int) $data['lictype'] : 0,
                'stype' => $this->sanitizeString($data['stype'] ?? ''),
                'manufacturerid' => !empty($data['manufacturerid']) ? (int) $data['manufacturerid'] : null,
                'invoiceid' => !empty($data['invoiceid']) ? (int) $data['invoiceid'] : null,
                'purchdate' => !empty($data['purchdate']) ? strtotime($data['purchdate']) : null,
            ];


            $softwareId = $this->softwareModel->create($softwareData);

            // TODO: Handle associations with prepared statements
            // Will be implemented when fixing association issues

            $this->logUserAction('software_created', ['software_id' => $softwareId, 'title' => $data['stitle']]);
            $this->addFlashMessage('success', 'Software created successfully.');

            return $this->redirectToRoute($request, $response, 'software.edit', ['id' => $softwareId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create software', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create software. Please try again.');
            return $this->redirectToRoute($request, $response, 'software.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);
        $tab = $queryParams['tab'] ?? 'data';

        $software = $this->softwareModel->find($id);
        if (!$software) {
            $this->addFlashMessage('error', 'Software not found.');
            return $this->redirectToRoute($request, $response, 'software.index');
        }

        if (!$this->canUserEditSoftware($user, $software)) {
            $this->addFlashMessage('error', 'You do not have permission to edit this software.');
            return $this->redirectToRoute($request, $response, 'software.index');
        }

        // Get software manufacturers
        $manufacturers = $this->agentModel->getSoftwareManufacturers();

        // Load association data for active tab and provide actual data for template
        $associationData = [];

        // Always load the actual association data for template display
        $software['items'] = $this->softwareModel->getAssociatedItems($id);
        $software['invoices'] = $this->softwareModel->getAssociatedInvoices($id);
        $software['contracts'] = $this->softwareModel->getAssociatedContracts($id);
        $software['files'] = $this->softwareModel->getAssociatedFiles($id);

        // Load additional data based on current tab for search functionality
        if ($tab === 'items') {
            $associationData['available_items'] = $this->softwareModel->getAvailableItems($id);
        } elseif ($tab === 'invoices') {
            // Could add available invoices search here
        } elseif ($tab === 'contracts') {
            // Could add available contracts search here
        } elseif ($tab === 'files') {
            // Could add available files search here
        }

        return $this->render($response, 'software/edit.twig', [
            'software' => $software,
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
            'current_tab' => $tab,
            'manufacturers' => $manufacturers,
        ] + $associationData);
    }

    /**
     * Update software
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $software = $this->softwareModel->find($id);
        if (!$software) {
            $this->addFlashMessage('error', 'Software not found.');
            return $this->redirectToRoute($request, $response, 'software.index');
        }

        if (!$this->canUserEditSoftware($user, $software)) {
            $this->addFlashMessage('error', 'You do not have permission to edit this software.');
            return $this->redirectToRoute($request, $response, 'software.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'software.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        try {
            // Prepare data for update
            $updateData = [
                'stitle' => $this->sanitizeString($data['stitle']),
                'sversion' => $this->sanitizeString($data['sversion'] ?? ''),
                'sinfo' => $this->sanitizeString($data['sinfo'] ?? ''),
                'slicenseinfo' => $this->sanitizeString($data['slicenseinfo'] ?? ''),
                'licqty' => !empty($data['licqty']) ? (int) $data['licqty'] : 1,
                'lictype' => !empty($data['lictype']) ? (int) $data['lictype'] : 0,
                'stype' => $this->sanitizeString($data['stype'] ?? ''),
                'manufacturerid' => !empty($data['manufacturerid']) ? (int) $data['manufacturerid'] : null,
                'invoiceid' => !empty($data['invoiceid']) ? (int) $data['invoiceid'] : null,
                'purchdate' => !empty($data['purchdate']) ? strtotime($data['purchdate']) : null,
            ];


            $this->softwareModel->update($id, $updateData);

            $this->logUserAction('software_updated', ['software_id' => $id, 'title' => $data['stitle']]);
            $this->addFlashMessage('success', 'Software updated successfully.');

            return $this->redirectToRoute($request, $response, 'software.edit', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update software', [
                'software_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update software. Please try again.');
            return $this->redirectToRoute($request, $response, 'software.edit', ['id' => $id]);
        }
    }

    /**
     * Delete software
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $software = $this->softwareModel->find($id);
        if (!$software) {
            $this->addFlashMessage('error', 'Software not found.');
            return $this->redirectToRoute($request, $response, 'software.index');
        }

        if (!$this->canUserDeleteSoftware($user, $software)) {
            $this->addFlashMessage('error', 'You do not have permission to delete this software.');
            return $this->redirectToRoute($request, $response, 'software.edit', ['id' => $id]);
        }

        try {
            $softwareTitle = $software['stitle'];
            $this->softwareModel->delete($id);

            $this->logUserAction('software_deleted', ['software_id' => $id, 'title' => $softwareTitle]);
            $this->addFlashMessage('success', 'Software deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete software', [
                'software_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete software. Please try again.');
        }

        return $this->redirectToRoute($request, $response, 'software.index');
    }

    /**
     * Check if user can create software
     */
    private function canUserCreateSoftware($user): bool
    {
        return $user && $user->usertype >= 1;
    }

    /**
     * Check if user can edit software
     */
    private function canUserEditSoftware($user, $software): bool
    {
        if (!$user) {
            return false;
        }

        // Check if user object has isAdmin method to prevent errors
        if (!method_exists($user, 'isAdmin')) {
            return false;
        }

        // Admin can edit all software
        if ($user->isAdmin()) {
            return true;
        }

        // Users with usertype >= 1 can edit software
        return $user->usertype >= 1;
    }

    /**
     * Check if user can delete software
     */
    private function canUserDeleteSoftware($user, $software): bool
    {
        // Only admins can delete software
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    /**
     * Manage software associations (add/remove)
     */
    public function manageAssociations(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $software = $this->softwareModel->find($id);
        if (!$software) {
            return $this->json($response, ['error' => 'Software not found'], 404);
        }

        if (!$this->canUserEditSoftware($user, $software)) {
            return $this->json($response, ['error' => 'Permission denied'], 403);
        }

        $data = $this->getParsedBody($request);
        $type = $data['type'] ?? '';
        $itemId = (int) ($data['id'] ?? 0);
        $action = $data['action'] ?? '';

        if (!in_array($type, ['item', 'invoice', 'contract', 'file']) || !$itemId || !in_array($action, ['add', 'remove'])) {
            return $this->json($response, ['error' => 'Invalid parameters'], 400);
        }

        try {
            $success = false;

            switch ($type) {
                case 'item':
                    if ($action === 'add') {
                        $success = $this->softwareModel->associateItem($id, $itemId);
                    } else {
                        $success = $this->softwareModel->dissociateItem($id, $itemId);
                    }
                    break;

                case 'invoice':
                case 'contract':
                case 'file':
                    // TODO: Implement other association types
                    return $this->json($response, ['error' => 'Association type not yet implemented'], 501);
            }

            if ($success) {
                $this->logUserAction('software_association_' . $action, [
                    'software_id' => $software['id'],
                    'type' => $type,
                    'item_id' => $itemId
                ]);

                return $this->json($response, ['success' => true]);
            } else {
                return $this->json($response, ['error' => 'Association already exists or item not found'], 400);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to manage software association', [
                'software_id' => $id,
                'type' => $type,
                'item_id' => $itemId,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return $this->json($response, ['error' => 'Failed to update association'], 500);
        }
    }
}