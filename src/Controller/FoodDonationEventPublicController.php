<?php

namespace App\Controller;

use App\Entity\FoodDonationEvent;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use App\Repository\RatingRepository;
use App\Repository\UserRepository;
use App\Entity\Rating;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/donations/events')]
class FoodDonationEventPublicController extends AbstractController
{
    #[Route('', name: 'app_food_donation_event_public_index', methods: ['GET'])]
    public function index(FoodDonationEventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy([], ['event_date' => 'DESC']);

        return $this->render('food_donation_event/index.html.twig', [
            'food_donation_events' => $events,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_event_public_show', methods: ['GET'])]
    public function show(
        Request $request,
        FoodDonationEvent $foodDonationEvent,
        FoodDonationItemRepository $itemRepository,
        RatingRepository $ratingRepository,
        UserRepository $userRepository,
    ): Response {
        $items = [];
        if (null !== $foodDonationEvent->getDonationEventId()) {
            $items = $itemRepository->findItemsWithDishNames($foodDonationEvent->getDonationEventId());
        }

        $avgEvent = $ratingRepository->getAverageEventRating($foodDonationEvent);
        $avgFood = $ratingRepository->getAverageFoodRating($foodDonationEvent);
        $ratingCount = $ratingRepository->getRatingCount($foodDonationEvent);
        $ratings = $ratingRepository->findRatingsWithUsers($foodDonationEvent);

        $user = null;
        $userRating = null;
        $userId = (int) $request->getSession()->get('user_id', 0);
        if ($userId > 0) {
            $user = $userRepository->find($userId);
            if ($user) {
                $userRating = $ratingRepository->findUserRating($foodDonationEvent, $user);
            }
        }

        return $this->render('food_donation_event/details.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'event_items' => $items,
            'avg_event_rating' => $avgEvent,
            'avg_food_rating' => $avgFood,
            'rating_count' => $ratingCount,
            'ratings' => $ratings,
            'current_user' => $user,
            'user_rating' => $userRating,
        ]);
    }

    #[Route('/{donation_event_id}/rate', name: 'app_food_donation_event_public_rate', methods: ['POST'])]
    public function rate(
        Request $request,
        FoodDonationEvent $foodDonationEvent,
        RatingRepository $ratingRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $userId = (int) $request->getSession()->get('user_id', 0);
        if ($userId <= 0) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('rate_event_'.$foodDonationEvent->getDonationEventId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');
            return $this->redirectToRoute('app_food_donation_event_public_show', [
                'donation_event_id' => $foodDonationEvent->getDonationEventId(),
            ]);
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            $this->addFlash('error', 'User account not found.');
            return $this->redirectToRoute('app_food_donation_event_public_show', [
                'donation_event_id' => $foodDonationEvent->getDonationEventId(),
            ]);
        }

        $eventRating = (int) $request->request->get('event_rating', 0);
        $foodRating = (int) $request->request->get('food_rating', 0);
        $comment = trim((string) $request->request->get('comment', ''));

        if ($eventRating < 1 || $eventRating > 5 || $foodRating < 1 || $foodRating > 5) {
            $this->addFlash('error', 'Please provide valid ratings (1-5) for both event and food.');
            return $this->redirectToRoute('app_food_donation_event_public_show', [
                'donation_event_id' => $foodDonationEvent->getDonationEventId(),
            ]);
        }

        $existing = $ratingRepository->findUserRating($foodDonationEvent, $user);
        if ($existing instanceof Rating) {
            $existing->setEventRating($eventRating);
            $existing->setFoodRating($foodRating);
            $existing->setComment($comment !== '' ? $comment : null);
        } else {
            $rating = new Rating();
            $rating->setEvent($foodDonationEvent);
            $rating->setUser($user);
            $rating->setEventRating($eventRating);
            $rating->setFoodRating($foodRating);
            $rating->setComment($comment !== '' ? $comment : null);
            $entityManager->persist($rating);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Your rating has been saved.');

        return $this->redirectToRoute('app_food_donation_event_public_show', [
            'donation_event_id' => $foodDonationEvent->getDonationEventId(),
        ]);
    }

    #[Route('/{donation_event_id}/rate/delete', name: 'app_food_donation_event_public_rate_delete', methods: ['POST'])]
    public function deleteRating(
        Request $request,
        FoodDonationEvent $foodDonationEvent,
        RatingRepository $ratingRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $userId = (int) $request->getSession()->get('user_id', 0);
        if ($userId <= 0) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('delete_rating_'.$foodDonationEvent->getDonationEventId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');
            return $this->redirectToRoute('app_food_donation_event_public_show', [
                'donation_event_id' => $foodDonationEvent->getDonationEventId(),
            ]);
        }

        $user = $userRepository->find($userId);
        if ($user) {
            $rating = $ratingRepository->findUserRating($foodDonationEvent, $user);
            if ($rating) {
                $entityManager->remove($rating);
                $entityManager->flush();
                $this->addFlash('success', 'Your rating has been removed.');
            }
        }

        return $this->redirectToRoute('app_food_donation_event_public_show', [
            'donation_event_id' => $foodDonationEvent->getDonationEventId(),
        ]);
    }
}
