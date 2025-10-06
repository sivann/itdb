<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ItemModel;
use App\Models\AgentModel;
use App\Models\FileModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use PDO;

class ItemController extends BaseController
{
    private AuthService $authService;
    private ItemModel $itemModel;
    private AgentModel $agentModel;
    private FileModel $fileModel;
    private PDO $pdo;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        ItemModel $itemModel,
        AgentModel $agentModel,
        FileModel $fileModel,
        PDO $pdo
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->itemModel = $itemModel;
        $this->agentModel = $agentModel;
        $this->fileModel = $fileModel;
        $this->pdo = $pdo;
    }

    /**
     * List all items
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);

        // Build filters for ItemModel
        $filters = [];
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }
        if (!empty($queryParams['type'])) {
            $filters['type'] = $queryParams['type'];
        }
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }
        if (!empty($queryParams['location'])) {
            $filters['location'] = $queryParams['location'];
        }
        if (!empty($queryParams['user'])) {
            $filters['user'] = $queryParams['user'];
        }
        if (!empty($queryParams['sort'])) {
            $filters['sort'] = $queryParams['sort'];
        }
        if (!empty($queryParams['order'])) {
            $filters['order'] = $queryParams['order'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($queryParams['per_page'] ?? 25)));

        // Get paginated items
        $result = $this->itemModel->getPaginated($page, $perPage, $filters);

        // Get filter options
        $filterOptions = $this->itemModel->getFilterOptions();

        return $this->render($response, 'items/index.twig', [
            'items' => $result['data'],
            'filters' => $filterOptions,
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
     * Show item details (redirects to edit)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $queryParams = $this->getQueryParams($request);

        // Preserve query parameters when redirecting
        $queryString = http_build_query($queryParams);
        $redirectUrl = '/items/' . $id . '/edit' . ($queryString ? '?' . $queryString : '');

        return $response->withStatus(302)->withHeader('Location', $redirectUrl);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Check if user can create items
        if (!$user || !$this->canUserCreateItems($user)) {
            $this->addFlashMessage('error', 'You do not have permission to create items.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        // Get form options
        $filterOptions = $this->itemModel->getFilterOptions();

        return $this->render($response, 'items/create.twig', [
            'form_options' => $filterOptions,
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new item
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$this->canUserCreateItems($user)) {
            $this->addFlashMessage('error', 'You do not have permission to create items.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'items.create');
        }

        $data = $this->getParsedBody($request);

        // Validation rules
        $rules = [
            'itemtypeid' => 'required|integer',
            'status' => 'required|integer',
            'function' => 'string|max:255',
            'model' => 'string|max:100',
            'sn' => 'string|max:100',
            'label' => 'string|max:50',
        ];

        $errors = $this->validateItemData($data, $rules);

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlashMessage('error', $error);
            }
            return $this->redirectToRoute($request, $response, 'items.create');
        }

        try {
            // Prepare data for creation using correct database field names
            $itemData = [
                'function' => $this->sanitizeString($data['function'] ?? ''),
                'itemtypeid' => !empty($data['itemtypeid']) ? (int) $data['itemtypeid'] : null,
                'status' => (int) $data['status'],
                'model' => $this->sanitizeString($data['model'] ?? ''),
                'sn' => $this->sanitizeString($data['sn'] ?? ''),
                'label' => $this->sanitizeString($data['label'] ?? ''),
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'maintenanceinfo' => $this->sanitizeString($data['maintenanceinfo'] ?? ''),
                'userid' => !empty($data['userid']) ? (int) $data['userid'] : null,
                'locationid' => !empty($data['locationid']) ? (int) $data['locationid'] : null,
                'ipv4' => $this->sanitizeString($data['ipv4'] ?? ''),
                'dnsname' => $this->sanitizeString($data['dnsname'] ?? ''),
                'cpu' => $this->sanitizeString($data['cpu'] ?? ''),
                'ram' => $this->sanitizeString($data['ram'] ?? ''),
                'hd' => $this->sanitizeString($data['hd'] ?? ''),
            ];

            // Handle purchase information
            if (!empty($data['purchasedate'])) {
                $itemData['purchasedate'] = strtotime($data['purchasedate']);
            }
            if (!empty($data['warrantymonths'])) {
                $itemData['warrantymonths'] = (int) $data['warrantymonths'];
            }

            $itemId = $this->itemModel->create($itemData);

            $this->logUserAction('item_created', ['item_id' => $itemId]);
            $this->addFlashMessage('success', 'Item created successfully.');

            return $this->redirectToRoute($request, $response, 'items.edit', ['id' => $itemId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create item', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create item. Please try again.');
            return $this->redirectToRoute($request, $response, 'items.create');
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

        $item = $this->itemModel->findWithRelations($id);
        if (!$item) {
            $this->addFlashMessage('error', 'Item not found.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        if (!$this->canUserEditItem($user, $item)) {
            $this->addFlashMessage('error', 'You do not have permission to edit this item.');
            return $this->redirectToRoute($request, $response, 'items.edit', ['id' => $id]);
        }

        // Get form options from ItemModel
        $formOptions = $this->itemModel->getFilterOptions();

        // Get file types for associations
        $fileTypes = $this->fileModel->getFileTypes();

        // Get all tags for associations
        $allTags = $this->itemModel->getAllTags();

        return $this->render($response, 'items/edit.twig', [
            'item' => $item,
            'form_options' => $formOptions,
            'file_types' => $fileTypes,
            'all_tags' => $allTags,
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
            'current_tab' => $tab,
        ]);
    }

    /**
     * Update item
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $item = $this->itemModel->find($id);
        if (!$item) {
            $this->addFlashMessage('error', 'Item not found.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        if (!$this->canUserEditItem($user, $item)) {
            $this->addFlashMessage('error', 'You do not have permission to edit this item.');
            return $this->redirectToRoute($request, $response, 'items.edit', ['id' => $id]);
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'items.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        try {
            // Prepare data for update using correct database field names
            $updateData = [
                'function' => $this->sanitizeString($data['function'] ?? ''),
                'itemtypeid' => !empty($data['itemtypeid']) ? (int) $data['itemtypeid'] : null,
                'status' => (int) $data['status'],
                'model' => $this->sanitizeString($data['model'] ?? ''),
                'sn' => $this->sanitizeString($data['sn'] ?? ''),
                'label' => $this->sanitizeString($data['label'] ?? ''),
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'maintenanceinfo' => $this->sanitizeString($data['maintenanceinfo'] ?? ''),
                'userid' => !empty($data['userid']) ? (int) $data['userid'] : null,
                'locationid' => !empty($data['locationid']) ? (int) $data['locationid'] : null,
                'ipv4' => $this->sanitizeString($data['ipv4'] ?? ''),
                'dnsname' => $this->sanitizeString($data['dnsname'] ?? ''),
                'cpu' => $this->sanitizeString($data['cpu'] ?? ''),
                'ram' => $this->sanitizeString($data['ram'] ?? ''),
                'hd' => $this->sanitizeString($data['hd'] ?? ''),
            ];

            // Handle purchase information
            if (!empty($data['purchasedate'])) {
                $updateData['purchasedate'] = strtotime($data['purchasedate']);
            }
            if (!empty($data['warrantymonths'])) {
                $updateData['warrantymonths'] = (int) $data['warrantymonths'];
            }

            $this->itemModel->update($id, $updateData);

            $this->logUserAction('item_updated', ['item_id' => $id]);
            $this->addFlashMessage('success', 'Item updated successfully.');

            return $this->redirectToRoute($request, $response, 'items.edit', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update item', [
                'item_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update item. Please try again.');
            return $this->redirectToRoute($request, $response, 'items.edit', ['id' => $id]);
        }
    }

    /**
     * Delete item
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $item = $this->itemModel->find($id);
        if (!$item) {
            $this->addFlashMessage('error', 'Item not found.');
            return $this->redirectToRoute($request, $response, 'items.index');
        }

        if (!$this->canUserDeleteItem($user, $item)) {
            $this->addFlashMessage('error', 'You do not have permission to delete this item.');
            return $this->redirectToRoute($request, $response, 'items.edit', ['id' => $id]);
        }

        try {
            $itemTitle = $item['function'] ?: $item['model'] ?: "Item #{$item['id']}";
            $this->itemModel->delete($id);

            $this->logUserAction('item_deleted', ['item_id' => $id, 'title' => $itemTitle]);
            $this->addFlashMessage('success', 'Item deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete item', [
                'item_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete item. Please try again.');
        }

        return $this->redirectToRoute($request, $response, 'items.index');
    }

    /**
     * Search items (API endpoint)
     */
    public function search(Request $request, Response $response): Response
    {
        $queryParams = $this->getQueryParams($request);
        $query = $queryParams['q'] ?? '';
        $excludeSoftware = !empty($queryParams['exclude_software']) ? (int) $queryParams['exclude_software'] : null;

        // Use ItemModel search
        $items = $this->itemModel->search($query, $excludeSoftware, 20);

        // Transform the data to ensure correct field mapping for frontend
        $transformedItems = [];
        foreach ($items as $item) {
            $transformedItems[] = [
                'id' => $item['id'],
                'label' => $item['label'] ?: $item['function'] ?: "#" . $item['id'],
                'model' => $item['model'] ?: 'Unknown Model',
                'sn' => $item['sn'],
                'function' => $item['function'], // description/title
                'itemType' => [
                    'id' => $item['itemtypeid'] ?? null,
                    'name' => $item['itemtype_name'] ?? 'Unknown Type'
                ],
                'location' => [
                    'id' => $item['locationid'] ?? null,
                    'name' => $item['location_name'] ?? 'No Location'
                ],
                'user' => [
                    'id' => $item['userid'] ?? null,
                    'display_name' => $item['username'] ?? 'Unassigned'
                ]
            ];
        }

        return $this->json($response, [
            'items' => $transformedItems
        ]);
    }

    /**
     * Validate item data
     */
    private function validateItemData(array $data, array $rules): array
    {
        $errors = [];

        if (empty($data['itemtypeid'])) {
            $errors[] = 'Item type is required.';
        }

        if (empty($data['status'])) {
            $errors[] = 'Item status is required.';
        }

        // Check for duplicate serial number if provided
        if (!empty($data['sn'])) {
            if ($this->itemModel->serialNumberExists($data['sn'])) {
                $errors[] = 'Serial number already exists.';
            }
        }

        // Check for duplicate asset tag if provided
        if (!empty($data['label'])) {
            if ($this->itemModel->labelExists($data['label'])) {
                $errors[] = 'Asset tag already exists.';
            }
        }

        return $errors;
    }

    /**
     * Check if user can create items
     */
    private function canUserCreateItems($user): bool
    {
        return $user && $user->usertype >= 1;
    }

    /**
     * Check if user can edit item
     */
    private function canUserEditItem($user, $item): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can edit all items
        if ($user->isAdmin()) {
            return true;
        }

        // Users can edit items assigned to them
        if ($user->usertype >= 1 && ($item['userid'] ?? null) === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can delete item
     */
    private function canUserDeleteItem($user, $item): bool
    {
        // Only admins can delete items
        return $user && $user->isAdmin();
    }

    /**
     * Manage item associations (add/remove)
     */
    public function manageAssociations(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $item = $this->itemModel->find($id);
        if (!$item) {
            return $this->json($response, ['error' => 'Item not found'], 404);
        }

        if (!$this->canUserEditItem($user, $item)) {
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

        if (!in_array($type, ['software', 'invoice', 'contract', 'file', 'tag', 'item']) || !$itemId || !in_array($action, ['add', 'remove'])) {
            return $this->json($response, ['error' => 'Invalid parameters'], 400);
        }

        try {
            $success = false;
            $responseData = ['success' => false];

            switch ($type) {
                case 'software':
                    if ($action === 'add') {
                        $success = $this->itemModel->associateSoftware($id, $itemId);
                    } else {
                        $success = $this->itemModel->dissociateSoftware($id, $itemId);
                    }
                    break;

                case 'invoice':
                    if ($action === 'add') {
                        $success = $this->itemModel->associateInvoice($id, $itemId);
                    } else {
                        $success = $this->itemModel->dissociateInvoice($id, $itemId);
                    }
                    break;

                case 'contract':
                    if ($action === 'add') {
                        $success = $this->itemModel->associateContract($id, $itemId);
                    } else {
                        $success = $this->itemModel->dissociateContract($id, $itemId);
                    }
                    break;

                case 'file':
                    if ($action === 'add') {
                        $success = $this->itemModel->associateFile($id, $itemId);
                    } else {
                        $success = $this->itemModel->dissociateFile($id, $itemId);
                    }
                    break;

                case 'tag':
                    if ($action === 'add') {
                        $success = $this->itemModel->associateTag($id, $itemId);
                    } else {
                        $success = $this->itemModel->dissociateTag($id, $itemId);
                    }
                    break;

                case 'item':
                    if ($action === 'add') {
                        $success = $this->itemModel->associateItem($id, $itemId);
                    } else {
                        $success = $this->itemModel->dissociateItem($id, $itemId);
                    }
                    break;
            }

            $responseData['success'] = $success;

            // Handle failed associations with appropriate HTTP status
            if (!$success) {
                if ($action === 'add') {
                    return $this->json($response, [
                        'success' => false,
                        'error' => "This {$type} is already associated with this item"
                    ], 409); // 409 Conflict for duplicates
                } else {
                    return $this->json($response, [
                        'success' => false,
                        'error' => "Failed to remove {$type} association"
                    ], 400); // 400 Bad Request for other failures
                }
            }

            if ($success && $action === 'add') {
                // Get the newly added item data for UI update
                switch ($type) {
                    case 'software':
                        $sql = "SELECT s.id, s.stitle as name, s.sversion as version,
                                       a.title as manufacturer_name
                                FROM software s
                                LEFT JOIN agents a ON s.manufacturerid = a.id
                                WHERE s.id = ?";
                        break;

                    case 'item':
                        $sql = "SELECT i.id, i.label, i.function, it.name as itemtype_name,
                                       st.statusdesc as status_name,
                                       l.name as location_name, u.username
                                FROM items i
                                LEFT JOIN itemtypes it ON i.itemtypeid = it.id
                                LEFT JOIN statustypes st ON i.status = st.id
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
                                       f.uploaddate, ft.typedesc as filetype_name
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
                            $itemData['date_formatted'] = $itemData['date'] ? date('Y-m-d', (int)$itemData['date']) : 'N/A';
                            $itemData['total_formatted'] = number_format($itemData['totalcost'] ?? 0, 2);
                        } elseif ($type === 'contract') {
                            $itemData['startdate'] = $itemData['startdate'] ? date('Y-m-d', (int)$itemData['startdate']) : 'N/A';
                            $itemData['enddate'] = $itemData['enddate'] ? date('Y-m-d', (int)$itemData['enddate']) : 'N/A';
                        } elseif ($type === 'file') {
                            $itemData['uploaddate_formatted'] = $itemData['uploaddate'] ? date('Y-m-d', (int)$itemData['uploaddate']) : 'N/A';
                        }

                        $responseData['data'] = $itemData;
                    }
                }
            }

            return $this->json($response, $responseData);

        } catch (\Exception $e) {
            $this->logger->error('Failed to manage item association', [
                'item_id' => $id,
                'type' => $type,
                'target_id' => $itemId,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return $this->json($response, ['error' => 'Failed to update association'], 500);
        }
    }

    /**
     * Create a new tag and add it to item
     */
    private function createAndAddTag(Request $request, Response $response, int $itemId, array $data): Response
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
                // Tag exists, just associate it
                $tagId = $existingTag['id'];
                $success = $this->itemModel->associateTag($itemId, $tagId);

                if ($success) {
                    // Get the tag data for response
                    $sql = "SELECT id, name, color FROM tags WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$tagId]);
                    $tagData = $stmt->fetch(\PDO::FETCH_ASSOC);

                    return $this->json($response, [
                        'success' => true,
                        'data' => $tagData,
                        'message' => 'Existing tag added successfully'
                    ]);
                }

                return $this->json($response, ['error' => 'Tag already associated with this item'], 400);
            }

            // Create new tag
            $sql = "INSERT INTO tags (name, color) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tagName, $tagColor]);
            $tagId = $this->pdo->lastInsertId();

            // Associate the new tag with the item
            $success = $this->itemModel->associateTag($itemId, $tagId);

            if ($success) {
                return $this->json($response, [
                    'success' => true,
                    'data' => [
                        'id' => $tagId,
                        'name' => $tagName,
                        'color' => $tagColor
                    ],
                    'message' => 'New tag created and added successfully'
                ]);
            }

            return $this->json($response, ['error' => 'Failed to associate tag with item'], 500);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create and add tag to item', [
                'item_id' => $itemId,
                'tag_name' => $tagName,
                'error' => $e->getMessage()
            ]);
            return $this->json($response, ['error' => 'Failed to create tag'], 500);
        }
    }
}