<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class landingpageController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
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
