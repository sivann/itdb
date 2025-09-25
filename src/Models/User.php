<?php

declare(strict_types=1);

namespace App\Models;


class User extends BaseModel
{
    protected $table = 'users';

    protected $fillable = [
        'username',
        'userdesc',
        'pass',
        'usertype',
        'cookie1'
    ];


    /**
     * Get the user's full name or username
     */
    public function getDisplayName(): string
    {
        return $this->userdesc ?: $this->username;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->usertype === 1;
    }

    /**
     * Check if user is LDAP user
     */
    public function isLdapUser(): bool
    {
        return $this->usertype === 2;
    }


    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        // Current ITDB uses plain text comparison
        // In production, this should use password_verify()
        return $this->pass === $password;
    }

    /**
     * Generate new cookie token
     */
    public function generateCookieToken(): string
    {
        return (string) mt_rand();
    }

    /**
     * Verify cookie token
     */
    public function verifyCookieToken(string $token): bool
    {
        return $this->cookie1 === $token;
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'username' => 'required|string|min:3|max:50',
            'pass' => 'required|string|min:4',
            'userdesc' => 'string|max:100',
            'usertype' => 'integer|min:0|max:2'
        ];
    }

    /**
     * Get name (alias for username)
     */
    public function getName(): ?string
    {
        return $this->username;
    }
}