<?php

declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\SessionMiddleware;
use Slim\App;

return function (App $app) {
    // Body parsing middleware
    $app->addBodyParsingMiddleware();

    // Method override middleware
    $methodOverrideMiddleware = new \Slim\Middleware\MethodOverrideMiddleware();
    $app->add($methodOverrideMiddleware);

    // Add routing middleware
    $app->addRoutingMiddleware();

    // CORS middleware (if needed for API)
    $app->add(CorsMiddleware::class);

    // Session middleware
    $app->add(SessionMiddleware::class);

    // Auth middleware will be applied per route
};