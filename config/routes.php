<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\ItemController;
use App\Controllers\SoftwareController;
use App\Controllers\ContractController;
use App\Controllers\InvoiceController;
use App\Controllers\FileController;
use App\Controllers\UserController;
use App\Controllers\AgentController;
use App\Controllers\LocationController;
use App\Controllers\RackController;
use App\Controllers\ReportController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\ItemTypeController;
use App\Controllers\StatusTypeController;
use App\Controllers\ContractTypeController;
use App\Controllers\FileTypeController;
use App\Controllers\LicenseTypeController;
use App\Controllers\AuditLogController;
use App\Controllers\AgentTypeController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Static routes (no authentication required)
    $app->get('/favicon.ico', function ($request, $response) {
        // Serve favicon or return 204 No Content if not found
        $faviconPath = __DIR__ . '/../public/favicon.ico';
        if (file_exists($faviconPath)) {
            $response->getBody()->write(file_get_contents($faviconPath));
            return $response->withHeader('Content-Type', 'image/x-icon');
        }
        return $response->withStatus(204);
    });

    // Public routes (no authentication required)
    $app->group('', function (RouteCollectorProxy $group) {
        // Authentication routes
        $group->get('/login', [AuthController::class, 'showLogin'])->setName('login');
        $group->post('/login', [AuthController::class, 'login']);
        $group->post('/logout', [AuthController::class, 'logout'])->setName('logout');

        // About page
        $group->get('/about', [DashboardController::class, 'about'])->setName('about');
    });

    // Protected routes (authentication required)
    $app->group('', function (RouteCollectorProxy $group) {
        // Dashboard
        $group->get('/', [DashboardController::class, 'index'])->setName('dashboard');
        $group->get('/home', [DashboardController::class, 'index'])->setName('home');

        // Profile
        $group->get('/profile', [AuthController::class, 'profile'])->setName('profile');
        $group->post('/profile', [AuthController::class, 'updateProfile'])->setName('profile.update');

        // Items management
        $group->group('/items', function (RouteCollectorProxy $group) {
            $group->get('', [ItemController::class, 'index'])->setName('items.index');
            $group->get('/search', [ItemController::class, 'search'])->setName('items.search');
            $group->get('/create', [ItemController::class, 'create'])->setName('items.create');
            $group->get('/export/{format}', [ItemController::class, 'export'])->setName('items.export');
            $group->post('', [ItemController::class, 'store'])->setName('items.store');
            $group->get('/{id}', [ItemController::class, 'show'])->setName('items.show');
            $group->get('/{id}/edit', [ItemController::class, 'edit'])->setName('items.edit');
            $group->get('/{id}/history', [ItemController::class, 'history'])->setName('items.history');
            $group->post('/{id}', [ItemController::class, 'update'])->setName('items.update');
            $group->post('/{id}/delete', [ItemController::class, 'delete'])->setName('items.delete');
        });

        // Software management
        $group->group('/software', function (RouteCollectorProxy $group) {
            $group->get('', [SoftwareController::class, 'index'])->setName('software.index');
            $group->get('/create', [SoftwareController::class, 'create'])->setName('software.create');
            $group->post('', [SoftwareController::class, 'store'])->setName('software.store');
            $group->get('/{id}', [SoftwareController::class, 'show'])->setName('software.show');
            $group->get('/{id}/edit', [SoftwareController::class, 'edit'])->setName('software.edit');
            $group->post('/{id}', [SoftwareController::class, 'update'])->setName('software.update');
            $group->post('/{id}/delete', [SoftwareController::class, 'delete'])->setName('software.delete');
            // Association management
            $group->post('/{id}/associations', [SoftwareController::class, 'manageAssociations'])->setName('software.associations.add');
            $group->delete('/{id}/associations', [SoftwareController::class, 'manageAssociations'])->setName('software.associations.remove');
        });

        // Contracts management
        $group->group('/contracts', function (RouteCollectorProxy $group) {
            $group->get('', [ContractController::class, 'index'])->setName('contracts.index');
            $group->get('/search', [ContractController::class, 'search'])->setName('contracts.search');
            $group->get('/create', [ContractController::class, 'create'])->setName('contracts.create');
            $group->post('', [ContractController::class, 'store'])->setName('contracts.store');
            $group->get('/{id}', function($request, $response, $args) {
                $id = $args['id'];
                return $response->withStatus(302)->withHeader('Location', "/contracts/{$id}/edit");
            })->setName('contracts.show');
            $group->get('/{id}/edit', [ContractController::class, 'edit'])->setName('contracts.edit');
            $group->get('/{id}/events', [ContractController::class, 'events'])->setName('contracts.events');
            $group->post('/{id}', [ContractController::class, 'update'])->setName('contracts.update');
            $group->delete('/{id}', [ContractController::class, 'destroy'])->setName('contracts.destroy');
        });

        // Invoices management
        $group->group('/invoices', function (RouteCollectorProxy $group) {
            $group->get('', [InvoiceController::class, 'index'])->setName('invoices.index');
            $group->get('/search', [InvoiceController::class, 'search'])->setName('invoices.search');
            $group->get('/create', [InvoiceController::class, 'create'])->setName('invoices.create');
            $group->post('', [InvoiceController::class, 'store'])->setName('invoices.store');
            $group->get('/{id}', [InvoiceController::class, 'show'])->setName('invoices.show');
            $group->get('/{id}/edit', [InvoiceController::class, 'edit'])->setName('invoices.edit');
            $group->post('/{id}', [InvoiceController::class, 'update'])->setName('invoices.update');
            $group->post('/{id}/delete', [InvoiceController::class, 'delete'])->setName('invoices.delete');
        });

        // Files management
        $group->group('/files', function (RouteCollectorProxy $group) {
            $group->get('', [FileController::class, 'index'])->setName('files.index');
            $group->get('/search', [FileController::class, 'search'])->setName('files.search');
            $group->get('/create', [FileController::class, 'create'])->setName('files.create');
            $group->post('', [FileController::class, 'store'])->setName('files.store');
            $group->get('/{id}', [FileController::class, 'show'])->setName('files.show');
            $group->get('/{id}/edit', [FileController::class, 'edit'])->setName('files.edit');
            $group->get('/{id}/preview', [FileController::class, 'preview'])->setName('files.preview');
            $group->get('/{id}/download', [FileController::class, 'download'])->setName('files.download');
            $group->post('/{id}', [FileController::class, 'update'])->setName('files.update');
            $group->delete('/{id}', [FileController::class, 'destroy'])->setName('files.destroy');
        });

        // Agents (vendors/manufacturers) management
        $group->group('/agents', function (RouteCollectorProxy $group) {
            $group->get('', [AgentController::class, 'index'])->setName('agents.index');
            $group->get('/create', [AgentController::class, 'create'])->setName('agents.create');
            $group->post('', [AgentController::class, 'store'])->setName('agents.store');
            $group->get('/{id}', [AgentController::class, 'show'])->setName('agents.show');
            $group->get('/{id}/edit', [AgentController::class, 'edit'])->setName('agents.edit');
            $group->post('/{id}', [AgentController::class, 'update'])->setName('agents.update');
            $group->delete('/{id}', [AgentController::class, 'destroy'])->setName('agents.destroy');
        });

        // Locations management
        $group->group('/locations', function (RouteCollectorProxy $group) {
            $group->get('', [LocationController::class, 'index'])->setName('locations.index');
            $group->get('/create', [LocationController::class, 'create'])->setName('locations.create');
            $group->get('/tree', [LocationController::class, 'tree'])->setName('locations.tree');
            $group->post('', [LocationController::class, 'store'])->setName('locations.store');
            $group->get('/{id}', [LocationController::class, 'show'])->setName('locations.show');
            $group->get('/{id}/edit', [LocationController::class, 'edit'])->setName('locations.edit');
            $group->post('/{id}', [LocationController::class, 'update'])->setName('locations.update');
            $group->delete('/{id}', [LocationController::class, 'destroy'])->setName('locations.destroy');
        });

        // Racks management
        $group->group('/racks', function (RouteCollectorProxy $group) {
            $group->get('', [RackController::class, 'index'])->setName('racks.index');
            $group->get('/create', [RackController::class, 'create'])->setName('racks.create');
            $group->post('', [RackController::class, 'store'])->setName('racks.store');
            $group->get('/{id}', [RackController::class, 'show'])->setName('racks.show');
            $group->get('/{id}/edit', [RackController::class, 'edit'])->setName('racks.edit');
            $group->get('/{id}/layout', [RackController::class, 'layout'])->setName('racks.layout');
            $group->put('/{id}', [RackController::class, 'update'])->setName('racks.update');
            $group->delete('/{id}', [RackController::class, 'destroy'])->setName('racks.destroy');
        });

        // Users management
        $group->group('/users', function (RouteCollectorProxy $group) {
            $group->get('', [UserController::class, 'index'])->setName('users.index');
            $group->get('/create', [UserController::class, 'create'])->setName('users.create');
            $group->post('', [UserController::class, 'store'])->setName('users.store');
            $group->get('/{id}', function($request, $response, $args) {
                $id = $args['id'];
                return $response->withStatus(302)->withHeader('Location', "/users/{$id}/edit");
            })->setName('users.show');
            $group->get('/{id}/edit', [UserController::class, 'edit'])->setName('users.edit');
            $group->put('/{id}', [UserController::class, 'update'])->setName('users.update');
            $group->post('/{id}', [UserController::class, 'update'])->setName('users.update.post');
            $group->delete('/{id}', [UserController::class, 'destroy'])->setName('users.destroy');
        });

        // Reports
        $group->group('/reports', function (RouteCollectorProxy $group) {
            $group->get('', [ReportController::class, 'index'])->setName('reports.index');
            $group->get('/items', [ReportController::class, 'items'])->setName('reports.items');
            $group->get('/financial', [ReportController::class, 'financial'])->setName('reports.financial');
            $group->get('/utilization', [ReportController::class, 'utilization'])->setName('reports.utilization');
            $group->get('/contracts', [ReportController::class, 'contracts'])->setName('reports.contracts');
        });

        // Administration
        $group->group('/admin', function (RouteCollectorProxy $group) {
            $group->get('', [AdminController::class, 'index'])->setName('admin.index');
            $group->get('/settings', [AdminController::class, 'settings'])->setName('admin.settings');
            $group->post('/settings', [AdminController::class, 'updateSettings'])->setName('admin.settings.update');
            $group->get('/backup', [AdminController::class, 'backup'])->setName('admin.backup');
            $group->get('/import', [AdminController::class, 'import'])->setName('admin.import');
            $group->post('/import', [AdminController::class, 'processImport'])->setName('admin.import.process');
            $group->get('/translations', [AdminController::class, 'translations'])->setName('admin.translations');
            $group->get('/types', [AdminController::class, 'types'])->setName('admin.types');

            // Audit Log
            $group->get('/audit-log', [AuditLogController::class, 'index'])->setName('admin.audit-log.index');
            $group->get('/audit-log/export', [AuditLogController::class, 'export'])->setName('admin.audit-log.export');
            $group->post('/audit-log/clean', [AuditLogController::class, 'clean'])->setName('admin.audit-log.clean');

            // Item Types Management
            $group->group('/item-types', function (RouteCollectorProxy $group) {
                $group->get('', [ItemTypeController::class, 'index'])->setName('admin.item-types.index');
                $group->get('/create', [ItemTypeController::class, 'create'])->setName('admin.item-types.create');
                $group->post('', [ItemTypeController::class, 'store'])->setName('admin.item-types.store');
                $group->get('/{id}', [ItemTypeController::class, 'show'])->setName('admin.item-types.show');
                $group->get('/{id}/edit', [ItemTypeController::class, 'edit'])->setName('admin.item-types.edit');
                $group->post('/{id}', [ItemTypeController::class, 'update'])->setName('admin.item-types.update');
                $group->post('/{id}/delete', [ItemTypeController::class, 'delete'])->setName('admin.item-types.delete');
            });

            // Status Types Management
            $group->group('/status-types', function (RouteCollectorProxy $group) {
                $group->get('', [StatusTypeController::class, 'index'])->setName('admin.status-types.index');
                $group->get('/create', [StatusTypeController::class, 'create'])->setName('admin.status-types.create');
                $group->post('', [StatusTypeController::class, 'store'])->setName('admin.status-types.store');
                $group->get('/{id}', [StatusTypeController::class, 'show'])->setName('admin.status-types.show');
                $group->get('/{id}/edit', [StatusTypeController::class, 'edit'])->setName('admin.status-types.edit');
                $group->post('/{id}', [StatusTypeController::class, 'update'])->setName('admin.status-types.update');
                $group->post('/{id}/delete', [StatusTypeController::class, 'delete'])->setName('admin.status-types.delete');
            });

            // Contract Types Management
            $group->group('/contract-types', function (RouteCollectorProxy $group) {
                $group->get('', [ContractTypeController::class, 'index'])->setName('admin.contract-types.index');
                $group->get('/create', [ContractTypeController::class, 'create'])->setName('admin.contract-types.create');
                $group->post('', [ContractTypeController::class, 'store'])->setName('admin.contract-types.store');
                $group->get('/{id}', [ContractTypeController::class, 'show'])->setName('admin.contract-types.show');
                $group->get('/{id}/edit', [ContractTypeController::class, 'edit'])->setName('admin.contract-types.edit');
                $group->post('/{id}', [ContractTypeController::class, 'update'])->setName('admin.contract-types.update');
                $group->post('/{id}/delete', [ContractTypeController::class, 'delete'])->setName('admin.contract-types.delete');
            });

            // File Types Management
            $group->group('/file-types', function (RouteCollectorProxy $group) {
                $group->get('', [FileTypeController::class, 'index'])->setName('admin.file-types.index');
                $group->get('/create', [FileTypeController::class, 'create'])->setName('admin.file-types.create');
                $group->post('', [FileTypeController::class, 'store'])->setName('admin.file-types.store');
                $group->get('/{id}', [FileTypeController::class, 'show'])->setName('admin.file-types.show');
                $group->get('/{id}/edit', [FileTypeController::class, 'edit'])->setName('admin.file-types.edit');
                $group->post('/{id}', [FileTypeController::class, 'update'])->setName('admin.file-types.update');
                $group->post('/{id}/delete', [FileTypeController::class, 'delete'])->setName('admin.file-types.delete');
            });

            // License Types Management
            $group->group('/license-types', function (RouteCollectorProxy $group) {
                $group->get('', [LicenseTypeController::class, 'index'])->setName('admin.license-types.index');
                $group->get('/create', [LicenseTypeController::class, 'create'])->setName('admin.license-types.create');
                $group->post('', [LicenseTypeController::class, 'store'])->setName('admin.license-types.store');
                $group->get('/{id}', [LicenseTypeController::class, 'show'])->setName('admin.license-types.show');
                $group->get('/{id}/edit', [LicenseTypeController::class, 'edit'])->setName('admin.license-types.edit');
                $group->post('/{id}', [LicenseTypeController::class, 'update'])->setName('admin.license-types.update');
                $group->post('/{id}/delete', [LicenseTypeController::class, 'delete'])->setName('admin.license-types.delete');
            });

            // Agent Types Management
            $group->group('/agent-types', function (RouteCollectorProxy $group) {
                $group->get('', [AgentTypeController::class, 'index'])->setName('admin.agent-types.index');
                $group->get('/create', [AgentTypeController::class, 'create'])->setName('admin.agent-types.create');
                $group->post('', [AgentTypeController::class, 'store'])->setName('admin.agent-types.store');
                $group->get('/{id}', [AgentTypeController::class, 'show'])->setName('admin.agent-types.show');
                $group->get('/{id}/edit', [AgentTypeController::class, 'edit'])->setName('admin.agent-types.edit');
                $group->post('/{id}', [AgentTypeController::class, 'update'])->setName('admin.agent-types.update');
                $group->post('/{id}/delete', [AgentTypeController::class, 'destroy'])->setName('admin.agent-types.delete');
            });
        });

        // Legacy route compatibility (temporary)
        $group->get('/index.php', function ($request, $response) {
            $action = $request->getQueryParam('action', '');
            $id = $request->getQueryParam('id', '');

            // Map legacy actions to new routes
            $routeMap = [
                'listitems' => 'items.index',
                'edititem' => $id === 'new' ? 'items.create' : 'items.edit',
                'listsoftware' => 'software.index',
                'editsoftware' => $id === 'new' ? 'software.create' : 'software.edit',
                'listcontracts' => 'contracts.index',
                'editcontract' => $id === 'new' ? 'contracts.create' : 'contracts.edit',
                'listinvoices' => 'invoices.index',
                'editinvoice' => $id === 'new' ? 'invoices.create' : 'invoices.edit',
                // Add more mappings as needed
            ];

            if (isset($routeMap[$action])) {
                $routeName = $routeMap[$action];
                $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
                $routeParser = $routeContext->getRouteParser();

                if ($id && $id !== 'new') {
                    $url = $routeParser->urlFor($routeName, ['id' => $id]);
                } else {
                    $url = $routeParser->urlFor($routeName);
                }

                return $response->withHeader('Location', $url)->withStatus(302);
            }

            // Default to dashboard
            $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
            $routeParser = $routeContext->getRouteParser();
            $url = $routeParser->urlFor('dashboard');
            return $response->withHeader('Location', $url)->withStatus(302);
        });

    })->add(AuthMiddleware::class);
};