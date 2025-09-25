<?php

declare(strict_types=1);

use App\Services\AuthService;
use App\Services\DatabaseService;
use App\Services\DatabaseManager;
use App\Services\ItemService;
use App\Services\SoftwareService;
use App\Services\ContractService;
use App\Services\InvoiceService;
use App\Services\FileService;
use App\Services\UserService;
use App\Services\AgentService;
use App\Services\LocationService;
use App\Services\RackService;
use App\Services\ReportService;
use App\Services\TranslationService;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([

        // Logger
        LoggerInterface::class => function (ContainerInterface $c) {
            $logger = new Logger('itdb');
            $handler = new StreamHandler(
                $_ENV['LOG_PATH'] ?? __DIR__ . '/../storage/logs/app.log',
                $_ENV['LOG_LEVEL'] ?? Logger::DEBUG
            );
            $logger->pushHandler($handler);
            return $logger;
        },

        // PDO Database Connection
        PDO::class => function (ContainerInterface $c) {
            $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
            $database = $_ENV['DB_DATABASE'] ?? '/Users/sivann/sbx/itdb2/src/data/itdb.db';

            if ($driver === 'sqlite') {
                $dsn = "sqlite:$database";
                $pdo = new PDO($dsn);
            } else {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? 3306;
                $username = $_ENV['DB_USERNAME'] ?? '';
                $password = $_ENV['DB_PASSWORD'] ?? '';
                $charset = 'utf8mb4';

                $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=$charset";
                $pdo = new PDO($dsn, $username, $password);
            }

            // Enable foreign key constraints for SQLite
            if ($driver === 'sqlite') {
                $pdo->exec('PRAGMA foreign_keys = ON');
            }

            // Set error mode and default fetch mode
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $pdo;
        },

        // Database Manager
        DatabaseManager::class => function (ContainerInterface $c) {
            return new DatabaseManager(
                $c->get(PDO::class),
                $c->get(LoggerInterface::class)
            );
        },

        // Twig
        Environment::class => function (ContainerInterface $c) {
            $loader = new FilesystemLoader(__DIR__ . '/../templates');
            $twig = new Environment($loader, [
                'cache' => $_ENV['APP_ENV'] === 'production' ? __DIR__ . '/../storage/cache' : false,
                'debug' => (bool) ($_ENV['APP_DEBUG'] ?? false),
                'auto_reload' => true,
            ]);

            // Add global variables
            $twig->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'ITDB');
            $twig->addGlobal('app_url', $_ENV['APP_URL'] ?? 'http://localhost:8080');

            // Add custom filters
            $twig->addFilter(new TwigFilter('format_bytes', function ($bytes, $precision = 2) {
                if ($bytes === 0 || $bytes === null) {
                    return '0 B';
                }

                $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
                $base = log($bytes, 1024);
                $index = floor($base);

                return round(pow(1024, $base - $index), $precision) . ' ' . $units[$index];
            }));

            return $twig;
        },

        // Services
        DatabaseService::class => function (ContainerInterface $c) {
            return new DatabaseService($c->get(LoggerInterface::class), $c->get(DatabaseManager::class));
        },

        AuthService::class => function (ContainerInterface $c) {
            return new AuthService(
                $c->get(LoggerInterface::class),
                $_ENV['JWT_SECRET'] ?? 'change-me-in-production',
                $c->get(DatabaseManager::class)
            );
        },

        ItemService::class => function (ContainerInterface $c) {
            return new ItemService($c->get(LoggerInterface::class));
        },

        SoftwareService::class => function (ContainerInterface $c) {
            return new SoftwareService($c->get(LoggerInterface::class));
        },

        ContractService::class => function (ContainerInterface $c) {
            return new ContractService($c->get(LoggerInterface::class));
        },

        InvoiceService::class => function (ContainerInterface $c) {
            return new InvoiceService($c->get(LoggerInterface::class));
        },

        FileService::class => function (ContainerInterface $c) {
            return new FileService(
                $c->get(LoggerInterface::class),
                $_ENV['UPLOAD_PATH'] ?? __DIR__ . '/../storage/uploads'
            );
        },

        UserService::class => function (ContainerInterface $c) {
            return new UserService($c->get(LoggerInterface::class));
        },

        AgentService::class => function (ContainerInterface $c) {
            return new AgentService($c->get(LoggerInterface::class));
        },

        LocationService::class => function (ContainerInterface $c) {
            return new LocationService($c->get(LoggerInterface::class));
        },

        RackService::class => function (ContainerInterface $c) {
            return new RackService($c->get(LoggerInterface::class));
        },

        ReportService::class => function (ContainerInterface $c) {
            return new ReportService($c->get(LoggerInterface::class));
        },

        TranslationService::class => function (ContainerInterface $c) {
            return new TranslationService(
                $c->get(LoggerInterface::class),
                $_ENV['DEFAULT_LANGUAGE'] ?? 'en'
            );
        },

        // Models
        \App\Models\AgentModel::class => function (ContainerInterface $c) {
            return new \App\Models\AgentModel($c->get(DatabaseManager::class));
        },

        \App\Models\AgentTypeModel::class => function (ContainerInterface $c) {
            return new \App\Models\AgentTypeModel($c->get(DatabaseManager::class));
        },

        \App\Models\ItemModel::class => function (ContainerInterface $c) {
            return new \App\Models\ItemModel($c->get(DatabaseManager::class));
        },

        \App\Models\SoftwareModel::class => function (ContainerInterface $c) {
            return new \App\Models\SoftwareModel($c->get(DatabaseManager::class), $c->get(\App\Models\InvoiceModel::class));
        },

        \App\Models\ContractModel::class => function (ContainerInterface $c) {
            return new \App\Models\ContractModel($c->get(DatabaseManager::class));
        },

        \App\Models\ContractTypeModel::class => function (ContainerInterface $c) {
            return new \App\Models\ContractTypeModel($c->get(DatabaseManager::class));
        },

        \App\Models\RackModel::class => function (ContainerInterface $c) {
            return new \App\Models\RackModel($c->get(DatabaseManager::class));
        },

        \App\Models\LocationModel::class => function (ContainerInterface $c) {
            return new \App\Models\LocationModel($c->get(DatabaseManager::class));
        },

        \App\Models\InvoiceModel::class => function (ContainerInterface $c) {
            return new \App\Models\InvoiceModel($c->get(DatabaseManager::class));
        },

        \App\Models\FileModel::class => function (ContainerInterface $c) {
            return new \App\Models\FileModel($c->get(DatabaseManager::class));
        },

        \App\Models\UserModel::class => function (ContainerInterface $c) {
            return new \App\Models\UserModel($c->get(DatabaseManager::class));
        },

        \App\Models\ItemTypeModel::class => function (ContainerInterface $c) {
            return new \App\Models\ItemTypeModel($c->get(DatabaseManager::class));
        },

        \App\Models\StatusTypeModel::class => function (ContainerInterface $c) {
            return new \App\Models\StatusTypeModel($c->get(DatabaseManager::class));
        },

        \App\Models\FileTypeModel::class => function (ContainerInterface $c) {
            return new \App\Models\FileTypeModel($c->get(DatabaseManager::class));
        },

        \App\Models\LicenseTypeModel::class => function (ContainerInterface $c) {
            return new \App\Models\LicenseTypeModel($c->get(DatabaseManager::class));
        },

        \App\Models\AuditLogModel::class => function (ContainerInterface $c) {
            return new \App\Models\AuditLogModel($c->get(DatabaseManager::class));
        },

        // Middleware
        \App\Middleware\AuthMiddleware::class => function (ContainerInterface $c) {
            return new \App\Middleware\AuthMiddleware($c->get(AuthService::class));
        },

        // Controllers will be auto-wired by PHP-DI
        \App\Controllers\LicenseTypeController::class => function (ContainerInterface $c) {
            return new \App\Controllers\LicenseTypeController(
                $c->get(LoggerInterface::class),
                $c->get(Environment::class),
                $c->get(AuthService::class),
                $c->get(\App\Models\LicenseTypeModel::class)
            );
        },

        \App\Controllers\AuditLogController::class => function (ContainerInterface $c) {
            return new \App\Controllers\AuditLogController(
                $c->get(LoggerInterface::class),
                $c->get(Environment::class),
                $c->get(AuthService::class),
                $c->get(\App\Models\AuditLogModel::class),
                $c->get(\App\Models\UserModel::class)
            );
        },
    ]);
};