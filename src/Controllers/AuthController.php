<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(LoggerInterface $logger, Environment $twig, AuthService $authService)
    {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
    }

    /**
     * Show login page
     */
    public function showLogin(Request $request, Response $response): Response
    {
        // If user is already authenticated, redirect to dashboard
        if ($this->authService->isAuthenticated()) {
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        // Get username from cookie if available
        $username = $_COOKIE['itdbuser'] ?? 'username';

        return $this->render($response, 'auth/login.twig', [
            'username' => $username,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Process login
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $this->getParsedBody($request);

        // Validate CSRF token
        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute($request, $response, 'login');
        }

        $username = $data['authusername'] ?? '';
        $password = $data['authpassword'] ?? '';

        if (empty($username) || empty($password)) {
            $this->addFlashMessage('error', 'Username and password are required.');
            return $this->redirectToRoute($request, $response, 'login');
        }

        // Attempt authentication
        $result = $this->authService->authenticate($username, $password);

        if (!$result['success']) {
            $this->addFlashMessage('error', $result['message']);
            return $this->redirectToRoute($request, $response, 'login');
        }

        // Log successful login
        $this->logUserAction('login', [
            'username' => $username,
            'user_id' => $result['user']->id
        ]);

        $this->addFlashMessage('success', $result['message']);

        // Redirect to intended page or dashboard
        $redirectUrl = $data['redirect'] ?? null;
        if ($redirectUrl && $this->isValidRedirectUrl($redirectUrl)) {
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        return $this->redirectToRoute($request, $response, 'dashboard');
    }

    /**
     * Process logout
     */
    public function logout(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Log logout action
        if ($user) {
            $this->logUserAction('logout', [
                'username' => $user->username,
                'user_id' => $user->id
            ]);
        }

        $this->authService->logout();

        $this->addFlashMessage('info', 'You have been logged out successfully.');

        return $this->redirectToRoute($request, $response, 'login');
    }

    /**
     * API endpoint for authentication (returns JSON)
     */
    public function apiLogin(Request $request, Response $response): Response
    {
        $data = $this->getParsedBody($request);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Username and password are required'
            ], 400);
        }

        $result = $this->authService->authenticate($username, $password);

        if (!$result['success']) {
            return $this->json($response, [
                'success' => false,
                'message' => $result['message']
            ], 401);
        }

        return $this->json($response, [
            'success' => true,
            'message' => $result['message'],
            'token' => $result['token'],
            'user' => [
                'id' => $result['user']->id,
                'username' => $result['user']->username,
                'name' => $result['user']->name,
                'user_type' => $result['user']->usertype,
            ]
        ]);
    }

    /**
     * API endpoint for token validation
     */
    public function apiValidateToken(Request $request, Response $response): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->json($response, [
                'success' => false,
                'message' => 'No token provided'
            ], 401);
        }

        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix
        $payload = $this->authService->validateJwt($token);

        if (!$payload) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        return $this->json($response, [
            'success' => true,
            'user' => [
                'id' => $payload['sub'],
                'username' => $payload['username'],
                'user_type' => $payload['user_type'],
            ]
        ]);
    }

    /**
     * Show user profile/account page
     */
    public function profile(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            return $this->redirectToRoute($request, $response, 'login');
        }

        return $this->render($response, 'auth/profile.twig', [
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            return $this->redirectToRoute($request, $response, 'login');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid security token.');
            return $this->redirectToRoute($request, $response, 'profile');
        }

        $data = $this->getParsedBody($request);

        // Validate and update allowed fields
        $allowedFields = ['name', 'email', 'phone', 'department'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $this->sanitizeString($data[$field]);
            }
        }

        // Handle password change
        if (!empty($data['new_password'])) {
            if (empty($data['current_password'])) {
                $this->addFlashMessage('error', 'Current password is required to change password.');
                return $this->redirectToRoute($request, $response, 'profile');
            }

            if (!$user->verifyPassword($data['current_password'])) {
                $this->addFlashMessage('error', 'Current password is incorrect.');
                return $this->redirectToRoute($request, $response, 'profile');
            }

            if ($data['new_password'] !== $data['confirm_password']) {
                $this->addFlashMessage('error', 'New passwords do not match.');
                return $this->redirectToRoute($request, $response, 'profile');
            }

            $updateData['pass'] = $data['new_password'];
        }

        if (empty($updateData)) {
            $this->addFlashMessage('info', 'No changes to save.');
            return $this->redirectToRoute($request, $response, 'profile');
        }

        try {
            $user->updateSafe($updateData);

            $this->logUserAction('profile_update', ['fields' => array_keys($updateData)]);
            $this->addFlashMessage('success', 'Profile updated successfully.');

        } catch (\Exception $e) {
            $this->logger->error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            $this->addFlashMessage('error', 'Failed to update profile. Please try again.');
        }

        return $this->redirectToRoute($request, $response, 'profile');
    }

    /**
     * Validate if redirect URL is safe
     */
    private function isValidRedirectUrl(string $url): bool
    {
        // Only allow relative URLs or URLs to the same domain
        if (str_starts_with($url, '/')) {
            return true;
        }

        $parsedUrl = parse_url($url);
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';

        return isset($parsedUrl['host']) && $parsedUrl['host'] === $currentHost;
    }
}