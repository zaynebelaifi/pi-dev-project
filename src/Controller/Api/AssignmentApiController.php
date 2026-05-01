<?php

namespace App\Controller\Api;

use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use App\Repository\AssignmentHistoryRepository;
use App\Repository\DeliveryManRepository;
use App\Repository\FleetCarRepository;
use App\Service\AssignmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/assignments')]
class AssignmentApiController extends AbstractController
{
    public function __construct(
        private AssignmentService $assignmentService,
        private AssignmentHistoryRepository $assignmentHistoryRepository,
        private FleetCarRepository $fleetCarRepository,
        private DeliveryManRepository $deliveryManRepository,
    ) {
    }

    #[Route('', name: 'api_assignments_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $rows = array_map(static fn ($a): array => [
            'id' => $a->getId(),
            'carId' => $a->getCar()?->getCarId(),
            'deliveryManId' => $a->getDeliveryMan()?->getDeliveryManId(),
            'status' => $a->getStatus(),
            'assignedAt' => $a->getAssignedAt()?->format(DATE_ATOM),
            'unassignedAt' => $a->getUnassignedAt()?->format(DATE_ATOM),
        ], $this->assignmentHistoryRepository->findLatest(100));

        return $this->json(['status' => 'ok', 'data' => $rows]);
    }

    #[Route('', name: 'api_assignments_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload) || empty($payload['carId']) || empty($payload['deliveryManId'])) {
            return $this->json([
                'status' => 'error',
                'code' => 'VALIDATION_FAILED',
                'message' => 'carId and deliveryManId are required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $car = $this->fleetCarRepository->find((int) $payload['carId']);
        $deliveryMan = $this->deliveryManRepository->find((int) $payload['deliveryManId']);

        if (!$car instanceof FleetCar || !$deliveryMan instanceof DeliveryMan) {
            return $this->json([
                'status' => 'error',
                'code' => 'NOT_FOUND',
                'message' => 'Car or delivery man not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $history = $this->assignmentService->assignCar(
                $car,
                $deliveryMan,
                $this->getUser(),
                (string) ($payload['reason'] ?? 'manual')
            );
        } catch (\RuntimeException $e) {
            return $this->json([
                'status' => 'error',
                'code' => $e->getMessage(),
                'message' => 'Assignment validation failed.',
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'status' => 'ok',
            'data' => [
                'assignmentId' => $history->getId(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/auto-assign', name: 'api_assignments_auto', methods: ['POST'])]
    public function autoAssign(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $assignments = $this->assignmentService->autoAssign($this->getUser());

        return $this->json([
            'status' => 'ok',
            'count' => count($assignments),
        ]);
    }

    #[Route('/cars/{carId}/unassign', name: 'api_assignments_unassign', methods: ['POST'])]
    public function unassign(int $carId): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $car = $this->fleetCarRepository->find($carId);
        if (!$car instanceof FleetCar) {
            return $this->json([
                'status' => 'error',
                'code' => 'NOT_FOUND',
                'message' => 'Car not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->assignmentService->unassignCar($car, $this->getUser());

        return $this->json([
            'status' => 'ok',
            'message' => 'Car unassigned successfully.',
        ]);
    }

    #[Route('/{id}/complete', name: 'api_assignments_complete', methods: ['POST'])]
    public function complete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $history = $this->assignmentHistoryRepository->find($id);
        if ($history === null) {
            return $this->json([
                'status' => 'error',
                'code' => 'NOT_FOUND',
                'message' => 'Assignment not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($history->getStatus() !== 'ACTIVE') {
            return $this->json([
                'status' => 'error',
                'code' => 'NOT_ACTIVE',
                'message' => 'Only active assignments can be completed.',
            ], Response::HTTP_CONFLICT);
        }

        $this->assignmentService->complete($history, $this->getUser());

        return $this->json(['status' => 'ok']);
    }
}
