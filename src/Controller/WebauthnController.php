<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Annotation\Route;

final class WebauthnController extends AbstractController
{
    public function __construct(
        private HttpKernelInterface $httpKernel,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/webauthn/register/start', name: 'app_webauthn_register_start', methods: ['GET'])]
    public function registerStart(Request $request): Response
    {
        if (!$request->isSecure() && $request->getHost() !== 'localhost' && $request->getHost() !== '127.0.0.1') {
            return new JsonResponse([
                'success' => false,
                'message' => 'HTTPS is required for WebAuthn registration.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->forwardAsPost($request, '/api/auth/webauthn/register/start', []);
    }

    #[Route('/webauthn/register/complete', name: 'app_webauthn_register_complete', methods: ['POST'])]
    public function registerComplete(Request $request): Response
    {
        $response = $this->forwardAsPost($request, '/api/auth/webauthn/register/finish', $this->extractJsonPayload($request));

        if ($response->getStatusCode() >= 400) {
            $this->logger->warning('WebAuthn registration failed.', [
                'ip' => $request->getClientIp(),
                'statusCode' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }

    #[Route('/webauthn/auth/start', name: 'app_webauthn_auth_start', methods: ['GET'])]
    public function authStart(Request $request): Response
    {
        if (!$request->isSecure() && $request->getHost() !== 'localhost' && $request->getHost() !== '127.0.0.1') {
            return new JsonResponse([
                'success' => false,
                'message' => 'HTTPS is required for WebAuthn authentication.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $username = trim((string) $request->query->get('username', ''));

        return $this->forwardAsPost($request, '/api/auth/webauthn/login/start', [
            'username' => $username !== '' ? $username : null,
        ]);
    }

    #[Route('/webauthn/auth/complete', name: 'app_webauthn_auth_complete', methods: ['POST'])]
    public function authComplete(Request $request): Response
    {
        $response = $this->forwardAsPost($request, '/api/auth/webauthn/login/finish', $this->extractJsonPayload($request));

        if ($response->getStatusCode() >= 400) {
            $this->logger->warning('WebAuthn authentication failed.', [
                'ip' => $request->getClientIp(),
                'statusCode' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function forwardAsPost(Request $parentRequest, string $path, array $payload): Response
    {
        $content = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($content)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unable to encode request payload.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $subRequest = Request::create(
            $path,
            'POST',
            [],
            $parentRequest->cookies->all(),
            [],
            array_replace($parentRequest->server->all(), [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ]),
            $content
        );

        if ($parentRequest->hasSession()) {
            $subRequest->setSession($parentRequest->getSession());
        }

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    }

    /**
     * @return array<string,mixed>
     */
    private function extractJsonPayload(Request $request): array
    {
        $decoded = json_decode((string) $request->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
