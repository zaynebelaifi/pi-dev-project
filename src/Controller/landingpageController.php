<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class landingpageController extends AbstractController
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $session = $this->requestStack->getSession();
        $userRole = $session->get('user_role');

        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        // ROLE_DELIVERY_MAN and ROLE_CLIENT can view the landing page

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
        ]);
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
        ]);
    }
}
