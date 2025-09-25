<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LocationModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class LocationController extends BaseController
{
    private AuthService $authService;
    private LocationModel $locationModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        LocationModel $locationModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->locationModel = $locationModel;
    }

    /**
     * List all locations
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);

        // Build filters for LocationModel
        $filters = [];
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }
        if (!empty($queryParams['floor'])) {
            $filters['floor'] = $queryParams['floor'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = 20;

        // Get paginated locations using LocationModel
        $result = $this->locationModel->getPaginated($page, $perPage, $filters);
        $locations = $result['data'];

        // Get available floors for filter dropdown
        $floors = $this->locationModel->getFloors();

        return $this->render($response, 'locations/index.twig', [
            'user' => $user,
            'locations' => $locations,
            'total' => $result['total'],
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'search' => $queryParams['search'] ?? '',
            'floor_filter' => $queryParams['floor'] ?? '',
            'floors' => $floors,
        ]);
    }

    /**
     * Show location details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $location = $this->locationModel->findWithRelations($id);
        if (!$location) {
            $this->addFlashMessage('error', 'Location not found');
            return $this->redirectToRoute($request, $response, 'locations.index');
        }

        return $this->render($response, 'locations/edit.twig', [
            'mode' => 'view',
            'user' => $user,
            'location' => $location,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        return $this->render($response, 'locations/edit.twig', [
            'mode' => 'create',
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new location
     */
    public function store(Request $request, Response $response): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'locations.create');
        }

        $data = $this->getParsedBody($request);

        // Validation
        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }

        // Handle floor plan upload
        $uploadedFiles = $request->getUploadedFiles();
        $floorPlanFile = $uploadedFiles['floorplan'] ?? null;
        $floorplanFilename = null;

        if ($floorPlanFile && $floorPlanFile->getError() === UPLOAD_ERR_OK) {
            $originalName = $floorPlanFile->getClientFilename();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);

            // Validate file type
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            if (!in_array(strtolower($extension), $allowedExtensions)) {
                $errors[] = 'Floor plan must be an image or PDF file';
            } else {
                $floorplanFilename = uniqid() . '.' . $extension;
            }
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            // Upload floor plan if provided
            if ($floorplanFilename) {
                $uploadPath = $_ENV['FLOORPLAN_PATH'] ?? './storage/floorplans';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                $floorPlanFile->moveTo($uploadPath . '/' . $floorplanFilename);
            }

            $locationId = $this->locationModel->create([
                'name' => $this->sanitizeString($data['name']),
                'floor' => $this->sanitizeString($data['floor'] ?? ''),
                'floorplanfn' => $floorplanFilename,
            ]);

            $this->logUserAction('location_created', ['location_id' => $locationId]);
            $this->addFlashMessage('success', 'Location created successfully');
            return $this->redirectToRoute($request, $response, 'locations.show', ['id' => $locationId]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating location', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error creating location');
            return $this->redirectToRoute($request, $response, 'locations.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $location = $this->locationModel->find($id);
        if (!$location) {
            $this->addFlashMessage('error', 'Location not found');
            return $this->redirectToRoute($request, $response, 'locations.index');
        }

        return $this->render($response, 'locations/edit.twig', [
            'mode' => 'edit',
            'user' => $user,
            'location' => $location,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update location
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'locations.index');
        }

        $id = (int) $args['id'];
        $data = $this->getParsedBody($request);

        $location = $this->locationModel->find($id);
        if (!$location) {
            $this->addFlashMessage('error', 'Location not found');
            return $this->redirectToRoute($request, $response, 'locations.index');
        }

        // Validation
        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }

        // Handle floor plan upload
        $uploadedFiles = $request->getUploadedFiles();
        $floorPlanFile = $uploadedFiles['floorplan'] ?? null;
        $floorplanFilename = $location['floorplanfn']; // Keep existing by default

        if ($floorPlanFile && $floorPlanFile->getError() === UPLOAD_ERR_OK) {
            $originalName = $floorPlanFile->getClientFilename();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);

            // Validate file type
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            if (!in_array(strtolower($extension), $allowedExtensions)) {
                $errors[] = 'Floor plan must be an image or PDF file';
            } else {
                $floorplanFilename = uniqid() . '.' . $extension;
            }
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            // Upload new floor plan if provided
            if ($floorPlanFile && $floorPlanFile->getError() === UPLOAD_ERR_OK) {
                $uploadPath = $_ENV['FLOORPLAN_PATH'] ?? './storage/floorplans';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Delete old floor plan if exists
                if ($location['floorplanfn'] && file_exists($uploadPath . '/' . $location['floorplanfn'])) {
                    unlink($uploadPath . '/' . $location['floorplanfn']);
                }

                $floorPlanFile->moveTo($uploadPath . '/' . $floorplanFilename);
            }

            $this->locationModel->update($id, [
                'name' => $this->sanitizeString($data['name']),
                'floor' => $this->sanitizeString($data['floor'] ?? ''),
                'floorplanfn' => $floorplanFilename,
            ]);

            $this->logUserAction('location_updated', ['location_id' => $id]);
            $this->addFlashMessage('success', 'Location updated successfully');
            return $this->redirectToRoute($request, $response, 'locations.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating location', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating location');
            return $this->redirectToRoute($request, $response, 'locations.edit', ['id' => $id]);
        }
    }

    /**
     * Delete location
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'locations.index');
        }

        $id = (int) $args['id'];

        $location = $this->locationModel->findWithCounts($id);
        if (!$location) {
            $this->addFlashMessage('error', 'Location not found');
            return $this->redirectToRoute($request, $response, 'locations.index');
        }

        // Check if location has items, racks, or areas
        if (($location['items_count'] ?? 0) > 0 || ($location['racks_count'] ?? 0) > 0 || ($location['areas_count'] ?? 0) > 0) {
            $this->addFlashMessage('error', 'Cannot delete location with associated items, racks, or areas. Remove them first.');
            return $this->redirectToRoute($request, $response, 'locations.show', ['id' => $id]);
        }

        try {
            // Delete floor plan file if exists
            if ($location['floorplanfn']) {
                $uploadPath = $_ENV['FLOORPLAN_PATH'] ?? './storage/floorplans';
                $filePath = $uploadPath . '/' . $location['floorplanfn'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->logUserAction('location_deleted', ['location_id' => $id, 'name' => $location['name']]);
            $this->locationModel->delete($id);

            $this->addFlashMessage('success', 'Location deleted successfully');
            return $this->redirectToRoute($request, $response, 'locations.index');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting location', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting location');
            return $this->redirectToRoute($request, $response, 'locations.show', ['id' => $id]);
        }
    }

    /**
     * Display floor plan
     */
    public function floorPlan(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $location = $this->locationModel->find($id);
        if (!$location || !$location['floorplanfn']) {
            $this->addFlashMessage('error', 'Floor plan not found');
            return $this->redirectToRoute($request, $response, 'locations.show', ['id' => $id]);
        }

        $uploadPath = $_ENV['FLOORPLAN_PATH'] ?? './storage/floorplans';
        $filePath = $uploadPath . '/' . $location['floorplanfn'];

        if (!file_exists($filePath)) {
            $this->addFlashMessage('error', 'Floor plan file does not exist');
            return $this->redirectToRoute($request, $response, 'locations.show', ['id' => $id]);
        }

        try {
            $this->logUserAction('floorplan_viewed', ['location_id' => $id]);

            $fileContent = file_get_contents($filePath);
            $mimeType = mime_content_type($filePath);

            $response->getBody()->write($fileContent);

            return $response
                ->withHeader('Content-Type', $mimeType)
                ->withHeader('Content-Length', (string) strlen($fileContent));

        } catch (\Exception $e) {
            $this->logger->error('Error displaying floor plan', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error displaying floor plan');
            return $this->redirectToRoute($request, $response, 'locations.show', ['id' => $id]);
        }
    }

    /**
     * Get location areas as JSON (for AJAX requests)
     */
    public function getAreas(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $location = $this->locationModel->find($id);
        if (!$location) {
            return $this->json($response, ['error' => 'Location not found'], 404);
        }

        $areas = $this->locationModel->getAreas($id);

        return $this->json($response, ['areas' => $areas]);
    }
}