<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ItemModel;
use App\Models\SoftwareModel;
use App\Models\InvoiceModel;
use App\Models\ContractModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ReportController extends BaseController
{
    private AuthService $authService;
    private ItemModel $itemModel;
    private SoftwareModel $softwareModel;
    private InvoiceModel $invoiceModel;
    private ContractModel $contractModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        ItemModel $itemModel,
        SoftwareModel $softwareModel,
        InvoiceModel $invoiceModel,
        ContractModel $contractModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->itemModel = $itemModel;
        $this->softwareModel = $softwareModel;
        $this->invoiceModel = $invoiceModel;
        $this->contractModel = $contractModel;
    }

    /**
     * Reports dashboard
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get basic statistics
        $stats = [
            'total_items' => $this->itemModel->getCount(),
            'total_software' => $this->softwareModel->getCount(),
            'total_invoices' => $this->invoiceModel->getCount(),
            'total_contracts' => $this->contractModel->getCount(),
        ];

        return $this->render($response, 'reports/index.twig', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Items report
     */
    public function items(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);

        // Get items with filtering
        $query = Item::with(['location', 'user']);

        // Apply filters based on query parameters
        if (!empty($queryParams['status'])) {
            $query->where('status', $queryParams['status']);
        }

        if (!empty($queryParams['location'])) {
            $query->where('locationid', $queryParams['location']);
        }

        $items = $query->orderBy('id', 'desc')->get();

        return $this->render($response, 'reports/items.twig', [
            'user' => $user,
            'items' => $items,
            'filters' => $queryParams,
        ]);
    }

    /**
     * Financial report
     */
    public function financial(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get financial data
        $totalValue = Item::sum('price');
        $invoiceStats = [
            'total_invoices' => Invoice::count(),
            'total_amount' => Invoice::sum('amount'),
            'pending_invoices' => Invoice::where('status', 'pending')->count(),
        ];

        return $this->render($response, 'reports/financial.twig', [
            'user' => $user,
            'total_value' => $totalValue,
            'invoice_stats' => $invoiceStats,
        ]);
    }

    /**
     * Utilization report
     */
    public function utilization(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get utilization statistics
        $utilizationStats = [
            'total_items' => Item::count(),
            'assigned_items' => Item::whereNotNull('userid')->count(),
            'unassigned_items' => Item::whereNull('userid')->count(),
            'active_items' => Item::where('status', 1)->count(),
            'inactive_items' => Item::where('status', 0)->count(),
        ];

        return $this->render($response, 'reports/utilization.twig', [
            'user' => $user,
            'stats' => $utilizationStats,
        ]);
    }

    /**
     * Contracts report
     */
    public function contracts(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Get contract statistics
        $contractStats = [
            'total_contracts' => Contract::count(),
            'active_contracts' => Contract::where('status', 'active')->count(),
            'expired_contracts' => Contract::where('enddate', '<', date('Y-m-d'))->count(),
            'expiring_soon' => Contract::whereBetween('enddate', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])->count(),
        ];

        // Get contracts expiring soon
        $expiringContracts = Contract::whereBetween('enddate', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])
                                   ->orderBy('enddate')
                                   ->limit(10)
                                   ->get();

        return $this->render($response, 'reports/contracts.twig', [
            'user' => $user,
            'stats' => $contractStats,
            'expiring_contracts' => $expiringContracts,
        ]);
    }
}