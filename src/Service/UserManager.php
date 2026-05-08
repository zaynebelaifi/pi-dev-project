<?php

declare(strict_types=1);

namespace App\Service;

/**
 * UserManager — enforces business rules for the User entity.
 *
 * Business rules:
 * 1. Email must not be empty and must be a valid email address.
 * 2. Password must not be empty and must be at least 8 characters.
 * 3. Role must be one of: ROLE_ADMIN, ROLE_CLIENT, ROLE_DELIVERY.
 * 4. A banned user must not be allowed to authenticate.
 * 5. First name (if provided) must be at least 2 characters.
 * 6. Phone number (if provided) must match a basic numeric pattern.
 */
final class UserManager
{
    /** @var string[] */
    public const ALLOWED_ROLES = ['ROLE_ADMIN', 'ROLE_CLIENT', 'ROLE_DELIVERY', 'ROLE_USER'];

    public const MIN_PASSWORD_LENGTH = 8;
    public const MIN_NAME_LENGTH = 2;

    /**
     * Validates all User business rules.
     *
     * @param array{
     *     email?: string,
     *     password?: string,
     *     role?: string,
     *     banned?: bool,
     *     first_name?: string|null,
     *     phone?: string|null
     * } $data
     *
     * @throws \InvalidArgumentException on any rule violation
     */
    public function validate(array $data): bool
    {
        // Rule 1: email must be non-empty and valid
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email must not be empty.');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid email address.', $data['email'])
            );
        }

        // Rule 2: password must be non-empty, min 8 chars
        if (empty($data['password'])) {
            throw new \InvalidArgumentException('Password must not be empty.');
        }
        if (strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Password must be at least %d characters.', self::MIN_PASSWORD_LENGTH)
            );
        }

        // Rule 3: role must be an allowed value
        $role = strtoupper(trim($data['role'] ?? ''));
        if (empty($role)) {
            throw new \InvalidArgumentException('Role must not be empty.');
        }
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Role "%s" is not valid. Allowed: %s', $role, implode(', ', self::ALLOWED_ROLES))
            );
        }

        // Rule 4: banned user cannot authenticate
        if (isset($data['banned']) && $data['banned'] === true) {
            throw new \InvalidArgumentException('This account has been banned and cannot be used.');
        }

        // Rule 5: first_name must be at least 2 chars if provided
        if (isset($data['first_name'])) {
            if (strlen(trim($data['first_name'])) < self::MIN_NAME_LENGTH) {
                throw new \InvalidArgumentException(
                    sprintf('First name must be at least %d characters.', self::MIN_NAME_LENGTH)
                );
            }
        }

        // Rule 6: phone must be numeric digits only if provided
        if (isset($data['phone'])) {
            if (!preg_match('/^\+?[0-9]{7,15}$/', $data['phone'])) {
                throw new \InvalidArgumentException(
                    'Phone number must contain 7–15 digits (optional leading +).'
                );
            }
        }

        return true;
    }

    /**
     * Checks whether a user is allowed to log in (not banned).
     *
     * @throws \InvalidArgumentException if banned
     */
    public function assertCanLogin(bool $banned): void
    {
        if ($banned) {
            throw new \InvalidArgumentException('This account has been banned and cannot log in.');
        }
    }

    /**
     * Normalises the role string.
     */
    public function normaliseRole(string $role): string
    {
        $r = strtoupper(trim($role));
        return str_starts_with($r, 'ROLE_') ? $r : 'ROLE_' . $r;
    }
}
