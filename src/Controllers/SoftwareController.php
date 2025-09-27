<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SoftwareModel;
use App\Models\AgentModel;
use App\Models\LicenseTypeModel;
use App\Models\FileModel;
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
    private LicenseTypeModel $licenseTypeModel;
    private FileModel $fileModel;
    private \PDO $pdo;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        SoftwareModel $softwareModel,
        AgentModel $agentModel,
        LicenseTypeModel $licenseTypeModel,
        FileModel $fileModel,
        \PDO $pdo
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->softwareModel = $softwareModel;
        $this->agentModel = $agentModel;
        $this->licenseTypeModel = $licenseTypeModel;
        $this->fileModel = $fileModel;
        $this->pdo = $pdo;
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
                'manufacturerid' => !empty($data['manufacturerid']) ? (int) $data['manufacturerid'] : null,
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

        // Get form options
        $manufacturers = $this->agentModel->getSoftwareManufacturers();
        $licenseTypes = $this->licenseTypeModel->getAll();
        $fileTypes = $this->fileModel->getFileTypes();
        $allTags = $this->softwareModel->getAllTags();

        return $this->render($response, 'software/edit.twig', [
            'software' => $software,
            'form_options' => [
                'manufacturers' => $manufacturers,
                'license_types' => $licenseTypes,
            ],
            'file_types' => $fileTypes,
            'all_tags' => $allTags,
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
            'current_tab' => $tab,
        ]);
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
                'scomments' => $this->sanitizeString($data['scomments'] ?? ''),
                'slicense' => $this->sanitizeString($data['slicense'] ?? ''),
                'licqty' => !empty($data['licqty']) ? (int) $data['licqty'] : 1,
                'slicensetype' => !empty($data['slicensetype']) ? (int) $data['slicensetype'] : 0,
                'manufacturerid' => !empty($data['manufacturerid']) ? (int) $data['manufacturerid'] : null,
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

        // Handle create_and_add_tag action separately
        if ($action === 'create_and_add_tag') {
            return $this->createAndAddTag($request, $response, $id, $data);
        }

        if (!in_array($type, ['item', 'invoice', 'contract', 'file', 'tag']) || !$itemId || !in_array($action, ['add', 'remove'])) {
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
                    if ($action === 'add') {
                        $success = $this->softwareModel->associateInvoice($id, $itemId);
                    } else {
                        $success = $this->softwareModel->dissociateInvoice($id, $itemId);
                    }
                    break;

                case 'contract':
                    if ($action === 'add') {
                        $success = $this->softwareModel->associateContract($id, $itemId);
                    } else {
                        $success = $this->softwareModel->dissociateContract($id, $itemId);
                    }
                    break;

                case 'file':
                    if ($action === 'add') {
                        $success = $this->softwareModel->associateFile($id, $itemId);
                    } else {
                        $success = $this->softwareModel->dissociateFile($id, $itemId);
                    }
                    break;

                case 'tag':
                    if ($action === 'add') {
                        $success = $this->softwareModel->associateTag($id, $itemId);
                    } else {
                        $success = $this->softwareModel->dissociateTag($id, $itemId);
                    }
                    break;
            }

            if ($success) {
                $this->logUserAction('software_association_' . $action, [
                    'software_id' => $software['id'],
                    'type' => $type,
                    'item_id' => $itemId
                ]);

                // Include additional data for UI updates
                $responseData = ['success' => true];

                if ($action === 'add') {
                    // Get details for UI update based on type
                    switch ($type) {
                        case 'item':
                            $sql = "SELECT i.id, i.label, i.function, it.name as type_name,
                                           l.name as location_name, u.username
                                    FROM items i
                                    LEFT JOIN itemtypes it ON i.itemtypeid = it.id
                                    LEFT JOIN locations l ON i.locationid = l.id
                                    LEFT JOIN users u ON i.userid = u.id
                                    WHERE i.id = ?";
                            break;

                        case 'invoice':
                            $sql = "SELECT i.id, i.date, i.totalcost, i.comments,
                                           a.title as vendor_title
                                    FROM invoices i
                                    LEFT JOIN agents a ON i.vendorid = a.id
                                    WHERE i.id = ?";
                            break;

                        case 'contract':
                            $sql = "SELECT c.id, c.title, c.startdate, c.currentenddate as enddate,
                                           a.title as contractor_name
                                    FROM contracts c
                                    LEFT JOIN agents a ON c.contractorid = a.id
                                    WHERE c.id = ?";
                            break;

                        case 'file':
                            $sql = "SELECT f.id, f.fname, f.title, f.filesize as file_size,
                                           f.uploaddate, ft.name as filetype_name
                                    FROM files f
                                    LEFT JOIN filetypes ft ON f.ftype = ft.id
                                    WHERE f.id = ?";
                            break;

                        default:
                            $sql = null;
                    }

                    if ($sql) {
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute([$itemId]);
                        $itemData = $stmt->fetch(\PDO::FETCH_ASSOC);

                        if ($itemData) {
                            // Format data based on type
                            if ($type === 'invoice') {
                                $itemData['date_formatted'] = $itemData['date'] ? date('Y-m-d', $itemData['date']) : 'N/A';
                                $itemData['total_formatted'] = number_format($itemData['totalcost'] ?? 0, 2);
                            } elseif ($type === 'contract') {
                                $itemData['startdate'] = $itemData['startdate'] ? date('Y-m-d', $itemData['startdate']) : 'N/A';
                                $itemData['enddate'] = $itemData['enddate'] ? date('Y-m-d', $itemData['enddate']) : 'N/A';
                            } elseif ($type === 'file') {
                                $itemData['uploaddate_formatted'] = $itemData['uploaddate'] ? date('Y-m-d', $itemData['uploaddate']) : 'N/A';
                            }

                            $responseData['data'] = $itemData;
                        }
                    }
                }

                return $this->json($response, $responseData);
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

    /**
     * Create a new tag and add it to software
     */
    private function createAndAddTag(Request $request, Response $response, int $softwareId, array $data): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$this->validateCsrfToken($request)) {
            return $this->json($response, ['error' => 'Invalid CSRF token'], 403);
        }

        $tagName = trim($data['name'] ?? '');
        $tagColor = $data['color'] ?? '#007bff';

        if (empty($tagName)) {
            return $this->json($response, ['error' => 'Tag name is required'], 400);
        }

        try {
            // Check if tag already exists (case-insensitive)
            $sql = "SELECT id FROM tags WHERE LOWER(name) = LOWER(?) LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tagName]);
            $existingTag = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existingTag) {
                // Tag exists, just associate it with the software
                $tagId = $existingTag['id'];
                $success = $this->softwareModel->associateTag($softwareId, $tagId);

                if ($success) {
                    $this->logUserAction('software_existing_tag_added', [
                        'software_id' => $softwareId,
                        'tag_id' => $tagId,
                        'tag_name' => $tagName
                    ]);
                    return $this->json($response, [
                        'success' => true,
                        'message' => 'Existing tag added successfully',
                        'tagId' => $tagId,
                        'data' => ['id' => $tagId, 'name' => $tagName]
                    ]);
                } else {
                    return $this->json($response, ['error' => 'Tag is already associated with this software'], 400);
                }
            }

            // Create new tag
            $sql = "INSERT INTO tags (name, color) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tagName, $tagColor]);
            $tagId = (int) $this->pdo->lastInsertId();

            // Associate the new tag with the software
            $success = $this->softwareModel->associateTag($softwareId, $tagId);

            if ($success) {
                $this->logUserAction('software_tag_created_and_added', [
                    'software_id' => $softwareId,
                    'tag_id' => $tagId,
                    'tag_name' => $tagName,
                    'tag_color' => $tagColor
                ]);
                return $this->json($response, [
                    'success' => true,
                    'message' => 'Tag created and added successfully',
                    'tagId' => $tagId,
                    'data' => ['id' => $tagId, 'name' => $tagName, 'color' => $tagColor]
                ]);
            } else {
                // If association failed, remove the created tag
                $sql = "DELETE FROM tags WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tagId]);

                return $this->json($response, ['error' => 'Failed to associate tag with software'], 500);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to create and add tag', [
                'software_id' => $softwareId,
                'tag_name' => $tagName,
                'tag_color' => $tagColor,
                'error' => $e->getMessage()
            ]);

            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                return $this->json($response, ['error' => 'Tag name already exists'], 400);
            }

            return $this->json($response, ['error' => 'Failed to create tag'], 500);
        }
    }
}