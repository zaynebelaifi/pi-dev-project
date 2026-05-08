<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

/**
 * UserManagerTest — validates all business rules for the User module.
 *
 * Business rules tested:
 * 1. Email must not be empty.
 * 2. Email must be a valid email address.
 * 3. Password must not be empty.
 * 4. Password must be at least 8 characters.
 * 5. Role must be one of the allowed roles.
 * 6. Banned user cannot authenticate.
 * 7. First name must be >= 2 chars if provided.
 * 8. Phone must match numeric pattern if provided.
 */
class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    // ─────────────────────────────────────────────────────────
    // HAPPY PATH
    // ─────────────────────────────────────────────────────────

    /** Test 1 — valid complete user data passes all rules */
    public function testValidUser(): void
    {
        $result = $this->manager->validate([
            'email'      => 'admin@big4.tn',
            'password'   => 'SecurePass123!',
            'role'       => 'ROLE_ADMIN',
            'banned'     => false,
            'first_name' => 'Zayne',
            'phone'      => '+21698765432',
        ]);

        $this->assertTrue($result);
    }

    /** Test 2 — valid client user without optional fields */
    public function testValidUserWithMinimalFields(): void
    {
        $result = $this->manager->validate([
            'email'    => 'client@example.com',
            'password' => 'Password1',
            'role'     => 'ROLE_CLIENT',
        ]);

        $this->assertTrue($result);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 1 — EMAIL REQUIRED
    // ─────────────────────────────────────────────────────────

    /** Test 3 — empty email throws exception */
    public function testUserWithoutEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email must not be empty.');

        $this->manager->validate([
            'email'    => '',
            'password' => 'Password1',
            'role'     => 'ROLE_CLIENT',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 2 — EMAIL FORMAT
    // ─────────────────────────────────────────────────────────

    /** Test 4 — invalid email format throws exception */
    public function testUserWithInvalidEmailFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid email address.');

        $this->manager->validate([
            'email'    => 'not-an-email',
            'password' => 'Password1',
            'role'     => 'ROLE_CLIENT',
        ]);
    }

    /** Test 5 — email missing @ throws exception */
    public function testUserWithEmailMissingAtSign(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->validate([
            'email'    => 'useratdomain.com',
            'password' => 'Password1',
            'role'     => 'ROLE_CLIENT',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 3 — PASSWORD REQUIRED
    // ─────────────────────────────────────────────────────────

    /** Test 6 — empty password throws exception */
    public function testUserWithoutPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must not be empty.');

        $this->manager->validate([
            'email'    => 'user@example.com',
            'password' => '',
            'role'     => 'ROLE_CLIENT',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 4 — PASSWORD MIN LENGTH
    // ─────────────────────────────────────────────────────────

    /** Test 7 — password too short throws exception */
    public function testUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters.');

        $this->manager->validate([
            'email'    => 'user@example.com',
            'password' => 'abc',
            'role'     => 'ROLE_CLIENT',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 5 — ROLE VALIDATION
    // ─────────────────────────────────────────────────────────

    /** Test 8 — invalid role throws exception */
    public function testUserWithInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not valid.');

        $this->manager->validate([
            'email'    => 'user@example.com',
            'password' => 'Password1',
            'role'     => 'ROLE_SUPERVILLAIN',
        ]);
    }

    /** Test 9 — empty role throws exception */
    public function testUserWithEmptyRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Role must not be empty.');

        $this->manager->validate([
            'email'    => 'user@example.com',
            'password' => 'Password1',
            'role'     => '',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 6 — BANNED USER
    // ─────────────────────────────────────────────────────────

    /** Test 10 — banned user throws exception */
    public function testBannedUserCannotAuthenticate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('banned');

        $this->manager->validate([
            'email'    => 'banned@example.com',
            'password' => 'Password1',
            'role'     => 'ROLE_CLIENT',
            'banned'   => true,
        ]);
    }

    /** Test 11 — assertCanLogin throws for banned */
    public function testAssertCanLoginThrowsForBanned(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->assertCanLogin(true);
    }

    /** Test 12 — assertCanLogin passes for non-banned */
    public function testAssertCanLoginPassesForActive(): void
    {
        $this->manager->assertCanLogin(false); // no exception
        $this->assertTrue(true); // reached here = pass
    }

    // ─────────────────────────────────────────────────────────
    // RULE 7 — FIRST NAME LENGTH
    // ─────────────────────────────────────────────────────────

    /** Test 13 — first name too short throws exception */
    public function testUserWithShortFirstName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('First name must be at least');

        $this->manager->validate([
            'email'      => 'user@example.com',
            'password'   => 'Password1',
            'role'       => 'ROLE_CLIENT',
            'first_name' => 'A',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // RULE 8 — PHONE FORMAT
    // ─────────────────────────────────────────────────────────

    /** Test 14 — invalid phone format throws exception */
    public function testUserWithInvalidPhoneFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number');

        $this->manager->validate([
            'email'    => 'user@example.com',
            'password' => 'Password1',
            'role'     => 'ROLE_CLIENT',
            'phone'    => 'not-a-phone',
        ]);
    }

    /** Test 15 — normaliseRole adds prefix correctly */
    public function testNormaliseRoleAddsPrefix(): void
    {
        $this->assertSame('ROLE_ADMIN', $this->manager->normaliseRole('admin'));
        $this->assertSame('ROLE_CLIENT', $this->manager->normaliseRole('ROLE_CLIENT'));
    }
}
