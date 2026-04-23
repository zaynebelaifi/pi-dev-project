<?php

namespace App\Controller;

use App\Repository\MenuRepository;
<<<<<<< HEAD
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
=======
use App\Repository\RestaurantTableRepository;
use Doctrine\DBAL\Connection;
>>>>>>> ca885d35be836dd5c79d91d70d89d96a3c7663bc
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class landingpageController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
        private MenuRepository $menuRepository,
<<<<<<< HEAD
        private FoodDonationEventRepository $foodDonationEventRepository,
        private EventRegistrationRepository $eventRegistrationRepository,
    )
    {
    }
=======
        private RestaurantTableRepository $tableRepository,
        private Connection $connection,
    ) {}
>>>>>>> ca885d35be836dd5c79d91d70d89d96a3c7663bc

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $session = $this->requestStack->getSession();
        $userRole = $session->get('user_role');

        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_admin_dashboard');
        }

<<<<<<< HEAD
        $menus = $this->menuRepository->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();

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
=======
        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections'    => $this->buildMenuSections(),
            'availableTables' => $this->tableRepository->findBy(['status' => 'AVAILABLE']),
        ]);
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections'    => $this->buildMenuSections(),
            'availableTables' => $this->tableRepository->findBy(['status' => 'AVAILABLE']),
        ]);
    }

    private function buildMenuSections(): array
    {
        // Try the ORM query first; if schema/mapping is out-of-sync this can throw.
        try {
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
>>>>>>> ca885d35be836dd5c79d91d70d89d96a3c7663bc
            }

            $menuSections = [];
            foreach ($menus as $menu) {
                $dishes = [];
                foreach ($menu->getDishs() as $dish) {
                    $available = $dish->isAvailable();
                    // Treat NULL availability as available to avoid hiding dishes when schema/data is inconsistent
                    if ($available === null || $available) {
                        $dishes[] = [
                            'id'          => $dish->getId(),
                            'name'        => $dish->getName(),
                            'description' => $dish->getDescription(),
                            'basePrice'   => $dish->getBase_price(),
                            'imageUrl'    => $dish->getImageUrl() ?? null,
                        ];
                    }
                }
                // Always include the menu section even if there are no available dishes.
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
        } catch (\Throwable $e) {
            // Fallback: use DBAL raw queries to be resilient to schema/mapping drift.
        }

        // DBAL fallback: inspect columns and query raw rows.
        try {
            $sm = $this->connection->createSchemaManager();
            $columns = $sm->listTableColumns('menu');
            $colNames = array_map(fn($c) => $c->getName(), $columns);
            $activeCol = in_array('is_active', $colNames, true) ? 'is_active' : (in_array('isActive', $colNames, true) ? 'isActive' : null);
        } catch (\Throwable $e) {
            return [];
        }

        if (null === $activeCol) {
            $menuRows = $this->connection->fetchAllAssociative('SELECT id, title, description FROM menu ORDER BY created_at ASC');
        } else {
            $menuRows = $this->connection->fetchAllAssociative("SELECT id, title, description FROM menu WHERE $activeCol = 1 OR $activeCol IS NULL ORDER BY created_at ASC");
            if (!$menuRows) {
                $menuRows = $this->connection->fetchAllAssociative('SELECT id, title, description FROM menu ORDER BY created_at ASC');
            }
        }
        $menuSections = [];
        foreach ($menuRows as $mRow) {
            $dishRows = $this->connection->fetchAllAssociative('SELECT id, name, description, base_price, image_url, available FROM dish WHERE menu_id = ? ORDER BY created_at ASC', [$mRow['id']]);
            $dishes = [];
            foreach ($dishRows as $dRow) {
                if (isset($dRow['available']) && !$dRow['available']) {
                    continue;
                }
                $dishes[] = [
                    'id' => $dRow['id'],
                    'name' => $dRow['name'],
                    'description' => $dRow['description'],
                    'basePrice' => isset($dRow['base_price']) ? (float) $dRow['base_price'] : null,
                    'imageUrl' => $dRow['image_url'] ?? null,
                ];
            }
            // Always include the menu section even if there are no available dishes.
            $menuSections[] = [
                'menu' => [
                    'id' => $mRow['id'],
                    'title' => $mRow['title'],
                    'description' => $mRow['description'],
                ],
                'dishes' => $dishes,
            ];
        }

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

        $isUserLoggedIn = is_numeric($session->get('user_id'));
        $registeredEventIds = [];
        $myRegisteredEvents = [];
        $eventIds = array_values(array_filter(array_map(
            static fn ($event): ?int => $event->getDonationEventId(),
            $donationEvents
        )));

        if ($isUserLoggedIn && $eventIds !== []) {
            $registeredEventIds = $this->eventRegistrationRepository->findRegisteredEventIdsForUserId(
                (int) $session->get('user_id'),
                $eventIds
            );
        }

        if ($isUserLoggedIn) {
            $registrations = $this->eventRegistrationRepository->findForUserId((int) $session->get('user_id'));
            foreach ($registrations as $registration) {
                $event = $registration->getEvent();
                if ($event instanceof \App\Entity\FoodDonationEvent) {
                    $myRegisteredEvents[] = $event;
                }
            }
        }

        $myEventIds = array_values(array_unique(array_filter(array_map(
            static fn ($event): ?int => $event->getDonationEventId(),
            $myRegisteredEvents
        ))));
        $registrationCounts = $this->eventRegistrationRepository->countByEventIds(
            array_values(array_unique(array_merge($eventIds, $myEventIds)))
        );

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
            'donationEvents' => $donationEvents,
            'isUserLoggedIn' => $isUserLoggedIn,
            'registeredEventIds' => $registeredEventIds,
            'myRegisteredEvents' => $myRegisteredEvents,
            'registrationCounts' => $registrationCounts,
        ]);
    }

    #[Route('/landingpage', name: 'app_landingpage')]
    public function index(): Response
    {
        $session = $this->requestStack->getSession();

        $menus = $this->menuRepository->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();

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

        $isUserLoggedIn = is_numeric($session->get('user_id'));
        $registeredEventIds = [];
        $myRegisteredEvents = [];
        $eventIds = array_values(array_filter(array_map(
            static fn ($event): ?int => $event->getDonationEventId(),
            $donationEvents
        )));

        if ($isUserLoggedIn && $eventIds !== []) {
            $registeredEventIds = $this->eventRegistrationRepository->findRegisteredEventIdsForUserId(
                (int) $session->get('user_id'),
                $eventIds
            );
        }

        if ($isUserLoggedIn) {
            $registrations = $this->eventRegistrationRepository->findForUserId((int) $session->get('user_id'));
            foreach ($registrations as $registration) {
                $event = $registration->getEvent();
                if ($event instanceof \App\Entity\FoodDonationEvent) {
                    $myRegisteredEvents[] = $event;
                }
            }
        }

        $myEventIds = array_values(array_unique(array_filter(array_map(
            static fn ($event): ?int => $event->getDonationEventId(),
            $myRegisteredEvents
        ))));
        $registrationCounts = $this->eventRegistrationRepository->countByEventIds(
            array_values(array_unique(array_merge($eventIds, $myEventIds)))
        );

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
            'donationEvents' => $donationEvents,
            'isUserLoggedIn' => $isUserLoggedIn,
            'registeredEventIds' => $registeredEventIds,
            'myRegisteredEvents' => $myRegisteredEvents,
            'registrationCounts' => $registrationCounts,
        ]);
    }
}