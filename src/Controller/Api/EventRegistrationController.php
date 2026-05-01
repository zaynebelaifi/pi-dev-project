<?php

namespace App\Controller\Api;

use App\Entity\EventRegistration;
use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

final class EventRegistrationController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UserRepository $userRepository,
        private readonly FoodDonationEventRepository $eventRepository,
        private readonly EventRegistrationRepository $registrationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/events/{id}/register', name: 'api_event_register', methods: ['POST'])]
    public function registerEvent(int $id): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $sessionUserId = $session->get('user_id');
        $role = (string) ($session->get('user_role') ?? '');

        if (!is_numeric($sessionUserId)) {
            return $this->json([
                'success' => false,
                'message' => 'Please log in to register for events.',
            ], 401);
        }

        if ($role !== 'ROLE_CLIENT') {
            return $this->json([
                'success' => false,
                'message' => 'Only customers can register for events.',
            ], 403);
        }

        $user = $this->userRepository->find((int) $sessionUserId);
        $event = $this->eventRepository->find($id);

        if (!$user instanceof User || !$event instanceof FoodDonationEvent) {
            return $this->json([
                'success' => false,
                'message' => 'Event or user not found.',
            ], 404);
        }

        $liveStatus = FoodDonationEvent::calculateAutoStatus($event->getEventDate() ?? new \DateTimeImmutable('now'));
        if ($liveStatus !== FoodDonationEvent::STATUS_SCHEDULED) {
            return $this->json([
                'success' => false,
                'message' => 'Registration is only available for scheduled events.',
            ], 400);
        }

        $alreadyRegistered = $this->registrationRepository->isUserRegisteredForEvent((int) $user->getId(), (int) $event->getDonationEventId());
        if ($alreadyRegistered) {
            return $this->json([
                'success' => true,
                'already_registered' => true,
                'message' => 'You are already registered for this event.',
            ]);
        }

        $registration = (new EventRegistration())
            ->setEvent($event)
            ->setUser($user)
            ->setCreatedAt(new \DateTimeImmutable('now'));

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'You are now registered for this event.',
        ]);
    }
}
