<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\MenuRepository;
use App\Repository\RestaurantTableRepository;
use App\Repository\UserRepository;
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
        private FoodDonationEventRepository $foodDonationEventRepository,
        private EventRegistrationRepository $eventRegistrationRepository,
        private RestaurantTableRepository $restaurantTableRepository,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(Request $request): Response
    {
        $session = $this->requestStack->getSession();
        $userRole = $session->get('user_role');

        if ($userRole === 'ROLE_ADMIN') {
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
        $session = $this->requestStack->getSession();
        $user = $this->getUser();
        if (!$user instanceof User) {
            $sessionUserId = $session->get('user_id');
            if (is_numeric($sessionUserId)) {
                $user = $this->userRepository->find((int) $sessionUserId);
            }
        }

        $isCustomer = $user instanceof User
            && in_array($user->getRole(), ['ROLE_CLIENT', 'ROLE_CUSTOMER'], true);

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

        $isUserLoggedIn = $user instanceof User;
        $registeredEventIds = [];
        $myRegisteredEvents = [];
        $eventIds = array_values(array_filter(array_map(
            static fn ($event): ?int => $event->getDonationEventId(),
            $donationEvents
        )));

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

        return $this->render('base.html.twig', [
            'controller_name' => 'landingpageController',
            'menuSections' => $menuSections,
            'availableTables' => $this->restaurantTableRepository->findBy(['status' => 'AVAILABLE']),
            'donationEvents' => $donationEvents,
            'isUserLoggedIn' => $isUserLoggedIn,
            'registeredEventIds' => $registeredEventIds,
            'myRegisteredEvents' => $myRegisteredEvents,
            'registrationCounts' => $registrationCounts,
        ]);
    }
}
