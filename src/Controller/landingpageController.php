<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    )
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