<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AdminApiJwtDebugController extends AbstractController
{
    #[Route('/admin/api-jwt-debug', name: 'app_admin_api_jwt_debug', methods: ['GET'])]
    public function index(): Response
    {
        $session = $this->container->get('request_stack')->getSession();
        $legacyRole = strtoupper((string) $session?->get('user_role', ''));
        $isDev = (string) $this->getParameter('kernel.environment') === 'dev';

        if (!$isDev && !$this->isGranted('ROLE_ADMIN') && $legacyRole !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Admin access required.');
        }

        return $this->render('admin/api_jwt_debug.html.twig');
    }
}