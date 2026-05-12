<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Repository\DishRepository;
use App\Repository\MenuRepository;
use App\Repository\RestaurantTableRepository;
use App\Service\MenuRecommendationService;
use Knp\Component\Pager\PaginatorInterface;
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
        private DishRepository $dishRepository,
        private RestaurantTableRepository $tableRepository,
        private MenuRecommendationService $menuRecommendationService,
    ) {}

    #[Route('/', name: 'app_home')]
    public function home(Request $request, PaginatorInterface $paginator): Response
    {
        $session  = $this->requestStack->getSession();
        $userRole = $session->get('user_role');

        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            ...$this->buildMenuViewData($request, $paginator),
            'availableTables' => $this->tableRepository->findBy(['status' => 'AVAILABLE']),
        ]);
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            ...$this->buildMenuViewData($request, $paginator),
            'availableTables' => $this->tableRepository->findBy(['status' => 'AVAILABLE']),
        ]);
    }

    private function buildMenuViewData(Request $request, PaginatorInterface $paginator): array
    {
        $filters = [
            'budget' => $this->cleanChoice($request->query->get('budget'), ['low', 'medium', 'premium']),
            'category' => trim((string) $request->query->get('category', '')),
            'diet' => $this->cleanChoice($request->query->get('diet'), ['vegetarian', 'non_vegetarian']),
            'mood' => $this->cleanChoice($request->query->get('mood'), ['spicy', 'sweet', 'healthy', 'popular']),
            'q' => trim((string) $request->query->get('q', '')),
        ];

        $queryBuilder = $this->dishRepository->createPublicMenuQueryBuilder($filters);
        $pagination = $paginator->paginate(
            $queryBuilder,
            max(1, $request->query->getInt('page', 1)),
            6
        );

        $currentDishes = [];
        foreach ($pagination->getItems() as $item) {
            if ($item instanceof Dish) {
                $currentDishes[] = $item;
            }
        }

        return [
            'menuSections' => $this->buildMenuSections($currentDishes),
            'dishPagination' => $pagination,
            'menuHelper' => [
                'filters' => $filters,
                'categoryOptions' => $this->getMenuCategoryOptions(),
                ...$this->menuRecommendationService->summarize($filters, $currentDishes),
            ],
        ];
    }

    /**
     * @param Dish[] $dishes
     */
    private function buildMenuSections(array $dishes): array
    {
        $menuSections = [];
        foreach ($dishes as $dish) {
            $menu = $dish->getMenu();
            if (null === $menu || null === $menu->getId()) {
                continue;
            }

            $menuId = $menu->getId();
            if (!isset($menuSections[$menuId])) {
                $menuSections[$menuId] = [
                    'menu'   => [
                        'id'          => $menuId,
                        'title'       => $menu->getTitle(),
                        'description' => $menu->getDescription(),
                    ],
                    'dishes' => [],
                ];
            }

            $menuSections[$menuId]['dishes'][] = [
                'id'          => $dish->getId(),
                'name'        => $dish->getName(),
                'description' => $dish->getDescription(),
                'basePrice'   => $dish->getBase_price(),
                'imageUrl'    => $dish->getImageUrl() ?? null,
            ];
        }

        return array_values($menuSections);
    }

    private function getMenuCategoryOptions(): array
    {
        return $this->menuRepository->createQueryBuilder('m')
            ->select('m.title')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.title', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    private function cleanChoice(mixed $value, array $allowed): string
    {
        $choice = trim((string) $value);

        return in_array($choice, $allowed, true) ? $choice : '';
    }
}
