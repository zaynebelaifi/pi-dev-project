<?php

namespace App\Tests\Controller\Api;

use App\Controller\Api\LoginController;
use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginControllerTest extends TestCase
{
    public function testLoginReturnsTokenForValidCredentials(): void
    {
        $request = new Request(content: json_encode([
            'email' => 'driver@example.com',
            'password' => 'TopSecret123!',
        ], JSON_THROW_ON_ERROR));

        $user = (new User())
            ->setEmail('driver@example.com')
            ->setRole('ROLE_DELIVERY_MAN')
            ->setPassword('hashed-password');

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findOneBy')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->with($user, 'TopSecret123!')->willReturn(true);

        $jwt = $this->createMock(JWTTokenManagerInterface::class);
        $jwt->method('create')->with($user)->willReturn('jwt-token-value');

        $controller = new LoginController();
        $response = $controller->__invoke($request, $repo, $hasher, $jwt);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('jwt-token-value', $payload['token']);
        self::assertSame('driver@example.com', $payload['user']['email']);
        self::assertContains('ROLE_DELIVERY_MAN', $payload['user']['roles']);
    }

    public function testLoginFailsForInvalidCredentials(): void
    {
        $request = new Request(content: json_encode([
            'email' => 'driver@example.com',
            'password' => 'wrong-password',
        ], JSON_THROW_ON_ERROR));

        $user = (new User())
            ->setEmail('driver@example.com')
            ->setRole('ROLE_DELIVERY_MAN')
            ->setPassword('hashed-password');

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findOneBy')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->with($user, 'wrong-password')->willReturn(false);

        $jwt = $this->createMock(JWTTokenManagerInterface::class);

        $controller = new LoginController();
        $response = $controller->__invoke($request, $repo, $hasher, $jwt);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Invalid credentials.', $payload['message']);
    }
}