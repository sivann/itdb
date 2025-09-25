<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\InvoiceModel;
use App\Models\AgentModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class InvoiceController extends BaseController
{
    private AuthService $authService;
    private InvoiceModel $invoiceModel;
    private AgentModel $agentModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        InvoiceModel $invoiceModel,
        AgentModel $agentModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->invoiceModel = $invoiceModel;
        $this->agentModel = $agentModel;
    }

    /**
     * List all invoices
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
        if (!empty($queryParams['vendor'])) {
            $filters['vendor'] = $queryParams['vendor'];
        }
        if (!empty($queryParams['date_from'])) {
            $filters['date_from'] = $queryParams['date_from'];
        }
        if (!empty($queryParams['date_to'])) {
            $filters['date_to'] = $queryParams['date_to'];
        }

        // Pagination
        $page = (int) ($queryParams['page'] ?? 1);
        $perPage = (int) ($queryParams['per_page'] ?? 25);
        $perPage = min(100, max(10, $perPage)); // Limit between 10 and 100

        // Get paginated invoices
        $result = $this->invoiceModel->getPaginated($page, $perPage, $filters);

        // Get vendors for filter
        $vendors = $this->agentModel->getVendors();

        return $this->render($response, 'invoices/index.twig', [
            'invoices' => $result['data'],
            'filters' => [
                'vendors' => $vendors,
            ],
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
     * Show invoice details (redirects to edit)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $queryParams = $this->getQueryParams($request);

        // Preserve query parameters when redirecting
        $queryString = http_build_query($queryParams);
        $redirectUrl = '/invoices/' . $id . '/edit' . ($queryString ? '?' . $queryString : '');

        return $response->withStatus(302)->withHeader('Location', $redirectUrl);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Check if user can create invoices
        if (!$user || !$this->canUserCreateInvoices($user)) {
            $this->addFlashMessage('error', 'You do not have permission to create invoices.');
            return $this->redirectToRoute($request, $response, 'invoices.index');
        }

        // Get form options - only vendors
        $vendors = $this->agentModel->getVendors();

        return $this->render($response, 'invoices/create.twig', [
            'form_options' => [
                'vendors' => $vendors,
            ],
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new invoice
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || !$this->canUserCreateInvoices($user)) {
            $this->addFlashMessage('error', 'You do not have permission to create invoices.');
            return $this->redirectToRoute($request, $response, 'invoices.index');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'invoices.create');
        }

        $data = $this->getParsedBody($request);

        // Basic validation
        if (empty($data['vendorid'])) {
            $this->addFlashMessage('error', 'Vendor is required.');
            return $this->redirectToRoute($request, $response, 'invoices.create');
        }

        try {
            // Prepare data for creation - map to actual DB schema
            $invoiceData = [
                'vendorid' => !empty($data['vendorid']) ? (int) $data['vendorid'] : null,
                'buyerid' => !empty($data['buyerid']) ? (int) $data['buyerid'] : null,
                'totalcost' => !empty($data['totalcost']) ? (float) $data['totalcost'] : 0.00,
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'date' => !empty($data['date']) ? strtotime($data['date']) : time(),
            ];

            $invoiceId = $this->invoiceModel->create($invoiceData);

            $this->logUserAction('invoice_created', ['invoice_id' => $invoiceId]);
            $this->addFlashMessage('success', 'Invoice created successfully.');

            return $this->redirectToRoute($request, $response, 'invoices.edit', ['id' => $invoiceId]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create invoice', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to create invoice. Please try again.');
            return $this->redirectToRoute($request, $response, 'invoices.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $invoice = $this->invoiceModel->find($id);
        if (!$invoice) {
            $this->addFlashMessage('error', 'Invoice not found.');
            return $this->redirectToRoute($request, $response, 'invoices.index');
        }

        if (!$this->canUserEditInvoice($user, $invoice)) {
            $this->addFlashMessage('error', 'You do not have permission to edit this invoice.');
            return $this->redirectToRoute($request, $response, 'invoices.edit', ['id' => $id]);
        }

        // Get form options - only vendors
        $vendors = $this->agentModel->getVendors();

        return $this->render($response, 'invoices/edit.twig', [
            'invoice' => $invoice,
            'form_options' => [
                'vendors' => $vendors,
            ],
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update invoice
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $invoice = $this->invoiceModel->find($id);
        if (!$invoice) {
            $this->addFlashMessage('error', 'Invoice not found.');
            return $this->redirectToRoute($request, $response, 'invoices.index');
        }

        if (!$this->canUserEditInvoice($user, $invoice)) {
            $this->addFlashMessage('error', 'You do not have permission to edit this invoice.');
            return $this->redirectToRoute($request, $response, 'invoices.edit', ['id' => $id]);
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'invoices.edit', ['id' => $id]);
        }

        $data = $this->getParsedBody($request);

        try {
            // Prepare data for update - map to actual DB schema
            $updateData = [
                'vendorid' => !empty($data['vendorid']) ? (int) $data['vendorid'] : null,
                'buyerid' => !empty($data['buyerid']) ? (int) $data['buyerid'] : null,
                'totalcost' => !empty($data['totalcost']) ? (float) $data['totalcost'] : 0.00,
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'date' => !empty($data['date']) ? strtotime($data['date']) : null,
            ];

            $this->invoiceModel->update($id, $updateData);

            $this->logUserAction('invoice_updated', ['invoice_id' => $id]);
            $this->addFlashMessage('success', 'Invoice updated successfully.');

            return $this->redirectToRoute($request, $response, 'invoices.edit', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->addFlashMessage('error', 'Failed to update invoice. Please try again.');
            return $this->redirectToRoute($request, $response, 'invoices.edit', ['id' => $id]);
        }
    }

    /**
     * Delete invoice
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->authService->getCurrentUser();

        $invoice = $this->invoiceModel->find($id);
        if (!$invoice) {
            $this->addFlashMessage('error', 'Invoice not found.');
            return $this->redirectToRoute($request, $response, 'invoices.index');
        }

        if (!$this->canUserDeleteInvoice($user, $invoice)) {
            $this->addFlashMessage('error', 'You do not have permission to delete this invoice.');
            return $this->redirectToRoute($request, $response, 'invoices.edit', ['id' => $id]);
        }

        try {
            $this->invoiceModel->delete($id);

            $this->logUserAction('invoice_deleted', ['invoice_id' => $id]);
            $this->addFlashMessage('success', 'Invoice deleted successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlashMessage('error', 'Failed to delete invoice. Please try again.');
        }

        return $this->redirectToRoute($request, $response, 'invoices.index');
    }

    /**
     * Check if user can create invoices
     */
    private function canUserCreateInvoices($user): bool
    {
        return $user && $user->usertype >= 1;
    }

    /**
     * Check if user can edit invoice
     */
    private function canUserEditInvoice($user, $invoice): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can edit all invoices
        if ($user->isAdmin()) {
            return true;
        }

        // Users with usertype >= 2 can edit invoices
        return $user->usertype >= 2;
    }

    /**
     * Check if user can delete invoice
     */
    private function canUserDeleteInvoice($user, $invoice): bool
    {
        // Only admins can delete invoices
        return $user && $user->isAdmin();
    }

    /**
     * API endpoint for searching invoices
     */
    public function search(Request $request, Response $response): Response
    {
        $queryParams = $this->getQueryParams($request);
        $query = $queryParams['q'] ?? '';
        $limit = min(20, max(1, (int) ($queryParams['limit'] ?? 20)));

        // Search invoices using model
        $invoices = [];
        if (!empty($query)) {
            // Simple search for now - could be enhanced in InvoiceModel
            $invoices = $this->invoiceModel->getPaginated(1, $limit, ['search' => $query])['data'];
        }

        // Format results for frontend
        $results = [];
        foreach ($invoices as $invoice) {
            $results[] = [
                'id' => $invoice['id'],
                'display' => sprintf('ID: %d, Invoice',
                    $invoice['id']
                ),
                'vendor' => $invoice['vendor_name'] ?? null,
                'total' => $invoice['totalcost'] ? number_format($invoice['totalcost'], 2) : null,
                'date' => $invoice['date'] ? date('Y-m-d', $invoice['date']) : null
            ];
        }

        return $this->json($response, [
            'success' => true,
            'invoices' => $results
        ]);
    }
}