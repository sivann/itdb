<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;

class AuthService extends BaseService
{
    private string $jwtSecret;
    private string $jwtAlgorithm;
    private int $jwtExpiration;

    public function __construct(LoggerInterface $logger, string $jwtSecret, DatabaseManager $db)
    {
        parent::__construct($logger);
        $this->jwtSecret = $jwtSecret;
        $this->jwtAlgorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $this->jwtExpiration = (int) ($_ENV['JWT_EXPIRATION'] ?? 86400);
        $this->setDatabaseManager($db);
    }

    /**
     * Authenticate user with username and password
     */
    public function authenticate(string $username, string $password): array
    {
        // Sanitize input
        $username = $this->sanitizeString($username);
        $username = str_replace([';', '%', "'"], '', $username);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }

        // Try LDAP authentication first if enabled
        if ($this->isLdapEnabled() && $username !== 'admin') {
            $result = $this->authenticateLdap($username, $password);
            if ($result['success']) {
                return $result;
            }
        }

        // Try local authentication
        return $this->authenticateLocal($username, $password);
    }

    /**
     * Authenticate against local database
     */
    private function authenticateLocal(string $username, string $password): array
    {
        try {
            error_log("AUTH: Attempting to authenticate user: $username");

            // Use DatabaseManager for prepared statements
            $result = $this->db->fetchAll(
                "SELECT * FROM users WHERE username = ? LIMIT 1",
                [$username]
            );

            error_log("AUTH: Query result count: " . count($result));

            if (empty($result)) {
                $this->logAction('login_failed', ['username' => $username, 'reason' => 'user_not_found']);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            $userData = $result[0];

            // Verify password (plain text comparison for legacy compatibility)
            if ($userData['pass'] !== $password) {
                $this->logAction('login_failed', ['username' => $username, 'reason' => 'invalid_password']);
                return ['success' => false, 'message' => 'Wrong Password'];
            }

            // Generate new cookie token for session compatibility
            $cookieToken = mt_rand();

            // Update user's cookie token in database
            $this->db->execute(
                "UPDATE users SET cookie1 = ? WHERE id = ?",
                [$cookieToken, $userData['id']]
            );

            // Set session variables
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['user_type'] = $userData['usertype'];
            $_SESSION['authenticated'] = true;

            // Set cookies for compatibility with legacy system
            $cookiePath = $this->getCookiePath();
            setcookie('itdbcookie1', (string)$cookieToken, time() + (2 * 86400), $cookiePath);
            setcookie('itdbuser', $username, time() + (60 * 86400), $cookiePath);

            $this->logAction('login_success', ['user_id' => $userData['id'], 'username' => $username]);

            // Create a simple user object for compatibility
            $userObj = (object) $userData;

            return [
                'success' => true,
                'message' => 'User Authenticated',
                'user' => $userObj,
                'token' => $this->generateJwt($userObj)
            ];

        } catch (\Exception $e) {
            error_log("AUTH ERROR: " . $e->getMessage());
            $this->logError('Authentication error', $e, ['username' => $username]);
            return ['success' => false, 'message' => 'Authentication error'];
        }
    }

    /**
     * Authenticate against LDAP
     */
    private function authenticateLdap(string $username, string $password): array
    {
        if (!$this->isLdapEnabled()) {
            return ['success' => false, 'message' => 'LDAP not enabled'];
        }

        try {
            $ldapResult = $this->connectToLdapServer(
                $_ENV['LDAP_HOST'] ?? '',
                $username,
                $password,
                $_ENV['LDAP_BASE_DN'] ?? ''
            );

            if (!$ldapResult) {
                return ['success' => false, 'message' => 'Wrong Password'];
            }

            // Find or create LDAP user
            $result = $this->db->fetchAll(
                "SELECT * FROM users WHERE username = ? LIMIT 1",
                [$username]
            );

            if (empty($result)) {
                // Create new LDAP user
                $this->db->execute(
                    "INSERT INTO users (username, usertype, pass) VALUES (?, ?, ?)",
                    [$username, 2, ''] // LDAP user type, no local password
                );

                $userId = $this->db->getLastInsertId();
                $user = [
                    'id' => $userId,
                    'username' => $username,
                    'usertype' => 2,
                    'pass' => ''
                ];
            } else {
                $user = $result[0];
            }

            // Generate new cookie token
            $cookieToken = mt_rand();

            // Update user's cookie token in database
            $this->db->execute(
                "UPDATE users SET cookie1 = ? WHERE id = ?",
                [$cookieToken, $user['id']]
            );

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['usertype'];
            $_SESSION['authenticated'] = true;

            // Set cookies
            $cookiePath = $this->getCookiePath();
            setcookie('itdbcookie1', (string)$cookieToken, time() + (2 * 86400), $cookiePath);
            setcookie('itdbuser', $username, time() + (60 * 86400), $cookiePath);

            $this->logAction('ldap_login_success', ['user_id' => $user['id'], 'username' => $username]);

            // Create a simple user object for compatibility
            $userObj = (object) $user;

            return [
                'success' => true,
                'message' => 'User Authenticated',
                'user' => $userObj,
                'token' => $this->generateJwt($userObj)
            ];

        } catch (\Exception $e) {
            $this->logError('LDAP authentication error', $e, ['username' => $username]);
            return ['success' => false, 'message' => 'Authentication error'];
        }
    }

    /**
     * Check if user is authenticated via session
     */
    public function isAuthenticated(): bool
    {
        // Testing bypass - REMOVE IN PRODUCTION
        if (filter_var($_ENV['BYPASS_AUTH'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        // Check session authentication
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            return true;
        }

        // Check cookie-based authentication (legacy compatibility)
        if (isset($_COOKIE['itdbuser']) && isset($_COOKIE['itdbcookie1'])) {
            return $this->validateCookieAuth($_COOKIE['itdbuser'], $_COOKIE['itdbcookie1']);
        }

        return false;
    }

    /**
     * Validate cookie authentication (legacy compatibility)
     */
    private function validateCookieAuth(string $username, string $cookieToken): bool
    {
        try {
            $result = $this->db->fetchAll(
                "SELECT * FROM users WHERE username = ? AND cookie1 = ? LIMIT 1",
                [$username, $cookieToken]
            );

            if (empty($result)) {
                return false;
            }

            $user = $result[0];

            // Refresh session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['usertype'];
            $_SESSION['authenticated'] = true;

            // Renew cookie
            $cookiePath = $this->getCookiePath();
            setcookie('itdbcookie1', $cookieToken, time() + (2 * 86400), $cookiePath);

            return true;

        } catch (\Exception $e) {
            $this->logError('Cookie validation error', $e);
            return false;
        }
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?object
    {
        // Testing bypass - create fake admin user
        if (filter_var($_ENV['BYPASS_AUTH'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $fakeUserData = [
                'id' => 999,
                'username' => 'test_admin',
                'usertype' => 1,
                'userdesc' => 'Test Admin User'
            ];

            return new class($fakeUserData) {
                private array $data;

                public function __construct(array $data) {
                    $this->data = $data;
                }

                public function __get($name) {
                    return $this->data[$name] ?? null;
                }

                public function __isset($name) {
                    return isset($this->data[$name]);
                }

                public function isAdmin(): bool {
                    return (int) ($this->data['usertype'] ?? 0) === 1;
                }
            };
        }

        if (!$this->isAuthenticated()) {
            return null;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $result = $this->db->fetchAll(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$userId]
        );

        if (empty($result)) {
            return null;
        }

        $userData = $result[0];

        // Create a simple user object with isAdmin method
        $user = new class($userData) {
            private array $data;

            public function __construct(array $data) {
                $this->data = $data;
            }

            public function __get($name) {
                return $this->data[$name] ?? null;
            }

            public function __isset($name) {
                return isset($this->data[$name]);
            }

            public function isAdmin(): bool {
                return (int) ($this->data['usertype'] ?? 0) === 1;
            }
        };

        return $user;
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        // Clear session
        session_unset();
        session_destroy();

        // Clear cookies
        $cookiePath = $this->getCookiePath();
        setcookie('itdbcookie1', '', time() - 3600, $cookiePath);
        setcookie('itdbuser', '', time() - 3600, $cookiePath);

        $this->logAction('logout');
    }

    /**
     * Generate JWT token
     */
    private function generateJwt($user): string
    {
        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + $this->jwtExpiration,
            'username' => $user->username,
            'user_type' => $user->usertype,
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    /**
     * Validate JWT token
     */
    public function validateJwt(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            $this->logError('JWT validation failed', $e);
            return null;
        }
    }

    /**
     * Check if LDAP is enabled
     */
    private function isLdapEnabled(): bool
    {
        return filter_var($_ENV['LDAP_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Connect to LDAP server (placeholder - would need actual LDAP implementation)
     */
    private function connectToLdapServer(string $server, string $username, string $password, string $baseDn): bool
    {
        // This is a placeholder for LDAP authentication
        // In a real implementation, you would use ldap_connect, ldap_bind, etc.

        if (empty($server) || empty($username) || empty($password)) {
            return false;
        }

        // For now, return false to fall back to local authentication
        // TODO: Implement actual LDAP authentication
        return false;
    }

    /**
     * Get cookie path for the application
     */
    private function getCookiePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
        $cookiePath = dirname($scriptName);

        if (basename($cookiePath) === 'public') {
            $cookiePath = dirname($cookiePath);
        }

        return $cookiePath === '' ? '/' : $cookiePath;
    }

    /**
     * Check if current user is admin
     */
    public function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && ($user->isAdmin)();
    }

    /**
     * Require admin access
     */
    public function requireAdmin(): bool
    {
        if (!$this->isAdmin()) {
            throw new \Exception('Admin access required');
        }

        return true;
    }

    /**
     * Get user permissions (placeholder for future implementation)
     */
    public function getUserPermissions(): array
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            return [];
        }

        // Basic permissions based on user type
        $permissions = ['read'];

        if ($user->usertype >= 1) {
            $permissions[] = 'write';
        }

        if ($user->isAdmin()) {
            $permissions[] = 'admin';
            $permissions[] = 'delete';
        }

        return $permissions;
    }

}