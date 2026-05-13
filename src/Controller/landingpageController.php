<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\MenuRepository;
use App\Repository\RestaurantTableRepository;
use App\Repository\UserRepository;
use App\Service\OpenWeatherMapService;
use App\Utils\DishImageCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class landingpageController extends AbstractController
{
    private const MENU_PAGE_SIZE = 4;

    private const DEMO_MENU_TITLES = [
        1 => 'Signature Breakfast',
        2 => 'Artisan Coffee',
        3 => 'Fresh Salads',
        4 => 'Gourmet Burgers',
        5 => 'Pasta & Risotto',
        6 => 'Main Courses',
        7 => 'Sharing Plates',
        8 => 'Desserts',
        9 => 'Mocktails & Coolers',
        10 => 'Signature Drinks',
        11 => 'Late Night Bites',
        12 => 'Bakery Selection',
    ];

    private const DEMO_DISH_TITLES = [
        1 => ['Pistachio French Toast', 'Truffle Omelette Croissant', 'Sunrise Avocado Tartine', 'Big 4 Breakfast Board'],
        2 => ['Velvet Cappuccino', 'Salted Caramel Latte', 'Spanish Iced Latte', 'Mocha Royale'],
        3 => ['Grilled Chicken Caesar', 'Burrata Garden Salad', 'Quinoa Citrus Bowl', 'Smoked Salmon Greens'],
        4 => ['Angus Lounge Burger', 'Crispy Chicken Burger', 'Truffle Mushroom Burger', 'Halloumi Veggie Burger'],
        5 => ['Truffle Mushroom Pasta', 'Creamy Chicken Alfredo', 'Seafood Arrabbiata', 'Parmesan Risotto'],
        6 => ['Herb-Grilled Salmon', 'Pepper Steak Frites', 'Chicken Supreme', 'Rosemary Lamb Chops'],
        7 => ['Mediterranean Mezze Board', 'Crispy Calamari', 'Loaded Truffle Fries', 'Golden Chicken Tenders'],
        8 => ['Chocolate Lava Cake', 'Tiramisu Glass', 'Pistachio Cheesecake', 'Vanilla Creme Brulee'],
        9 => ['Passion Mojito', 'Berry Sparkler', 'Citrus Cooler', 'Peach Iced Tea'],
        10 => ['Rose Latte', 'Salted Caramel Frappe', 'Matcha Cloud', 'Classic Iced Americano'],
        11 => ['Turkey Club Sandwich', 'Grilled Chicken Wrap', 'Tuna Melt Panini', 'Margherita Flatbread'],
        12 => ['Butter Croissant', 'Almond Danish', 'Pain au Chocolat', 'Cinnamon Roll'],
    ];

    public function __construct(
        private RequestStack $requestStack,
        private MenuRepository $menuRepository,
        private FoodDonationEventRepository $foodDonationEventRepository,
        private EventRegistrationRepository $eventRegistrationRepository,
        private RestaurantTableRepository $restaurantTableRepository,
        private UserRepository $userRepository,
        private OpenWeatherMapService $openWeatherMapService,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $session = $this->requestStack->getSession();
        $userRole = $session->get('user_role');

        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->renderLandingPage();
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(): Response
    {
        return $this->renderLandingPage();
    }

    private function renderLandingPage(): Response
    {
        $session = $this->requestStack->getSession();
        $user = $this->getUser();
        if (!$user instanceof User) {
            $sessionUserId = $session->get('user_id');
            if (is_numeric($sessionUserId)) {
                try {
                    $user = $this->userRepository->find((int) $sessionUserId);
                } catch (\Throwable $exception) {
                    $user = null;
                }
            }
        }

        $isCustomer = $user instanceof User
            && in_array($user->getRole(), ['ROLE_CLIENT', 'ROLE_CUSTOMER'], true);

        try {
            $menus = $this->menuRepository->createQueryBuilder('m')
                ->where('m.isActive = :active')
                ->setParameter('active', true)
                ->orderBy('m.created_at', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Throwable $exception) {
            $menus = [];
        }

        $menuSections = [];
        $flatMenuItems = [];
        foreach ($menus as $menu) {
            $dishes = [];
            foreach ($menu->getDishs() as $dish) {
                if ($dish->isAvailable()) {
                    $displayMenuTitle = $this->resolveMenuTitle($menu->getTitle());
                    $dishData = [
                        'id' => $dish->getId(),
                        'name' => $this->resolveDishTitle($menu->getTitle(), $dish->getName()),
                        'description' => $dish->getDescription(),
                        'basePrice' => $dish->getBase_price(),
                        'imageUrl' => DishImageCatalog::resolve(
                            $dish->getImageUrl(),
                            $this->resolveDishTitle($menu->getTitle(), $dish->getName())
                        ),
                        'menuTitle' => $displayMenuTitle,
                    ];

                    $dishes[] = $dishData;
                    $flatMenuItems[] = $dishData;
                }
            }

            if (!empty($dishes)) {
                $menuSections[] = [
                    'menu' => [
                        'id' => $menu->getId(),
                        'title' => $this->resolveMenuTitle($menu->getTitle()),
                        'description' => $menu->getDescription(),
                    ],
                    'dishes' => $dishes,
                ];
            }
        }

        $donationEvents = [];
        try {
            $donationEvents = $this->foodDonationEventRepository->createQueryBuilder('e')
                ->where('e.event_date >= :now')
                ->setParameter('now', new \DateTimeImmutable('now'))
                ->orderBy('e.event_date', 'ASC')
                ->setMaxResults(6)
                ->getQuery()
                ->getResult();

            if (count($donationEvents) === 0) {
                $donationEvents = $this->foodDonationEventRepository->createQueryBuilder('e')
                    ->orderBy('e.event_date', 'DESC')
                    ->setMaxResults(6)
                    ->getQuery()
                    ->getResult();
            }
        } catch (\Throwable $exception) {
            $donationEvents = [];
        }

        $isUserLoggedIn = $user instanceof User;
        $registeredEventIds = [];
        $myRegisteredEvents = [];
        $eventIds = array_values(array_filter(array_map(
            static fn ($event): ?int => $event->getDonationEventId(),
            $donationEvents
        )));

        try {
            if ($isCustomer && $eventIds !== [] && $user instanceof User) {
                $registeredEventIds = $this->eventRegistrationRepository->findRegisteredEventIdsForUserId(
                    (int) $user->getId(),
                    $eventIds
                );
            }

            if ($isCustomer && $user instanceof User) {
                $myRegisteredEvents = $this->foodDonationEventRepository->findByRegisteredUser($user);
            }

            $myEventIds = array_values(array_unique(array_filter(array_map(
                static fn ($event): ?int => $event->getDonationEventId(),
                $myRegisteredEvents
            ))));
            $registrationCounts = $this->eventRegistrationRepository->countByEventIds(
                array_values(array_unique(array_merge($eventIds, $myEventIds)))
            );
        } catch (\Throwable $exception) {
            $registeredEventIds = [];
            $myRegisteredEvents = [];
            $registrationCounts = [];
        }
        $currentWeather = $this->openWeatherMapService->getCurrentByCity('Tunis');
        $availableTables = [];
        try {
            $availableTables = $this->restaurantTableRepository->findBy(['status' => 'AVAILABLE']);
        } catch (\Throwable $exception) {
            $availableTables = [];
        }

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
            'flatMenuItems' => $flatMenuItems,
            'menuPageSize' => self::MENU_PAGE_SIZE,
            'currentWeather' => $currentWeather,
            'availableTables' => $availableTables,
            'donationEvents' => $donationEvents,
            'isUserLoggedIn' => $isUserLoggedIn,
            'registeredEventIds' => $registeredEventIds,
            'myRegisteredEvents' => $myRegisteredEvents,
            'registrationCounts' => $registrationCounts,
        ]);
    }

    private function resolveMenuTitle(?string $title): string
    {
        $rawTitle = trim((string) $title);
        if (preg_match('/^Demo Menu\s+(\d{2})$/i', $rawTitle, $matches) === 1) {
            $menuNumber = (int) $matches[1];

            return self::DEMO_MENU_TITLES[$menuNumber] ?? $rawTitle;
        }

        return $rawTitle !== '' ? $rawTitle : 'Chef Selection';
    }

    private function resolveDishTitle(?string $menuTitle, ?string $dishTitle): string
    {
        $rawDishTitle = trim((string) $dishTitle);
        if (preg_match('/^Demo Dish\s+(\d{2})-(\d{2})$/i', $rawDishTitle, $matches) === 1) {
            $menuNumber = (int) $matches[1];
            $dishNumber = (int) $matches[2];

            return self::DEMO_DISH_TITLES[$menuNumber][$dishNumber - 1] ?? $rawDishTitle;
        }

        if ($rawDishTitle !== '') {
            return $rawDishTitle;
        }

        return $this->resolveMenuTitle($menuTitle) . ' Special';
    }
}
