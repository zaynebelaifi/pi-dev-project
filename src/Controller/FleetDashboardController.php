<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FleetDashboardController extends AbstractController
{
    /**
     * Fleet Management Dashboard
     * GET /admin/fleet
     */
    #[Route('/admin/fleet', name: 'fleet_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('fleet/dashboard.html.twig', [
            'tunis_lat' => 36.8065,
            'tunis_lng' => 10.1815,
        ]);
    }

    /**
     * Fleet assignment management page
     * GET /admin/fleet/assignments
     */
    #[Route('/admin/fleet/assignments', name: 'fleet_assignments_dashboard', methods: ['GET'])]
    public function assignments(Request $request): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('fleet/assignments.html.twig');
    }
}
