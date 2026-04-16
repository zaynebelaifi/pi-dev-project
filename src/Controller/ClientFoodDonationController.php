<?php

namespace App\Controller;

use App\Entity\EventRating;
use App\Entity\User;
use App\Repository\EventRatingRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientFoodDonationController extends AbstractController
{
    public function __construct(
        private FoodDonationEventRepository $foodDonationEventRepository,
        private EventRatingRepository $eventRatingRepository,
        private FoodDonationItemRepository $foodDonationItemRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/client/food-donation/calendar', name: 'app_client_food_donation_calendar')]
    public function calendar(): Response
    {
        $events = $this->foodDonationEventRepository->findBy([], ['event_date' => 'ASC']);

        $eventsData = array_map(static function ($event) {
            $date = $event->getEvent_date();
            return [
                'id' => $event->getDonation_event_id(),
                'charityName' => $event->getCharity_name(),
                'status' => $event->getStatus(),
                'eventDate' => $date ? $date->format('Y-m-d H:i') : null,
                'eventDateKey' => $date ? $date->format('Y-m-d') : null,
                'totalQuantity' => $event->getTotal_quantity(),
                'deliveryId' => $event->getDelivery_id(),
            ];
        }, $events);

        return $this->render('client_food_donation/calendar.html.twig', [
            'eventsJson' => json_encode($eventsData),
        ]);
    }

    #[Route('/client/food-donation/event/{id}', name: 'app_client_food_donation_show')]
    public function show(int $id): Response
    {
        $event = $this->foodDonationEventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Food donation event not found.');
        }

        try {
            $ratings = $this->eventRatingRepository->findByDonationEvent($event);
        } catch (TableNotFoundException) {
            $ratings = [];
        }

        $ratingCount = count($ratings);
        $averageEventRating = $ratingCount > 0 ? round(array_sum(array_map(fn(EventRating $rating) => $rating->getEventRating() ?? 0, $ratings)) / $ratingCount, 1) : null;
        $averageFoodRating = $ratingCount > 0 ? round(array_sum(array_map(fn(EventRating $rating) => $rating->getFoodRating() ?? 0, $ratings)) / $ratingCount, 1) : null;
        $availableDishes = $this->foodDonationItemRepository->findByDonationEventId($event->getDonationEventId());

        return $this->render('client_food_donation/show.html.twig', [
            'event' => $event,
            'ratings' => $ratings,
            'ratingCount' => $ratingCount,
            'averageEventRating' => $averageEventRating,
            'averageFoodRating' => $averageFoodRating,
            'availableDishes' => $availableDishes,
        ]);
    }

    #[Route('/client/food-donation/event/{id}/rate', name: 'app_client_food_donation_rate', methods: ['POST'])]
    public function rate(Request $request, int $id): Response
    {
        $event = $this->foodDonationEventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Food donation event not found.');
        }

        $user = $this->resolveCurrentUser($request);

        if ($user instanceof User && $this->eventRatingRepository->findOneByEventAndUser($event, $user)) {
            $this->addFlash('error', 'You have already rated this event');
            return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
        }

        if (!$this->isCsrfTokenValid('rate-event'.$event->getDonationEventId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid form submission. Please try again.');
            return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
        }

        $eventRatingValue = (int) $request->request->get('eventRating');
        $foodRatingValue = (int) $request->request->get('foodRating');
        $comment = trim($request->request->get('comment', '')) ?: null;

        if ($eventRatingValue < 1 || $eventRatingValue > 5 || $foodRatingValue < 1 || $foodRatingValue > 5) {
            $this->addFlash('error', 'Please submit a valid rating between 1 and 5 stars.');
            return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
        }

        $rating = new EventRating();
        $rating->setDonationEvent($event)
            ->setEventRating($eventRatingValue)
            ->setFoodRating($foodRatingValue)
            ->setComment($comment)
            ->setCreatedAt(new \DateTimeImmutable());

        if ($user instanceof User) {
            $rating->setUser($user);
        }

        try {
            $this->entityManager->persist($rating);
            $this->entityManager->flush();
        } catch (TableNotFoundException | NotNullConstraintViolationException) {
            $this->addFlash('error', 'Ratings are temporarily unavailable. Please try again after the database migration is applied.');
            return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
        }

        $this->addFlash('success', 'Your rating has been submitted successfully');
        return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
    }

    private function resolveCurrentUser(Request $request): ?User
    {
        $securityUser = $this->getUser();
        if ($securityUser instanceof User) {
            return $securityUser;
        }

        $sessionUserId = $request->getSession()->get('user_id');
        if (!is_numeric($sessionUserId)) {
            return null;
        }

        return $this->userRepository->find((int) $sessionUserId);
    }
}
