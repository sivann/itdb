<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContractModel;
use App\Models\ContractTypeModel;
use App\Models\AgentModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ContractController extends BaseController
{
    private AuthService $authService;
    private ContractModel $contractModel;
    private ContractTypeModel $contractTypeModel;
    private AgentModel $agentModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        ContractModel $contractModel,
        ContractTypeModel $contractTypeModel,
        AgentModel $agentModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->contractModel = $contractModel;
        $this->contractTypeModel = $contractTypeModel;
        $this->agentModel = $agentModel;
    }

    /**
     * List all contracts
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
        if (!empty($queryParams['contractor'])) {
            $filters['contractor'] = $queryParams['contractor'];
        }
        if (!empty($queryParams['status'])) {
            $filters['status'] = $queryParams['status'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = 20;

        // Get paginated contracts
        $result = $this->contractModel->getPaginated($page, $perPage, $filters);

        // Get contractors for filter dropdown
        $contractorIds = $this->contractModel->getContractorIds();
        $contractors = [];
        if (!empty($contractorIds)) {
            foreach ($contractorIds as $id) {
                $contractor = $this->agentModel->find($id);
                if ($contractor) {
                    $contractors[] = $contractor;
                }
            }
        }

        return $this->render($response, 'contracts/index.twig', [
            'user' => $user,
            'contracts' => $result['data'],
            'pagination' => [
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'last_page' => $result['total_pages'],
                'from' => ($result['page'] - 1) * $result['per_page'] + 1,
                'to' => min($result['page'] * $result['per_page'], $result['total'])
            ],
            'query' => $queryParams,
            'contractors' => $contractors,
        ]);
    }

    /**
     * Show contract details (redirects to edit)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $queryParams = $this->getQueryParams($request);

        // Preserve query parameters when redirecting
        $queryString = http_build_query($queryParams);
        $redirectUrl = '/contracts/' . $id . '/edit' . ($queryString ? '?' . $queryString : '');

        return $response->withStatus(302)->withHeader('Location', $redirectUrl);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get available parent contracts
        $parentContracts = $this->contractModel->getParentContracts();

        // Get contract types
        $contractTypes = $this->contractTypeModel->getAll();

        // Get contractors and vendors
        $contractors = $this->agentModel->getContractors();
        $vendors = $this->agentModel->getVendors();

        return $this->render($response, 'contracts/edit.twig', [
            'mode' => 'create',
            'user' => $user,
            'form_options' => [
                'contracts' => $parentContracts,
                'contract_types' => $contractTypes,
            ],
            'contractors' => $contractors,
            'vendors' => $vendors,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Store new contract
     */
    public function store(Request $request, Response $response): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'contracts.create');
        }

        $data = $this->getParsedBody($request);

        // Validation
        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }

        if (!empty($data['parentid']) && !$this->contractModel->find($data['parentid'])) {
            $errors[] = 'Invalid parent contract';
        }

        if (!empty($data['contractorid']) && !$this->agentModel->find($data['contractorid'])) {
            $errors[] = 'Invalid contractor';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $contractData = [
                'type' => !empty($data['type']) ? (int) $data['type'] : null,
                'parentid' => !empty($data['parentid']) ? (int) $data['parentid'] : null,
                'title' => $this->sanitizeString($data['title']),
                'number' => $this->sanitizeString($data['number'] ?? ''),
                'description' => $this->sanitizeString($data['description'] ?? ''),
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'totalcost' => !empty($data['totalcost']) ? (float) $data['totalcost'] : null,
                'contractorid' => !empty($data['contractorid']) ? (int) $data['contractorid'] : null,
                'vendorid' => !empty($data['vendorid']) ? (int) $data['vendorid'] : null,
                'startdate' => !empty($data['startdate']) ? strtotime($data['startdate']) : null,
                'currentenddate' => !empty($data['currentenddate']) ? strtotime($data['currentenddate']) : null,
                'renewals' => $this->sanitizeString($data['renewals'] ?? ''),
                'subtype' => !empty($data['subtype']) ? (int) $data['subtype'] : null,
            ];

            $contractId = $this->contractModel->create($contractData);

            $this->logUserAction('contract_created', ['contract_id' => $contractId]);
            $this->addFlashMessage('success', 'Contract created successfully');
            return $this->redirectToRoute($request, $response, 'contracts.edit', ['id' => $contractId]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating contract', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error creating contract');
            return $this->redirectToRoute($request, $response, 'contracts.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            $this->addFlashMessage('error', 'Contract not found');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        // Get available parent contracts
        $parentContracts = $this->contractModel->getParentContracts($id);

        // Get contract types
        $contractTypes = $this->contractTypeModel->getAll();

        // Get contractors and vendors
        $contractors = $this->agentModel->getContractors();
        $vendors = $this->agentModel->getVendors();

        return $this->render($response, 'contracts/edit.twig', [
            'mode' => 'edit',
            'user' => $user,
            'contract' => $contract,
            'form_options' => [
                'contracts' => $parentContracts,
                'contract_types' => $contractTypes,
            ],
            'contractors' => $contractors,
            'vendors' => $vendors,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update contract
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        $id = (int) $args['id'];
        $data = $this->getParsedBody($request);

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            $this->addFlashMessage('error', 'Contract not found');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        // Validation
        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }

        if (!empty($data['parentid'])) {
            if ($data['parentid'] == $id) {
                $errors[] = 'Contract cannot be its own parent';
            } elseif (!$this->contractModel->find($data['parentid'])) {
                $errors[] = 'Invalid parent contract';
            }
        }

        if (!empty($data['contractorid']) && !$this->agentModel->find($data['contractorid'])) {
            $errors[] = 'Invalid contractor';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $updateData = [
                'type' => !empty($data['type']) ? (int) $data['type'] : null,
                'parentid' => !empty($data['parentid']) ? (int) $data['parentid'] : null,
                'title' => $this->sanitizeString($data['title']),
                'number' => $this->sanitizeString($data['number'] ?? ''),
                'description' => $this->sanitizeString($data['description'] ?? ''),
                'comments' => $this->sanitizeString($data['comments'] ?? ''),
                'totalcost' => !empty($data['totalcost']) ? (float) $data['totalcost'] : null,
                'contractorid' => !empty($data['contractorid']) ? (int) $data['contractorid'] : null,
                'vendorid' => !empty($data['vendorid']) ? (int) $data['vendorid'] : null,
                'startdate' => !empty($data['startdate']) ? strtotime($data['startdate']) : null,
                'currentenddate' => !empty($data['currentenddate']) ? strtotime($data['currentenddate']) : null,
                'renewals' => $this->sanitizeString($data['renewals'] ?? ''),
                'subtype' => !empty($data['subtype']) ? (int) $data['subtype'] : null,
            ];

            $this->contractModel->update($id, $updateData);

            $this->logUserAction('contract_updated', ['contract_id' => $id]);
            $this->addFlashMessage('success', 'Contract updated successfully');
            return $this->redirectToRoute($request, $response, 'contracts.edit', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating contract', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating contract');
            return $this->redirectToRoute($request, $response, 'contracts.edit', ['id' => $id]);
        }
    }

    /**
     * Delete contract
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        $id = (int) $args['id'];

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            $this->addFlashMessage('error', 'Contract not found');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        // Check if contract has children
        if ($this->contractModel->hasChildren($id)) {
            $this->addFlashMessage('error', 'Cannot delete contract with sub-contracts. Delete sub-contracts first.');
            return $this->redirectToRoute($request, $response, 'contracts.edit', ['id' => $id]);
        }

        try {
            $this->logUserAction('contract_deleted', ['contract_id' => $contract['id'], 'title' => $contract['title']]);
            $this->contractModel->delete($id);

            $this->addFlashMessage('success', 'Contract deleted successfully');
            return $this->redirectToRoute($request, $response, 'contracts.index');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting contract', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting contract');
            return $this->redirectToRoute($request, $response, 'contracts.edit', ['id' => $id]);
        }
    }

    /**
     * Show contract renewal form
     */
    public function renew(Request $request, Response $response, array $args): Response
    {
        $user = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            $this->addFlashMessage('error', 'Contract not found');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        return $this->render($response, 'contracts/renew.twig', [
            'user' => $user,
            'contract' => $contract,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Process contract renewal
     */
    public function processRenewal(Request $request, Response $response, array $args): Response
    {
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        $id = (int) $args['id'];
        $data = $this->getParsedBody($request);

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            $this->addFlashMessage('error', 'Contract not found');
            return $this->redirectToRoute($request, $response, 'contracts.index');
        }

        // Validation
        $errors = [];
        if (empty($data['new_end_date'])) {
            $errors[] = 'New end date is required';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $newEndDate = strtotime($data['new_end_date']);
            $renewalNote = $this->sanitizeString($data['renewal_notes'] ?? '');

            // Update current end date
            $renewals = $contract['renewals'] ? $contract['renewals'] . "\n" . $renewalNote : $renewalNote;
            $this->contractModel->update($id, [
                'currentenddate' => $newEndDate,
                'renewals' => $renewals,
            ]);

            $this->logUserAction('contract_renewed', [
                'contract_id' => $contract['id'],
                'new_end_date' => date('Y-m-d', $newEndDate)
            ]);

            $this->addFlashMessage('success', 'Contract renewed successfully');
            return $this->redirectToRoute($request, $response, 'contracts.edit', ['id' => $contract['id']]);

        } catch (\Exception $e) {
            $this->logger->error('Error renewing contract', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error renewing contract');
            return $this->redirectToRoute($request, $response, 'contracts.renew', ['id' => $id]);
        }
    }

    /**
     * API endpoint for searching contracts
     */
    public function search(Request $request, Response $response): Response
    {
        $queryParams = $this->getQueryParams($request);
        $query = $queryParams['q'] ?? '';
        $limit = min(20, max(1, (int) ($queryParams['limit'] ?? 20)));

        // Search contracts
        $contracts = $this->contractModel->search($query, $limit);

        // Format results for frontend
        $results = [];
        foreach ($contracts as $contract) {
            $results[] = [
                'id' => $contract['id'],
                'title' => $contract['title'] ?: 'Untitled Contract',
                'display' => sprintf('ID: %d, %s',
                    $contract['id'],
                    $contract['title'] ?: 'Untitled Contract'
                ),
                'number' => $contract['number'],
                'contractor' => [
                    'name' => $contract['contractor_name'] ?? null
                ],
                'startdate' => $contract['startdate'] ? date('Y-m-d', $contract['startdate']) : null,
                'enddate' => $contract['currentenddate'] ? date('Y-m-d', $contract['currentenddate']) : null,
                'start_date' => $contract['startdate'] ? date('Y-m-d', $contract['startdate']) : null,
                'end_date' => $contract['currentenddate'] ? date('Y-m-d', $contract['currentenddate']) : null
            ];
        }

        return $this->json($response, [
            'success' => true,
            'contracts' => $results
        ]);
    }
}