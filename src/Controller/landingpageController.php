<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\RestaurantTableRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class landingpageController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
        private MenuRepository $menuRepository,
        private RestaurantTableRepository $tableRepository,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(Request $request): Response
    {
        $session = $this->requestStack->getSession();
        if ($session->get('user_role') === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->renderLandingPage();
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(Request $request): Response
    {
        return $this->renderLandingPage();
    }

    private function renderLandingPage(): Response
    {
        return $this->render('landingpage/index.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $this->buildMenuSections(),
            'availableTables' => $this->tableRepository->findBy(['status' => 'AVAILABLE']),
        ]);
    }

    /**
     * @return array<int, array{menu: array<string, mixed>, dishes: array<int, array<string, mixed>>}>
     */
    private function buildMenuSections(): array
    {
        $menus = $this->menuRepository->createQueryBuilder('m')
            ->andWhere('m.isActive = :active OR m.isActive IS NULL')
            ->setParameter('active', true)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();

        if (!$menus) {
            $menus = $this->menuRepository->createQueryBuilder('m')
                ->orderBy('m.created_at', 'ASC')
                ->getQuery()
                ->getResult();
        }

        $menuSections = [];
        foreach ($menus as $menu) {
            $dishes = [];
            foreach ($menu->getDishs() as $dish) {
                $available = $dish->isAvailable();
                if ($available === null || $available) {
                    $dishes[] = [
                        'id' => $dish->getId(),
                        'name' => $dish->getName(),
                        'description' => $dish->getDescription(),
                        'basePrice' => $dish->getBase_price(),
                        'imageUrl' => $dish->getImageUrl() ?? null,
                    ];
                }
            }

            $menuSections[] = [
                'menu' => [
                    'id' => $menu->getId(),
                    'title' => $menu->getTitle(),
                    'description' => $menu->getDescription(),
                ],
                'dishes' => $dishes,
            ];
        }

        return $menuSections;
    }
}