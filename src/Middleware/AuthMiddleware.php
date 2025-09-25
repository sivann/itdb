<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

class AuthMiddleware implements Middleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Process middleware
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated using AuthService (includes BYPASS_AUTH check)
        if (!$this->authService->isAuthenticated()) {
            // Redirect to login page
            $routeContext = RouteContext::fromRequest($request);
            $routeParser = $routeContext->getRouteParser();
            $loginUrl = $routeParser->urlFor('login');

            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', $loginUrl)->withStatus(302);
        }

        // User is authenticated, continue with request
        return $handler->handle($request);
    }
}