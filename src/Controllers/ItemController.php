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
            $filters['status_id'] = $queryParams['status'];
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
            'item_type_id' => 'required|integer',
            'status_id' => 'required|integer',
            'function' => 'string|max:255',
            'model' => 'string|max:100',
            'serial_number' => 'string|max:100',
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
                'item_type_id' => !empty($data['item_type_id']) ? (int) $data['item_type_id'] : null,
                'status_id' => (int) $data['status_id'],
                'model' => $this->sanitizeString($data['model'] ?? ''),
                'serial_number' => $this->sanitizeString($data['serial_number'] ?? ''),
                'label' => $this->sanitizeString($data['label'] ?? ''),
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'maintenance_info' => $this->sanitizeString($data['maintenance_info'] ?? ''),
                'user_id' => !empty($data['user_id']) ? (int) $data['user_id'] : null,
                'location_id' => !empty($data['location_id']) ? (int) $data['location_id'] : null,
                'ipv4_address' => $this->sanitizeString($data['ipv4_address'] ?? ''),
                'dns_name' => $this->sanitizeString($data['dns_name'] ?? ''),
                'cpu' => $this->sanitizeString($data['cpu'] ?? ''),
                'ram' => $this->sanitizeString($data['ram'] ?? ''),
                'hard_drive' => $this->sanitizeString($data['hard_drive'] ?? ''),
            ];

            // Handle purchase information
            if (!empty($data['purchase_date'])) {
                $itemData['purchase_date'] = strtotime($data['purchase_date']);
            }
            if (!empty($data['warranty_months'])) {
                $itemData['warranty_months'] = (int) $data['warranty_months'];
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
            // Validate required fields
            if (empty($data['item_type_id'])) {
                throw new \Exception('The item_type_id field is required and cannot be empty.');
            }

            // Prepare data for update using correct database field names
            $updateData = [
                'function' => $this->sanitizeString($data['function'] ?? ''),
                'item_type_id' => (int) $data['item_type_id'],
                'status_id' => (int) ($data['status_id'] ?? 0),
                'manufacturer_id' => !empty($data['manufacturer_id']) ? (int) $data['manufacturer_id'] : null,
                'model' => $this->sanitizeString($data['model'] ?? ''),
                'serial_number' => $this->sanitizeString($data['serial_number'] ?? ''),
                'label' => $this->sanitizeString($data['label'] ?? ''),
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'maintenance_info' => $this->sanitizeString($data['maintenance_info'] ?? ''),
                'user_id' => !empty($data['user_id']) ? (int) $data['user_id'] : null,
                'location_id' => !empty($data['location_id']) ? (int) $data['location_id'] : null,
                'ipv4_address' => $this->sanitizeString($data['ipv4_address'] ?? ''),
                'dns_name' => $this->sanitizeString($data['dns_name'] ?? ''),
                'cpu' => $this->sanitizeString($data['cpu'] ?? ''),
                'ram' => $this->sanitizeString($data['ram'] ?? ''),
                'hard_drive' => $this->sanitizeString($data['hard_drive'] ?? ''),
                'is_rack_mountable' => !empty($data['is_rack_mountable']) ? 1 : 0,
                'rack_id' => !empty($data['rack_id']) ? (int) $data['rack_id'] : null,
                'rack_position' => !empty($data['rack_position']) ? (int) $data['rack_position'] : null,
                'rack_position_depth' => !empty($data['rack_position_depth']) ? (int) $data['rack_position_depth'] : null,
                'origin' => $this->sanitizeString($data['origin'] ?? ''),
                'purchase_price' => $this->sanitizeString($data['purchase_price'] ?? ''),
            ];

            // Handle purchase information
            if (!empty($data['purchase_date'])) {
                $updateData['purchase_date'] = strtotime($data['purchase_date']);
            }
            if (!empty($data['warranty_months'])) {
                $updateData['warranty_months'] = (int) $data['warranty_months'];
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
                'serial_number' => $item['serial_number'],
                'function' => $item['function'], // description/title
                'itemType' => [
                    'id' => $item['item_type_id'] ?? null,
                    'name' => $item['itemtype_name'] ?? 'Unknown Type'
                ],
                'location' => [
                    'id' => $item['location_id'] ?? null,
                    'name' => $item['location_name'] ?? 'No Location'
                ],
                'user' => [
                    'id' => $item['user_id'] ?? null,
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

        if (empty($data['item_type_id'])) {
            $errors[] = 'Item type is required.';
        }

        if (empty($data['status_id'])) {
            $errors[] = 'Item status is required.';
        }

        // Check for duplicate serial number if provided
        if (!empty($data['serial_number'])) {
            if ($this->itemModel->serialNumberExists($data['serial_number'])) {
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
        if ($user->usertype >= 1 && ($item['user_id'] ?? null) === $user->id) {
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
                        $sql = "SELECT s.id, s.title as name, s.version as version,
                                       a.name as manufacturer_name, lt.name as license_type
                                FROM software s
                                LEFT JOIN agents a ON s.manufacturer_id = a.id
                                LEFT JOIN license_types lt ON s.license_type_id = lt.id
                                WHERE s.id = ?";
                        break;

                    case 'item':
                        $sql = "SELECT i.id, i.label, i.function, it.name as itemtype_name,
                                       st.name as status_name,
                                       l.name as location_name, u.username, a.name as manufacturer_name
                                FROM items i
                                LEFT JOIN item_types it ON i.item_type_id = it.id
                                LEFT JOIN status_types st ON i.status_id = st.id
                                LEFT JOIN locations l ON i.location_id = l.id
                                LEFT JOIN users u ON i.user_id = u.id
                                LEFT JOIN agents a ON i.manufacturer_id = a.id
                                WHERE i.id = ?";
                        break;

                    case 'invoice':
                        $sql = "SELECT i.id, i.number, i.invoice_date, i.total_cost, i.comments,
                                       a.name as vendor_title
                                FROM invoices i
                                LEFT JOIN agents a ON i.vendor_id = a.id
                                WHERE i.id = ?";
                        break;

                    case 'contract':
                        $sql = "SELECT c.id, c.title, c.contract_number, c.start_date, c.end_date as enddate,
                                       a.name as contractor_name
                                FROM contracts c
                                LEFT JOIN agents a ON c.contractor_id = a.id
                                WHERE c.id = ?";
                        break;

                    case 'file':
                        $sql = "SELECT f.id, f.filename_stored, f.title, f.file_size as file_size,
                                       f.uploaded_at, ft.name as filetype_name
                                FROM files f
                                LEFT JOIN file_types ft ON f.file_type_id = ft.id
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
                            $itemData['date_formatted'] = $itemData['invoice_date'] ? date('Y-m-d', (int)$itemData['invoice_date']) : 'N/A';
                            $itemData['total_formatted'] = number_format($itemData['total_cost'] ?? 0, 2);
                        } elseif ($type === 'contract') {
                            $itemData['start_date'] = $itemData['start_date'] ? date('Y-m-d', (int)$itemData['start_date']) : 'N/A';
                            $itemData['end_date'] = $itemData['end_date'] ? date('Y-m-d', (int)$itemData['end_date']) : 'N/A';
                        } elseif ($type === 'file') {
                            $itemData['uploaded_at_formatted'] = $itemData['uploaded_at'] ? date('Y-m-d', (int)$itemData['uploaded_at']) : 'N/A';
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