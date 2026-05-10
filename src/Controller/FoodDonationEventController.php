<?php

namespace App\Controller;

use App\Entity\FoodDonationEvent;
use App\Form\FoodDonationEventType;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use App\Repository\RatingRepository;
use App\Repository\UserRepository;
use App\Service\DonationItemStockService;
use App\Service\DonationOptimizationService;
use App\Service\EventNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/food/donation/event')]
final class FoodDonationEventController extends AbstractController
{
    #[Route(name: 'app_food_donation_event_index', methods: ['GET'])]
    public function index(Request $request, FoodDonationEventRepository $foodDonationEventRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $search = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', '');
        $sort = $request->query->get('sort', 'event_date');
        $direction = $request->query->get('direction', 'asc');

        $totalEvents = $foodDonationEventRepository->countAllEvents();
        $totalQuantity = $foodDonationEventRepository->sumTotalQuantity();
        $pendingCount = $foodDonationEventRepository->countByStatus('PENDING');
        $scheduledCount = $foodDonationEventRepository->countByStatus('SCHEDULED');
        $inProgressCount = $foodDonationEventRepository->countByStatus('IN_PROGRESS');
        $completedCount = $foodDonationEventRepository->countByStatus('COMPLETED');

        return $this->render('admin/food_donation_event/index.html.twig', [
            'food_donation_events' => $foodDonationEventRepository->findFilteredEvents($search, $status, $sort, $direction),
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
            'total_events' => $totalEvents,
            'total_quantity' => $totalQuantity,
            'pending_count' => $pendingCount,
            'scheduled_count' => $scheduledCount,
            'in_progress_count' => $inProgressCount,
            'completed_count' => $completedCount,
        ]);
    }

    #[Route('/new', name: 'app_food_donation_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EventNotificationService $notificationService, UserRepository $userRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $foodDonationEvent = new FoodDonationEvent();
        $form = $this->createForm(FoodDonationEventType::class, $foodDonationEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $now = new \DateTimeImmutable();
            $foodDonationEvent->setCreated_at($now);
            $foodDonationEvent->setUpdated_at($now);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($foodDonationEvent);
            $entityManager->flush();

            $userId = (int) $request->getSession()->get('user_id', 0);
            if ($userId > 0) {
                $user = $userRepository->find($userId);
                if ($user) {
                    $notificationService->notifyEventCreated($user, $foodDonationEvent);
                    $entityManager->flush();
                }
            }

            $this->addFlash('success', 'Donation event created successfully.');
            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/food_donation_event/new.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_event_show', methods: ['GET'])]
    public function show(Request $request, FoodDonationEvent $foodDonationEvent, FoodDonationItemRepository $foodDonationItemRepository, RatingRepository $ratingRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $items = [];
        if (null !== $foodDonationEvent->getDonationEventId()) {
            $items = $foodDonationItemRepository->findItemsWithDishNames($foodDonationEvent->getDonationEventId());
        }

        $avgEvent = $ratingRepository->getAverageEventRating($foodDonationEvent);
        $avgFood = $ratingRepository->getAverageFoodRating($foodDonationEvent);
        $ratingCount = $ratingRepository->getRatingCount($foodDonationEvent);
        $ratings = $ratingRepository->findRatingsWithUsers($foodDonationEvent);

        return $this->render('admin/food_donation_event/show.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'event_items' => $items,
            'avg_event_rating' => $avgEvent,
            'avg_food_rating' => $avgFood,
            'rating_count' => $ratingCount,
            'ratings' => $ratings,
        ]);
    }

    #[Route('/{donation_event_id}/edit', name: 'app_food_donation_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FoodDonationEvent $foodDonationEvent, EntityManagerInterface $entityManager, EventNotificationService $notificationService, UserRepository $userRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $previousStatus = $foodDonationEvent->getStatus();
        $form = $this->createForm(FoodDonationEventType::class, $foodDonationEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $foodDonationEvent->setUpdated_at(new \DateTimeImmutable());
            $entityManager->flush();

            $newStatus = $foodDonationEvent->getStatus();
            if ($previousStatus !== $newStatus) {
                $userId = (int) $request->getSession()->get('user_id', 0);
                if ($userId > 0) {
                    $user = $userRepository->find($userId);
                    if ($user) {
                        $notificationService->notifyEventStatusChanged($user, $foodDonationEvent, $newStatus);
                        $entityManager->flush();
                    }
                }
            }

            $this->addFlash('success', 'Donation event updated successfully.');
            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/food_donation_event/edit.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_event_delete', methods: ['POST'])]
    public function delete(Request $request, FoodDonationEvent $foodDonationEvent, EntityManagerInterface $entityManager, FoodDonationItemRepository $foodDonationItemRepository, DonationItemStockService $stockService): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if ($this->isCsrfTokenValid('delete'.$foodDonationEvent->getDonation_event_id(), $request->request->get('_token'))) {
            $connection = $entityManager->getConnection();
            $connection->beginTransaction();

            try {
                if (null !== $foodDonationEvent->getDonationEventId()) {
                    $items = $foodDonationItemRepository->findBy([
                        'donation_event_id' => $foodDonationEvent->getDonationEventId(),
                    ]);

                    foreach ($items as $item) {
                        $stockService->restoreForDish((int) $item->getItem_id(), (int) $item->getQuantity());
                        $entityManager->remove($item);
                    }
                }

                $entityManager->remove($foodDonationEvent);
                $entityManager->flush();
                $connection->commit();

                $this->addFlash('success', 'Donation event deleted successfully.');
            } catch (\Throwable $e) {
                $connection->rollBack();
                $this->addFlash('error', 'Failed to delete event: '.$e->getMessage());
            }
        }

        return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyUnlessAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }

    #[Route('/recommendations', name: 'app_food_donation_event_recommendations', methods: ['GET'])]
    public function recommendations(Request $request, DonationOptimizationService $optimizationService): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $nearExpiryDays = max(0, (int) $request->query->get('days', 3));
        $recommendations = $optimizationService->getRecommendations($nearExpiryDays);

        return $this->render('admin/food_donation_event/recommendations.html.twig', [
            'near_expiry_days' => $nearExpiryDays,
            'recommendations' => $recommendations,
        ]);
    }
}
