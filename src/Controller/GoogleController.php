<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * GoogleController handles the initial redirect to Google and the callback.
 */
class GoogleController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process.
     */
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        // Guard: if credentials are placeholder values, show a friendly message
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        if (empty($clientId) || $clientId === 'CHANGE_ME') {
            $this->addFlash('warning', 'Google login is not configured yet. Please log in with your email and password.');
            return $this->redirectToRoute('app_login');
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile' // scopes
            ]);
    }

    /**
     * After going to Google, you're redirected back here because this is the "redirect_route"
     * configured in knpu_oauth2_client.yaml.
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request): Response
    {
        // This method can be blank - it will be intercepted by the GoogleAuthenticator.
        return $this->redirectToRoute('app_home');
    }
}
