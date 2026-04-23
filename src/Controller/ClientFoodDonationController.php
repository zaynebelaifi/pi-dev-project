<?php

namespace App\Controller;

use App\Entity\EventRating;
use App\Entity\EventRegistration;
use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Repository\EventRatingRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use App\Repository\UserRepository;
use App\Service\TwilioSmsService;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ClientFoodDonationController extends AbstractController
{
    public function __construct(
        private FoodDonationEventRepository $foodDonationEventRepository,
        private EventRatingRepository $eventRatingRepository,
        private EventRegistrationRepository $eventRegistrationRepository,
        private FoodDonationItemRepository $foodDonationItemRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TwilioSmsService $twilioSmsService,
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
    public function show(Request $request, int $id): Response
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

        $eventScoreRatings = array_values(array_filter($ratings, static fn (EventRating $rating): bool => $rating->getEventRating() !== null));
        $foodScoreRatings = array_values(array_filter($ratings, static fn (EventRating $rating): bool => $rating->getFoodRating() !== null));

        $averageEventRating = count($eventScoreRatings) > 0
            ? round(array_sum(array_map(static fn (EventRating $rating): int => (int) $rating->getEventRating(), $eventScoreRatings)) / count($eventScoreRatings), 1)
            : null;

        $averageFoodRating = count($foodScoreRatings) > 0
            ? round(array_sum(array_map(static fn (EventRating $rating): int => (int) $rating->getFoodRating(), $foodScoreRatings)) / count($foodScoreRatings), 1)
            : null;
        $availableDishes = $this->foodDonationItemRepository->findByDonationEventId($event->getDonationEventId());
        $normalizedStatus = $this->normalizeEventStatus($event->getStatus());
        $canRate = $this->canRateForStatus($normalizedStatus);
        $isCancelled = $normalizedStatus === FoodDonationEvent::STATUS_CANCELLED;
        $currentUser = $this->resolveCurrentUser($request);
        $currentUserId = $currentUser?->getId();
        $isAdmin = $request->getSession()->get('user_role') === 'ROLE_ADMIN';

        $userRole = $request->getSession()->get('user_role');
        $isCustomer = $userRole === 'ROLE_CLIENT';
        $isAdmin = $userRole === 'ROLE_ADMIN';
        $isRegistered = $isCustomer && $currentUser instanceof User
            ? $this->eventRegistrationRepository->isUserRegisteredForEvent((int) $currentUser->getId(), (int) $event->getDonationEventId())
            : false;

        return $this->render('client_food_donation/show.html.twig', [
            'event' => $event,
            'ratings' => $ratings,
            'ratingCount' => $ratingCount,
            'averageEventRating' => $averageEventRating,
            'averageFoodRating' => $averageFoodRating,
            'availableDishes' => $availableDishes,
            'normalizedStatus' => $normalizedStatus,
            'canRate' => $canRate,
            'isCancelled' => $isCancelled,
            'currentUserId' => $currentUserId,
            'isAdmin' => $isAdmin,
            'isCustomer' => $isCustomer,
            'isRegistered' => $isRegistered,
        ]);
    }

    #[Route('/client/food-donation/my-registrations', name: 'app_client_food_donation_my_registrations')]
    public function myRegistrations(Request $request): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $user = $this->resolveCurrentUser($request);

        if (!$user instanceof User || $userRole !== 'ROLE_CLIENT') {
            return $this->redirectToRoute('app_login');
        }

        $registrations = $this->eventRegistrationRepository->findForUserId((int) $user->getId());

        return $this->render('client_food_donation/my_registrations.html.twig', [
            'registrations' => $registrations,
        ]);
    }

    #[Route('/client/food-donation/event/{id}/register', name: 'app_client_food_donation_register', methods: ['POST'])]
    public function register(Request $request, int $id): Response
    {
        $isAjaxRequest = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        $event = $this->foodDonationEventRepository->find($id);
        if (!$event instanceof FoodDonationEvent) {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Event not found.'], Response::HTTP_NOT_FOUND);
            }

            $this->addFlash('error', 'Event not found.');

            return $this->redirectToRoute('app_client_food_donation_calendar');
        }

        $userRole = $request->getSession()->get('user_role');
        $user = $this->resolveCurrentUser($request);
        if (!$user instanceof User) {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Please log in to register for events.'], Response::HTTP_UNAUTHORIZED);
            }

            $this->addFlash('error', 'Please log in to register for events.');

            return $this->redirectToRoute('app_login');
        }

        if ($userRole !== 'ROLE_CLIENT') {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Only customers can register for events.'], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('error', 'Only customers can register for events.');
            return $this->redirectToRoute('app_home');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('register-event' . $event->getDonationEventId(), $token)) {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid form submission. Please try again.'], Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('error', 'Invalid form submission. Please try again.');

            return $this->redirectToRoute('app_home');
        }

        $normalizedStatus = $this->normalizeEventStatus($event->getStatus());
        if ($normalizedStatus !== FoodDonationEvent::STATUS_SCHEDULED) {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Registration is only available for scheduled events.'], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Registration is only available for scheduled events.');

            return $this->redirectToRoute('app_home');
        }

        if ($this->eventRegistrationRepository->isUserRegisteredForEvent((int) $user->getId(), (int) $event->getDonationEventId())) {
            if ($isAjaxRequest) {
                return new JsonResponse([
                    'success' => true,
                    'already_registered' => true,
                    'message' => 'You are already registered for this event.',
                ]);
            }

            $this->addFlash('success', 'You are already registered for this event.');

            return $this->redirectToRoute('app_home');
        }

        $registration = (new EventRegistration())
            ->setEvent($event)
            ->setUser($user)
            ->setCreatedAt(new \DateTimeImmutable('now'));

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        $smsSent = false;
        try {
            $smsSent = $this->twilioSmsService->sendRegistrationConfirmationSms($user, $event);
        } catch (\Throwable) {
            $smsSent = false;
        }

        $this->addFlash('success', 'You are now registered for this event.');

        if ($isAjaxRequest) {
            $registrationCount = (int) ($this->eventRegistrationRepository->countByEventIds([(int) $event->getDonationEventId()])[(int) $event->getDonationEventId()] ?? 0);

            return new JsonResponse([
                'success' => true,
                'registered' => true,
                'registration_count' => $registrationCount,
                'sms_sent' => $smsSent,
                'message' => 'You are now registered for this event.',
            ]);
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/client/food-donation/event/{id}/unregister', name: 'app_client_food_donation_unregister', methods: ['POST'])]
    public function unregister(Request $request, int $id): Response
    {
        $isAjaxRequest = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        $event = $this->foodDonationEventRepository->find($id);
        if (!$event instanceof FoodDonationEvent) {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Event not found.'], Response::HTTP_NOT_FOUND);
            }

            $this->addFlash('error', 'Event not found.');

            return $this->redirectToRoute('app_home');
        }

        $user = $this->resolveCurrentUser($request);
        if (!$user instanceof User) {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Please log in to manage registrations.'], Response::HTTP_UNAUTHORIZED);
            }

            $this->addFlash('error', 'Please log in to manage registrations.');

            return $this->redirectToRoute('app_login');
        }

        $userRole = $request->getSession()->get('user_role');
        if ($userRole !== 'ROLE_CLIENT') {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Only customers can manage event registrations.'], Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('error', 'Only customers can manage event registrations.');

            return $this->redirectToRoute('app_home');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('unregister-event' . $event->getDonationEventId(), $token)) {
            if ($isAjaxRequest) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid form submission. Please try again.'], Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('error', 'Invalid form submission. Please try again.');

            return $this->redirectToRoute('app_home');
        }

        $registration = $this->eventRegistrationRepository->findOneBy([
            'event' => $event,
            'user' => $user,
        ]);

        if (!$registration instanceof EventRegistration) {
            if ($isAjaxRequest) {
                return new JsonResponse([
                    'success' => true,
                    'registered' => false,
                    'registration_count' => (int) ($this->eventRegistrationRepository->countByEventIds([(int) $event->getDonationEventId()])[(int) $event->getDonationEventId()] ?? 0),
                    'message' => 'You are not registered for this event.',
                ]);
            }

            $this->addFlash('error', 'You are not registered for this event.');

            return $this->redirectToRoute('app_home');
        }

        $this->entityManager->remove($registration);
        $this->entityManager->flush();

        $registrationCount = (int) ($this->eventRegistrationRepository->countByEventIds([(int) $event->getDonationEventId()])[(int) $event->getDonationEventId()] ?? 0);

        $this->addFlash('success', 'You have been unregistered from this event.');

        if ($isAjaxRequest) {
            return new JsonResponse([
                'success' => true,
                'registered' => false,
                'registration_count' => $registrationCount,
                'message' => 'You have been unregistered from this event.',
            ]);
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/client/food-donation/event/{id}/rate', name: 'app_client_food_donation_rate', methods: ['POST'])]
    public function rate(Request $request, int $id): Response
    {
        $event = $this->foodDonationEventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Food donation event not found.');
        }

        $normalizedStatus = $this->normalizeEventStatus($event->getStatus());
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

        if (!$this->canRateForStatus($normalizedStatus)) {
            if ($comment === null) {
                $this->addFlash('error', 'Please add a comment before submitting.');
                return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
            }

            $rating = new EventRating();
            $rating->setDonationEvent($event)
                ->setEventRating(null)
                ->setFoodRating(null)
                ->setComment($comment)
                ->setCreatedAt(new \DateTimeImmutable());

            if ($user instanceof User) {
                $rating->setUser($user);
            }

            try {
                $this->entityManager->persist($rating);
                $this->entityManager->flush();
            } catch (TableNotFoundException | NotNullConstraintViolationException) {
                $this->addFlash('error', 'Comments are temporarily unavailable. Please try again after the database migration is applied.');
                return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
            }

            if ($normalizedStatus === FoodDonationEvent::STATUS_CANCELLED) {
                $this->addFlash('success', 'Your comment has been submitted. This event is cancelled, so rating is unavailable.');
            } else {
                $this->addFlash('success', 'Your comment has been submitted. Rating will be available when event is Ongoing or Completed.');
            }

            return $this->redirectToRoute('app_client_food_donation_show', ['id' => $id]);
        }

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

    #[Route('/event/{eventId}/review/{reviewId}/edit', name: 'app_event_review_edit_ajax', methods: ['POST'])]
    public function editReviewAjax(Request $request, int $eventId, int $reviewId): JsonResponse
    {
        $event = $this->foodDonationEventRepository->find($eventId);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Event not found.'], Response::HTTP_NOT_FOUND);
        }

        $rating = $this->eventRatingRepository->find($reviewId);
        if (!$rating instanceof EventRating || (int) ($rating->getDonationEvent()?->getDonationEventId() ?? 0) !== $eventId) {
            return $this->json(['success' => false, 'message' => 'Review not found for this event.'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->resolveCurrentUser($request);
        if (!$user instanceof User || !$rating->getUser() || $rating->getUser()->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'message' => 'You can only edit your own review.'], Response::HTTP_FORBIDDEN);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('edit-review' . $rating->getRatingId(), $token)) {
            return $this->json(['success' => false, 'message' => 'Invalid security token.'], Response::HTTP_FORBIDDEN);
        }

        $eventRatingValue = $request->request->get('eventRating');
        $foodRatingValue = $request->request->get('foodRating');
        $comment = trim((string) $request->request->get('comment', '')) ?: null;

        $eventRatingValue = $eventRatingValue !== null && $eventRatingValue !== '' ? (int) $eventRatingValue : null;
        $foodRatingValue = $foodRatingValue !== null && $foodRatingValue !== '' ? (int) $foodRatingValue : null;

        if ($eventRatingValue !== null && ($eventRatingValue < 1 || $eventRatingValue > 5)) {
            return $this->json(['success' => false, 'message' => 'Event rating must be between 1 and 5.'], Response::HTTP_BAD_REQUEST);
        }

        if ($foodRatingValue !== null && ($foodRatingValue < 1 || $foodRatingValue > 5)) {
            return $this->json(['success' => false, 'message' => 'Food rating must be between 1 and 5.'], Response::HTTP_BAD_REQUEST);
        }

        if ($comment === null && $eventRatingValue === null && $foodRatingValue === null) {
            return $this->json(['success' => false, 'message' => 'Please provide at least a comment or rating.'], Response::HTTP_BAD_REQUEST);
        }

        $rating
            ->setEventRating($eventRatingValue)
            ->setFoodRating($foodRatingValue)
            ->setComment($comment);

        $this->entityManager->flush();
        $this->addFlash('success', 'Review updated successfully');

        return $this->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'review' => [
                'id' => $rating->getRatingId(),
                'eventRating' => $rating->getEventRating(),
                'foodRating' => $rating->getFoodRating(),
                'comment' => $rating->getComment(),
            ],
        ]);
    }

    #[Route('/event/{eventId}/review/{reviewId}/delete', name: 'app_event_review_delete_ajax', methods: ['POST'])]
    public function deleteReviewAjax(Request $request, int $eventId, int $reviewId): JsonResponse
    {
        $event = $this->foodDonationEventRepository->find($eventId);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Event not found.'], Response::HTTP_NOT_FOUND);
        }

        $rating = $this->eventRatingRepository->find($reviewId);
        if (!$rating instanceof EventRating || (int) ($rating->getDonationEvent()?->getDonationEventId() ?? 0) !== $eventId) {
            return $this->json(['success' => false, 'message' => 'Review not found for this event.'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->resolveCurrentUser($request);
        if (!$user instanceof User || !$rating->getUser() || $rating->getUser()->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'message' => 'You can only delete your own review.'], Response::HTTP_FORBIDDEN);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete-review' . $rating->getRatingId(), $token)) {
            return $this->json(['success' => false, 'message' => 'Invalid security token.'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($rating);
        $this->entityManager->flush();
        $this->addFlash('success', 'Review deleted successfully');

        return $this->json(['success' => true, 'message' => 'Review deleted successfully']);
    }

    #[Route('/admin/event/{eventId}/comments', name: 'app_admin_event_comments_ajax', methods: ['GET'])]
    public function adminEventCommentsAjax(Request $request, int $eventId): JsonResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->json(['success' => false, 'message' => 'Admin access required.'], Response::HTTP_FORBIDDEN);
        }

        $event = $this->foodDonationEventRepository->find($eventId);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Event not found.'], Response::HTTP_NOT_FOUND);
        }

        $ratings = $this->eventRatingRepository->findByDonationEvent($event);
        $comments = array_map(function (EventRating $rating): array {
            $user = $rating->getUser();
            $displayName = 'Guest Reviewer';
            if ($user instanceof User) {
                $displayName = trim((string) (($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')));
                if ($displayName === '') {
                    $displayName = (string) ($user->getEmail() ?? 'User #' . $user->getId());
                }
            }

            return [
                'id' => $rating->getRatingId(),
                'userName' => $displayName,
                'date' => $rating->getCreatedAt()?->format('M j, Y H:i') ?? 'Recently',
                'eventRating' => $rating->getEventRating(),
                'foodRating' => $rating->getFoodRating(),
                'comment' => $rating->getComment() ?? '',
                'deleteToken' => $this->csrfTokenManager->getToken('admin-delete-review' . $rating->getRatingId())->getValue(),
            ];
        }, $ratings);

        return $this->json([
            'success' => true,
            'event' => [
                'id' => $event->getDonationEventId(),
                'charityName' => $event->getCharityName(),
                'status' => $event->getStatus(),
            ],
            'comments' => $comments,
        ]);
    }

    #[Route('/admin/event/{eventId}/review/{reviewId}/delete', name: 'app_admin_event_review_delete_ajax', methods: ['POST'])]
    public function adminDeleteReviewAjax(Request $request, int $eventId, int $reviewId): JsonResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->json(['success' => false, 'message' => 'Admin access required.'], Response::HTTP_FORBIDDEN);
        }

        $event = $this->foodDonationEventRepository->find($eventId);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Event not found.'], Response::HTTP_NOT_FOUND);
        }

        $rating = $this->eventRatingRepository->find($reviewId);
        if (!$rating instanceof EventRating || (int) ($rating->getDonationEvent()?->getDonationEventId() ?? 0) !== $eventId) {
            return $this->json(['success' => false, 'message' => 'Review not found for this event.'], Response::HTTP_NOT_FOUND);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('admin-delete-review' . $rating->getRatingId(), $token)) {
            return $this->json(['success' => false, 'message' => 'Invalid security token.'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($rating);
        $this->entityManager->flush();
        $this->addFlash('success', 'Review deleted successfully');

        return $this->json(['success' => true, 'message' => 'Review deleted successfully']);
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

    private function canRateForStatus(string $status): bool
    {
        return in_array($status, [FoodDonationEvent::STATUS_ONGOING, FoodDonationEvent::STATUS_COMPLETED], true);
    }

    private function normalizeEventStatus(?string $status): string
    {
        return match (strtolower(trim((string) ($status ?? FoodDonationEvent::STATUS_SCHEDULED)))) {
            'scheduled', 'pending' => FoodDonationEvent::STATUS_SCHEDULED,
            'in progress', 'in_progress' => FoodDonationEvent::STATUS_IN_PROGRESS,
            'ongoing' => FoodDonationEvent::STATUS_ONGOING,
            'completed' => FoodDonationEvent::STATUS_COMPLETED,
            'cancelled' => FoodDonationEvent::STATUS_CANCELLED,
            default => FoodDonationEvent::STATUS_SCHEDULED,
        };
    }
}
