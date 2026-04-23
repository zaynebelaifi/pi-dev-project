<?php

namespace App\Controller\Api;

use App\Entity\FleetCar;
use App\Repository\FleetCarRepository;
use App\Service\CarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cars')]
class CarApiController extends AbstractController
{
    public function __construct(
        private CarService $carService,
        private FleetCarRepository $fleetCarRepository,
    ) {
    }

    #[Route('', name: 'api_cars_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $cars = array_map(static fn (FleetCar $car): array => [
            'id' => $car->getCarId(),
            'licensePlate' => $car->getLicensePlate(),
            'brand' => $car->getBrand(),
            'model' => $car->getModel(),
            'status' => $car->getStatus(),
            'latitude' => $car->getLatitude(),
            'longitude' => $car->getLongitude(),
            'isActive' => $car->isActive(),
        ], $this->carService->getAllCars());

        return $this->json(['status' => 'ok', 'data' => $cars]);
    }

    #[Route('', name: 'api_cars_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json([
                'status' => 'error',
                'code' => 'INVALID_JSON',
                'message' => 'Invalid JSON payload.',
            ], Response::HTTP_BAD_REQUEST);
        }

        foreach (['make', 'model', 'licensePlate', 'vehicleType'] as $required) {
            if (empty($payload[$required])) {
                return $this->json([
                    'status' => 'error',
                    'code' => 'VALIDATION_FAILED',
                    'message' => sprintf('Field "%s" is required.', $required),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $car = (new FleetCar())
            ->setMake((string) $payload['make'])
            ->setModel((string) $payload['model'])
            ->setLicensePlate((string) $payload['licensePlate'])
            ->setVehicleType((string) $payload['vehicleType'])
            ->setStatus((string) ($payload['status'] ?? 'AVAILABLE'))
            ->setIsActive((bool) ($payload['isActive'] ?? true));

        if (isset($payload['fuelType'])) {
            $car->setFuelType((string) $payload['fuelType']);
        }

        if (isset($payload['year']) && is_numeric($payload['year'])) {
            $car->setYear((int) $payload['year']);
        }

        $this->carService->createCar($car);

        return $this->json([
            'status' => 'ok',
            'data' => [
                'id' => $car->getCarId(),
                'licensePlate' => $car->getLicensePlate(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_cars_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $car = $this->fleetCarRepository->find($id);
        if (!$car instanceof FleetCar) {
            return $this->json([
                'status' => 'error',
                'code' => 'CAR_NOT_FOUND',
                'message' => 'Car not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'status' => 'ok',
            'data' => [
                'id' => $car->getCarId(),
                'brand' => $car->getBrand(),
                'model' => $car->getModel(),
                'licensePlate' => $car->getLicensePlate(),
                'status' => $car->getStatus(),
                'latitude' => $car->getLatitude(),
                'longitude' => $car->getLongitude(),
                'lastUpdate' => $car->getLastUpdate()?->format(DATE_ATOM),
            ],
        ]);
    }
}
