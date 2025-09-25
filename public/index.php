<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Build DI Container
$containerBuilder = new ContainerBuilder();

// Set up dependencies
$dependencies = require __DIR__ . '/../config/container.php';
$dependencies($containerBuilder);

// Build container
$container = $containerBuilder->build();

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set up middleware
$middleware = require __DIR__ . '/../config/middleware.php';
$middleware($app);

// Set up routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// Add error middleware LAST so it executes FIRST
$errorMiddleware = $app->addErrorMiddleware(
    (bool) ($_ENV['APP_DEBUG'] ?? false),
    true,
    true
);

// Add custom error handler for 404s
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('text/html', \App\Handlers\HtmlErrorRenderer::class);

// Custom error handler to log 404s more quietly
$errorMiddleware->setErrorHandler(
    Slim\Exception\HttpNotFoundException::class,
    function (\Psr\Http\Message\ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($container) {
        $logger = $container->get(\Psr\Log\LoggerInterface::class);

        // Log 404s at INFO level instead of ERROR level with minimal details
        $logger->info('404 Not Found: ' . $request->getMethod() . ' ' . (string) $request->getUri());

        // Create a simple 404 response
        $response = new \Slim\Psr7\Response(404);
        $response->getBody()->write((new \App\Handlers\HtmlErrorRenderer())($exception, $displayErrorDetails));
        return $response->withHeader('Content-Type', 'text/html');
    }
);

// Run app
$app->run();