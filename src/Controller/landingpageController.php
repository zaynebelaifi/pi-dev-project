<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class landingpageController extends AbstractController
{
    public function __construct(private RequestStack $requestStack, private MenuRepository $menuRepository)
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

        // Fetch active menus with dishes
        $menus = $this->menuRepository->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();

        // Format menu sections with dishes
        $menuSections = [];
        foreach ($menus as $menu) {
            $dishes = [];
            foreach ($menu->getDishs() as $dish) {
                if ($dish->isAvailable()) {
                    $dishes[] = [
                        'id' => $dish->getId(),
                        'name' => $dish->getName(),
                        'description' => $dish->getDescription(),
                        'basePrice' => $dish->getBase_price(),
                        'imageUrl' => $dish->getImageUrl() ?? null,
                    ];
                }
            }
            if (!empty($dishes)) {
                $menuSections[] = [
                    'menu' => [
                        'id' => $menu->getId(),
                        'title' => $menu->getTitle(),
                        'description' => $menu->getDescription(),
                    ],
                    'dishes' => $dishes,
                ];
            }
        }

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
        ]);
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(): Response
    {
        // Fetch active menus with dishes
        $menus = $this->menuRepository->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();

        // Format menu sections with dishes
        $menuSections = [];
        foreach ($menus as $menu) {
            $dishes = [];
            foreach ($menu->getDishs() as $dish) {
                if ($dish->isAvailable()) {
                    $dishes[] = [
                        'id' => $dish->getId(),
                        'name' => $dish->getName(),
                        'description' => $dish->getDescription(),
                        'basePrice' => $dish->getBase_price(),
                        'imageUrl' => $dish->getImageUrl() ?? null,
                    ];
                }
            }
            if (!empty($dishes)) {
                $menuSections[] = [
                    'menu' => [
                        'id' => $menu->getId(),
                        'title' => $menu->getTitle(),
                        'description' => $menu->getDescription(),
                    ],
                    'dishes' => $dishes,
                ];
            }
        }

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
        ]);
    }
}
