<?php

declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\SessionMiddleware;
use Slim\App;

return function (App $app) {
    // Parse JSON bodies
    $app->addBodyParsingMiddleware();

    // Add routing middleware
    $app->addRoutingMiddleware();

    // Method override middleware (for _method field in forms) - must run before routing
    $methodOverrideMiddleware = new \Slim\Middleware\MethodOverrideMiddleware();
    $app->add($methodOverrideMiddleware);

    // CORS middleware (if needed for API)
    $app->add(CorsMiddleware::class);

    // Session middleware
    $app->add(SessionMiddleware::class);

    // Auth middleware will be applied per route
};