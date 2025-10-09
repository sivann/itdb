<?php

declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\SessionMiddleware;
use Slim\App;

return function (App $app) {
    // Add routing middleware first (will run after body parsing and method override)
    $app->addRoutingMiddleware();

    // Method override middleware - must be added before body parsing
    // so it can read the _method field from parsed request body
    // and must be added after routing middleware so it runs before routing
    $methodOverrideMiddleware = new \Slim\Middleware\MethodOverrideMiddleware();
    $app->add($methodOverrideMiddleware);

    // Body parsing middleware - added after method override so it runs before method override
    // This ensures form data is parsed before _method field is checked
    $app->addBodyParsingMiddleware();

    // CORS middleware (if needed for API)
    $app->add(CorsMiddleware::class);

    // Session middleware
    $app->add(SessionMiddleware::class);

    // Auth middleware will be applied per route
};