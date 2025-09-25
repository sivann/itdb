<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\RackModel;
use App\Models\LocationModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class RackController extends BaseController
{
    private AuthService $authService;
    private RackModel $rackModel;
    private LocationModel $locationModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        RackModel $rackModel,
        LocationModel $locationModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->rackModel = $rackModel;
        $this->locationModel = $locationModel;
    }

    /**
     * List all racks
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
        if (!empty($queryParams['location'])) {
            $filters['location'] = $queryParams['location'];
        }
        if (!empty($queryParams['area'])) {
            $filters['area'] = $queryParams['area'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = 20;

        // Get paginated racks
        $result = $this->rackModel->getPaginated($page, $perPage, $filters);

        // Get locations for filter dropdown
        $locations = $this->locationModel->getAll();

        return $this->render($response, 'racks/index.twig', [
            'user' => $user,
            'racks' => $result['data'],
            'pagination' => [
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'last_page' => $result['total_pages'],
                'from' => ($result['page'] - 1) * $result['per_page'] + 1,
                'to' => min($result['page'] * $result['per_page'], $result['total'])
            ],
            'query' => $queryParams,
            'locations' => $locations,
        ]);
    }

    /**
     * Show rack details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $rack = $this->rackModel->find($id);
        if (!$rack) {
            $this->addFlashMessage('error', 'Rack not found');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        // Get rack layout with items
        $rack['layout'] = $this->rackModel->getRackLayout($id);

        return $this->render($response, 'racks/edit.twig', [
            'mode' => 'view',
            'user' => $user,
            'rack' => $rack,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get locations and areas
        $locations = $this->locationModel->getAll();
        $locationAreas = [];

        return $this->render($response, 'racks/edit.twig', [
            'mode' => 'create',
            'user' => $user,
            'locations' => $locations,
            'location_areas' => $locationAreas,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new rack
     */
    public function store(Request $request, Response $response): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'racks.create');
        }

        $data = $this->getParsedBody($request);

        // Validation
        $errors = [];
        if (empty($data['locationid'])) {
            $errors[] = 'Location is required';
        } elseif (!$this->locationModel->find($data['locationid'])) {
            $errors[] = 'Invalid location';
        }

        // LocationArea not supported - skip validation
        // if (!empty($data['locareaid']) && !LocationArea::find($data['locareaid'])) {
        //     $errors[] = 'Invalid location area';
        // }

        if (!empty($data['usize']) && ((int) $data['usize'] < 1 || (int) $data['usize'] > 100)) {
            $errors[] = 'U-size must be between 1 and 100';
        }

        if (!empty($data['depth']) && (int) $data['depth'] < 1) {
            $errors[] = 'Depth must be greater than 0';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $rackData = [
                'locationid' => (int) $data['locationid'],
                'usize' => !empty($data['usize']) ? (int) $data['usize'] : null,
                'depth' => !empty($data['depth']) ? (int) $data['depth'] : null,
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'model' => $this->sanitizeString($data['model'] ?? ''),
                'label' => $this->sanitizeString($data['label'] ?? ''),
                'revnums' => !empty($data['revnums']) ? (int) $data['revnums'] : null,
                'locareaid' => !empty($data['locareaid']) ? (int) $data['locareaid'] : null,
            ];

            $rackId = $this->rackModel->create($rackData);

            $this->logUserAction('rack_created', ['rack_id' => $rackId]);
            $this->addFlashMessage('success', 'Rack created successfully');
            return $this->redirectToRoute($request, $response, 'racks.show', ['id' => $rackId]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating rack', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error creating rack');
            return $this->redirectToRoute($request, $response, 'racks.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $rack = $this->rackModel->find($id);
        if (!$rack) {
            $this->addFlashMessage('error', 'Rack not found');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        // Get locations and areas
        $locations = $this->locationModel->getAll();
        $locationAreas = [];

        return $this->render($response, 'racks/edit.twig', [
            'mode' => 'edit',
            'user' => $user,
            'rack' => $rack,
            'locations' => $locations,
            'location_areas' => $locationAreas,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update rack
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        $id = (int) $args['id'];
        $data = $this->getParsedBody($request);

        $rack = $this->rackModel->find($id);
        if (!$rack) {
            $this->addFlashMessage('error', 'Rack not found');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        // Validation
        $errors = [];
        if (empty($data['locationid'])) {
            $errors[] = 'Location is required';
        } elseif (!$this->locationModel->find($data['locationid'])) {
            $errors[] = 'Invalid location';
        }

        // LocationArea not supported - skip validation
        // if (!empty($data['locareaid']) && !LocationArea::find($data['locareaid'])) {
        //     $errors[] = 'Invalid location area';
        // }

        if (!empty($data['usize']) && ((int) $data['usize'] < 1 || (int) $data['usize'] > 100)) {
            $errors[] = 'U-size must be between 1 and 100';
        }

        if (!empty($data['depth']) && (int) $data['depth'] < 1) {
            $errors[] = 'Depth must be greater than 0';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $updateData = [
                'locationid' => (int) $data['locationid'],
                'usize' => !empty($data['usize']) ? (int) $data['usize'] : null,
                'depth' => !empty($data['depth']) ? (int) $data['depth'] : null,
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'model' => $this->sanitizeString($data['model'] ?? ''),
                'label' => $this->sanitizeString($data['label'] ?? ''),
                'revnums' => !empty($data['revnums']) ? (int) $data['revnums'] : null,
                'locareaid' => !empty($data['locareaid']) ? (int) $data['locareaid'] : null,
            ];

            $this->rackModel->update($id, $updateData);

            $this->logUserAction('rack_updated', ['rack_id' => $id]);
            $this->addFlashMessage('success', 'Rack updated successfully');
            return $this->redirectToRoute($request, $response, 'racks.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating rack', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating rack');
            return $this->redirectToRoute($request, $response, 'racks.edit', ['id' => $id]);
        }
    }

    /**
     * Delete rack
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        $id = (int) $args['id'];

        $rack = $this->rackModel->find($id);
        if (!$rack) {
            $this->addFlashMessage('error', 'Rack not found');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        // Check if rack can be deleted
        $canDeleteResult = $this->rackModel->canDelete($id);
        if (!$canDeleteResult['can_delete']) {
            $errorMsg = 'Cannot delete rack: referenced by ' . implode(', ', $canDeleteResult['references']);
            $this->addFlashMessage('error', $errorMsg);
            return $this->redirectToRoute($request, $response, 'racks.show', ['id' => $id]);
        }

        try {
            $this->logUserAction('rack_deleted', ['rack_id' => $rack['id'], 'label' => $rack['label']]);
            $this->rackModel->delete($id);

            $this->addFlashMessage('success', 'Rack deleted successfully');
            return $this->redirectToRoute($request, $response, 'racks.index');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting rack', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting rack');
            return $this->redirectToRoute($request, $response, 'racks.show', ['id' => $id]);
        }
    }

    /**
     * Show rack visualization
     */
    public function visualize(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $rack = $this->rackModel->find($id);
        if (!$rack) {
            $this->addFlashMessage('error', 'Rack not found');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        // Get rack layout
        $rackData = $this->rackModel->getRackLayout($id);
        $rackSize = $rack['usize'] ?: 42;

        return $this->render($response, 'racks/visualize.twig', [
            'user' => $user,
            'rack' => $rack,
            'rack_data' => $rackData,
            'rack_size' => $rackSize,
        ]);
    }

    /**
     * Get racks by location (AJAX endpoint)
     */
    public function getByLocation(Request $request, Response $response, array $args): Response
    {
        $locationId = (int) $args['location_id'];

        $location = $this->locationModel->find($locationId);
        if (!$location) {
            return $this->json($response, ['error' => 'Location not found'], 404);
        }

        // Get racks for this location
        $allRacks = $this->rackModel->getAll();
        $racks = [];
        foreach ($allRacks as $rack) {
            if ((int)$rack['locationid'] === $locationId) {
                $racks[] = [
                    'id' => $rack['id'],
                    'name' => $rack['label'],
                    'label' => $rack['label'],
                    'model' => $rack['model'],
                    'usize' => $rack['usize'],
                ];
            }
        }

        return $this->json($response, ['racks' => $racks]);
    }

    /**
     * Get available rack positions (AJAX endpoint)
     */
    public function getAvailablePositions(Request $request, Response $response, array $args): Response
    {
        $rackId = (int) $args['rack_id'];

        $rack = $this->rackModel->find($rackId);
        if (!$rack) {
            return $this->json($response, ['error' => 'Rack not found'], 404);
        }

        $layout = $this->rackModel->getRackLayout($rackId);
        $availablePositions = [];
        $occupiedPositions = [];

        foreach ($layout as $position) {
            if ($position['available']) {
                $availablePositions[] = $position['position'];
            } else {
                $occupiedPositions[] = $position['position'];
            }
        }

        $rackSize = $rack['usize'] ?: 42;

        return $this->json($response, [
            'rack' => [
                'id' => $rack['id'],
                'name' => $rack['label'],
                'size' => $rackSize,
            ],
            'available_positions' => $availablePositions,
            'occupied_positions' => $occupiedPositions,
        ]);
    }

    /**
     * Bulk operations on racks
     */
    public function bulkAction(Request $request, Response $response): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        $data = $this->getParsedBody($request);
        $action = $data['action'] ?? '';
        $rackIds = $data['rack_ids'] ?? [];

        if (empty($rackIds) || !is_array($rackIds)) {
            $this->addFlashMessage('error', 'No racks selected');
            return $this->redirectToRoute($request, $response, 'racks.index');
        }

        try {
            // Validate rack IDs exist
            $validRackIds = [];
            foreach ($rackIds as $rackId) {
                if ($this->rackModel->find((int)$rackId)) {
                    $validRackIds[] = (int)$rackId;
                }
            }

            if (empty($validRackIds)) {
                $this->addFlashMessage('error', 'No valid racks found');
                return $this->redirectToRoute($request, $response, 'racks.index');
            }

            switch ($action) {
                case 'delete':
                    $deletedCount = 0;
                    foreach ($validRackIds as $rackId) {
                        $canDeleteResult = $this->rackModel->canDelete($rackId);
                        if ($canDeleteResult['can_delete']) {
                            $this->rackModel->delete($rackId);
                            $deletedCount++;
                        }
                    }
                    $this->addFlashMessage('success', "Deleted {$deletedCount} racks (skipped racks with items)");
                    break;

                case 'change_location':
                    if (empty($data['new_location']) || !$this->locationModel->find($data['new_location'])) {
                        $this->addFlashMessage('error', 'Invalid location');
                        return $this->redirectToRoute($request, $response, 'racks.index');
                    }

                    $updatedCount = 0;
                    foreach ($validRackIds as $rackId) {
                        if ($this->rackModel->update($rackId, ['locationid' => (int) $data['new_location']])) {
                            $updatedCount++;
                        }
                    }
                    $this->addFlashMessage('success', "Updated location for {$updatedCount} racks");
                    break;

                default:
                    $this->addFlashMessage('error', 'Invalid action');
                    break;
            }

            $this->logUserAction('rack_bulk_action', [
                'action' => $action,
                'rack_count' => count($validRackIds),
                'rack_ids' => $validRackIds
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error performing bulk action', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error performing bulk action');
        }

        return $this->redirectToRoute($request, $response, 'racks.index');
    }
}