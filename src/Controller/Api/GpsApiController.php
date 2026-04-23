<?php

namespace App\Controller\Api;

use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use App\Repository\DeliveryManRepository;
use App\Repository\FleetCarRepository;
use App\Service\GPSService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/gps')]
class GpsApiController extends AbstractController
{
    public function __construct(
        private GPSService $gpsService,
        private FleetCarRepository $fleetCarRepository,
        private DeliveryManRepository $deliveryManRepository,
    ) {
    }

    #[Route('/update', name: 'api_gps_update', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload) || !isset($payload['carId'], $payload['latitude'], $payload['longitude'])) {
            return $this->json([
                'status' => 'error',
                'code' => 'VALIDATION_FAILED',
                'message' => 'carId, latitude and longitude are required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $car = $this->fleetCarRepository->find((int) $payload['carId']);
        if (!$car instanceof FleetCar) {
            return $this->json([
                'status' => 'error',
                'code' => 'CAR_NOT_FOUND',
                'message' => 'Car not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $deliveryMan = null;
        if (isset($payload['deliveryManId'])) {
            $candidate = $this->deliveryManRepository->find((int) $payload['deliveryManId']);
            if ($candidate instanceof DeliveryMan) {
                $deliveryMan = $candidate;
            }
        }

        try {
            $log = $this->gpsService->updateLocation(
                $car,
                (float) $payload['latitude'],
                (float) $payload['longitude'],
                $deliveryMan,
                isset($payload['accuracy']) ? (int) $payload['accuracy'] : null,
                isset($payload['speed']) ? (float) $payload['speed'] : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'status' => 'error',
                'code' => 'INVALID_COORDINATES',
                'message' => 'Latitude/longitude are invalid.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'status' => 'ok',
            'data' => [
                'id' => $log->getId(),
                'timestamp' => $log->getTimestamp()?->format(DATE_ATOM),
            ],
        ]);
    }

    #[Route('/car/{carId}/history', name: 'api_gps_history', methods: ['GET'])]
    public function history(int $carId, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $car = $this->fleetCarRepository->find($carId);
        if (!$car instanceof FleetCar) {
            return $this->json([
                'status' => 'error',
                'code' => 'CAR_NOT_FOUND',
                'message' => 'Car not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $limit = max(1, (int) $request->query->get('limit', 100));
        $offset = max(0, (int) $request->query->get('offset', 0));

        $rows = array_map(static fn ($log): array => [
            'id' => $log->getId(),
            'latitude' => $log->getLatitude(),
            'longitude' => $log->getLongitude(),
            'speed' => $log->getSpeed(),
            'timestamp' => $log->getTimestamp()?->format(DATE_ATOM),
        ], $this->gpsService->getLocationHistory($car, $limit, $offset));

        return $this->json(['status' => 'ok', 'data' => $rows]);
    }
}
