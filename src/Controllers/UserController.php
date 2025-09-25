<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class UserController extends BaseController
{
    private AuthService $authService;
    private UserModel $userModel;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        AuthService $authService,
        UserModel $userModel
    ) {
        parent::__construct($logger, $twig);
        $this->authService = $authService;
        $this->userModel = $userModel;
    }

    /**
     * List all users
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();
        $queryParams = $this->getQueryParams($request);

        // Only admins can view user list
        if (!$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        // Build filters for UserModel
        $filters = [];
        if (!empty($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }

        // Filter by user type
        if (isset($queryParams['usertype']) && $queryParams['usertype'] !== '') {
            $filters['usertype'] = (int) $queryParams['usertype'];
        }

        // Pagination
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = 20;

        // Get paginated users using UserModel
        $result = $this->userModel->getPaginated($page, $perPage, $filters);

        return $this->render($response, 'users/index.twig', [
            'user' => $user,
            'users' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'search' => $queryParams['search'] ?? '',
            'usertype_filter' => $queryParams['usertype'] ?? '',
            'user_types' => [
                0 => 'Inactive',
                1 => 'Admin',
                2 => 'LDAP User'
            ],
        ]);
    }

    /**
     * Show user details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        // Users can only view their own profile unless they're admin
        if (!$currentUser->isAdmin() && $currentUser->id !== $id) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $user = $this->userModel->findWithCounts($id);
        if (!$user) {
            $this->addFlashMessage('error', 'User not found');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        // Get recent items assigned to this user
        $recentItems = $this->userModel->getRecentItems($id, 10);

        return $this->render($response, 'users/edit.twig', [
            'mode' => 'view',
            'user' => $currentUser,
            'profile_user' => $user,
            'recent_items' => $recentItems,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        // Only admins can create users
        if (!$user->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        return $this->render($response, 'users/edit.twig', [
            'mode' => 'create',
            'user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
            'user_types' => [
                1 => 'Admin',
                2 => 'LDAP User'
            ],
        ]);
    }

    /**
     * Store new user
     */
    public function store(Request $request, Response $response): Response
    {
        $currentUser = $this->authService->getCurrentUser();

        // Only admins can create users
        if (!$currentUser->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'users.create');
        }

        $data = $this->getParsedBody($request);

        // Validation
        $errors = [];
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        } elseif ($this->userModel->usernameExists($data['username'])) {
            $errors[] = 'Username already exists';
        }

        if (empty($data['pass'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['pass']) < 4) {
            $errors[] = 'Password must be at least 4 characters';
        }

        if (!isset($data['usertype']) || !in_array((int) $data['usertype'], [1, 2])) {
            $errors[] = 'Valid user type is required';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $userId = $this->userModel->create([
                'username' => $this->sanitizeString($data['username']),
                'realname' => $this->sanitizeString($data['userdesc'] ?? ''), // Map userdesc to realname
                'password' => $data['pass'], // In production, should be hashed
                'usertype' => (int) $data['usertype'],
                'comments' => null,
            ]);

            $this->logUserAction('user_created', ['created_user_id' => $userId, 'username' => $data['username']]);
            $this->addFlashMessage('success', 'User created successfully');
            return $this->redirectToRoute($request, $response, 'users.show', ['id' => $userId]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating user', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error creating user');
            return $this->redirectToRoute($request, $response, 'users.create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        // Users can only edit their own profile unless they're admin
        if (!$currentUser->isAdmin() && $currentUser->id !== $id) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->addFlashMessage('error', 'User not found');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        return $this->render($response, 'users/edit.twig', [
            'mode' => 'edit',
            'user' => $currentUser,
            'profile_user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
            'user_types' => [
                0 => 'Inactive',
                1 => 'Admin',
                2 => 'LDAP User'
            ],
            'can_edit_usertype' => $currentUser->isAdmin(),
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        // Users can only edit their own profile unless they're admin
        if (!$currentUser->isAdmin() && $currentUser->id !== $id) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        $data = $this->getParsedBody($request);

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->addFlashMessage('error', 'User not found');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        // Validation
        $errors = [];
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        } elseif ($this->userModel->usernameExists($data['username'], $id)) {
            $errors[] = 'Username already exists';
        }

        // Password validation (only if provided)
        if (!empty($data['pass']) && strlen($data['pass']) < 4) {
            $errors[] = 'Password must be at least 4 characters';
        }

        // Only admins can change user type
        if (isset($data['usertype']) && !$currentUser->isAdmin()) {
            $errors[] = 'Access denied: cannot change user type';
        } elseif (isset($data['usertype']) && !in_array((int) $data['usertype'], [0, 1, 2])) {
            $errors[] = 'Invalid user type';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $updateData = [
                'username' => $this->sanitizeString($data['username']),
                'realname' => $this->sanitizeString($data['userdesc'] ?? ''),
                'comments' => null
            ];

            // Update user type only if admin
            if (isset($data['usertype']) && $currentUser->isAdmin()) {
                $updateData['usertype'] = (int) $data['usertype'];
            }

            $this->userModel->update($id, $updateData);

            // Update password separately if provided
            if (!empty($data['pass'])) {
                $this->userModel->updatePassword($id, $data['pass']); // In production, should be hashed
            }

            $this->logUserAction('user_updated', ['updated_user_id' => $id, 'username' => $data['username']]);
            $this->addFlashMessage('success', 'User updated successfully');
            return $this->redirectToRoute($request, $response, 'users.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating user', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error updating user');
            return $this->redirectToRoute($request, $response, 'users.edit', ['id' => $id]);
        }
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authService->getCurrentUser();

        // Only admins can delete users
        if (!$currentUser->isAdmin()) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        $id = (int) $args['id'];

        // Prevent deleting self
        if ($currentUser->id === $id) {
            $this->addFlashMessage('error', 'You cannot delete your own account');
            return $this->redirectToRoute($request, $response, 'users.show', ['id' => $id]);
        }

        $user = $this->userModel->findWithCounts($id);
        if (!$user) {
            $this->addFlashMessage('error', 'User not found');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        // Check if user has assigned items
        if (($user['items_count'] ?? 0) > 0) {
            $this->addFlashMessage('error', 'Cannot delete user with assigned items. Reassign items first.');
            return $this->redirectToRoute($request, $response, 'users.show', ['id' => $id]);
        }

        try {
            $this->logUserAction('user_deleted', ['deleted_user_id' => $id, 'username' => $user['username']]);
            $this->userModel->delete($id);

            $this->addFlashMessage('success', 'User deleted successfully');
            return $this->redirectToRoute($request, $response, 'users.index');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting user', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error deleting user');
            return $this->redirectToRoute($request, $response, 'users.show', ['id' => $id]);
        }
    }

    /**
     * Show change password form
     */
    public function changePassword(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        // Users can only change their own password unless they're admin
        if (!$currentUser->isAdmin() && $currentUser->id !== $id) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->addFlashMessage('error', 'User not found');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        return $this->render($response, 'users/change-password.twig', [
            'user' => $currentUser,
            'profile_user' => $user,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Process password change
     */
    public function updatePassword(Request $request, Response $response, array $args): Response
    {
        $currentUser = $this->authService->getCurrentUser();
        $id = (int) $args['id'];

        // Users can only change their own password unless they're admin
        if (!$currentUser->isAdmin() && $currentUser->id !== $id) {
            $this->addFlashMessage('error', 'Access denied');
            return $this->redirectToRoute($request, $response, 'dashboard');
        }

        if (!$this->validateCsrfToken($request)) {
            $this->addFlashMessage('error', 'Invalid CSRF token');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        $data = $this->getParsedBody($request);

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->addFlashMessage('error', 'User not found');
            return $this->redirectToRoute($request, $response, 'users.index');
        }

        // Validation
        $errors = [];

        // Current password check (only for non-admins changing their own password)
        if ($currentUser->id === $id && !$currentUser->isAdmin()) {
            if (empty($data['current_password'])) {
                $errors[] = 'Current password is required';
            } elseif (!$this->userModel->verifyPassword($id, $data['current_password'])) {
                $errors[] = 'Current password is incorrect';
            }
        }

        if (empty($data['new_password'])) {
            $errors[] = 'New password is required';
        } elseif (strlen($data['new_password']) < 4) {
            $errors[] = 'New password must be at least 4 characters';
        }

        if ($data['new_password'] !== $data['confirm_password']) {
            $errors[] = 'Password confirmation does not match';
        }

        if (!empty($errors)) {
            return $this->handleValidationErrors($errors, $request, $response);
        }

        try {
            $this->userModel->updatePassword($id, $data['new_password']); // In production, should be hashed

            $this->logUserAction('password_changed', ['changed_user_id' => $id, 'username' => $user['username']]);
            $this->addFlashMessage('success', 'Password changed successfully');
            return $this->redirectToRoute($request, $response, 'users.show', ['id' => $id]);

        } catch (\Exception $e) {
            $this->logger->error('Error changing password', ['error' => $e->getMessage()]);
            $this->addFlashMessage('error', 'Error changing password');
            return $this->redirectToRoute($request, $response, 'users.change-password', ['id' => $id]);
        }
    }
}