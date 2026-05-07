<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notifications')]
class NotificationApiController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationService $notificationService,
    ) {
    }

    #[Route('', name: 'api_notifications_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $rows = array_map(static fn (Notification $n): array => [
            'id' => $n->getId(),
            'type' => $n->getType(),
            'title' => $n->getTitle(),
            'message' => $n->getMessage(),
            'isRead' => $n->isRead(),
            'createdAt' => $n->getCreatedAt()?->format(DATE_ATOM),
        ], $this->notificationService->getNotifications($user));

        return $this->json(['status' => 'ok', 'data' => $rows]);
    }

    #[Route('/{id}/read', name: 'api_notifications_read', methods: ['PATCH'])]
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);
        if (!$notification instanceof Notification || $notification->getRecipient()?->getId() !== $user->getId()) {
            return $this->json([
                'status' => 'error',
                'code' => 'NOT_FOUND',
                'message' => 'Notification not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->notificationService->markAsRead($notification);

        return $this->json(['status' => 'ok']);
    }

    #[Route('/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'status' => 'ok',
            'count' => $this->notificationRepository->countUnread($user),
        ]);
    }

    #[Route('/{id}', name: 'api_notifications_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);
        if (!$notification instanceof Notification || $notification->getRecipient()?->getId() !== $user->getId()) {
            return $this->json([
                'status' => 'error',
                'code' => 'NOT_FOUND',
                'message' => 'Notification not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->notificationService->deleteNotification($notification);

        return $this->json(['status' => 'ok']);
    }
}
