<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Routing\RouteContext;
use Twig\Environment;

abstract class BaseController
{
    protected LoggerInterface $logger;
    protected Environment $twig;

    public function __construct(LoggerInterface $logger, Environment $twig)
    {
        $this->logger = $logger;
        $this->twig = $twig;
    }

    /**
     * Render a template with data
     */
    protected function render(Response $response, string $template, array $data = []): Response
    {
        // Add common template variables
        $data = array_merge($data, [
            'current_route' => $this->getCurrentRoute($response),
            'flash_messages' => $this->getFlashMessages(),
        ]);

        $html = $this->twig->render($template, $data);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    /**
     * Return JSON response
     */
    protected function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Redirect to a named route
     */
    protected function redirectToRoute(
        Request $request,
        Response $response,
        string $routeName,
        array $data = [],
        array $queryParams = []
    ): Response {
        $routeContext = RouteContext::fromRequest($request);
        $routeParser = $routeContext->getRouteParser();
        $url = $routeParser->urlFor($routeName, $data, $queryParams);

        return $response->withHeader('Location', $url)->withStatus(302);
    }

    /**
     * Add flash message to session
     */
    protected function addFlashMessage(string $type, string $message): void
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }

        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * Get and clear flash messages
     */
    protected function getFlashMessages(): array
    {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);

        return $messages;
    }

    /**
     * Get current route name
     */
    protected function getCurrentRoute(Response $response): ?string
    {
        // This would need to be implemented based on route context
        return null;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrfToken(Request $request): bool
    {
        $submittedToken = $request->getParsedBody()['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        return hash_equals($sessionToken, $submittedToken);
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get parsed body data safely
     */
    protected function getParsedBody(Request $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    /**
     * Get query parameters safely
     */
    protected function getQueryParams(Request $request): array
    {
        return $request->getQueryParams();
    }

    /**
     * Sanitize string input
     */
    protected function sanitizeString(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        return trim(strip_tags($input));
    }

    /**
     * Sanitize integer input
     */
    protected function sanitizeInt($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Log user action
     */
    protected function logUserAction(string $action, array $data = []): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $username = $_SESSION['username'] ?? 'anonymous';

        $this->logger->info('User action', [
            'user_id' => $userId,
            'username' => $username,
            'action' => $action,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    }

    /**
     * Handle form validation errors
     */
    protected function handleValidationErrors(array $errors, Request $request, Response $response): Response
    {
        foreach ($errors as $error) {
            $this->addFlashMessage('error', $error);
        }

        // Return to referrer or default route
        $referer = $request->getHeaderLine('HTTP_REFERER');
        if ($referer) {
            return $response->withHeader('Location', $referer)->withStatus(302);
        }

        return $this->redirectToRoute($request, $response, 'dashboard');
    }
}