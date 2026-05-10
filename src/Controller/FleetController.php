<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\DeliveryMan;
use App\Repository\DeliveryRepository;
use App\Repository\DeliveryManRepository;
use App\Service\FleetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fleet', name: 'api_fleet_')]
class FleetController extends AbstractController
{
    public function __construct(
        private FleetService $fleetService,
        private DeliveryManRepository $deliveryManRepository,
        private DeliveryRepository $deliveryRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Update delivery man GPS location
     * POST /api/fleet/update-location
     */
    #[Route('/update-location', methods: ['POST'], name: 'update_location')]
    public function updateLocation(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['deliveryManId'], $data['latitude'], $data['longitude'])) {
            return $this->json(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        $deliveryMan = $this->deliveryManRepository->find($data['deliveryManId']);
        if (!$deliveryMan) {
            return $this->json(['success' => false, 'message' => 'Delivery man not found'], 404);
        }

        try {
            $this->fleetService->updateLocation(
                $deliveryMan,
                (float) $data['latitude'],
                (float) $data['longitude']
            );
            
            return $this->json([
                'success' => true,
                'message' => 'Location updated',
                'deliveryManId' => $deliveryMan->getDelivery_man_id(),
                'latitude' => $deliveryMan->getLatitude(),
                'longitude' => $deliveryMan->getLongitude(),
                'timestamp' => $deliveryMan->getLastLocationUpdate()?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all delivery men locations
     * GET /api/fleet/locations
     */
    #[Route('/locations', methods: ['GET'], name: 'locations')]
    public function getLocations(): JsonResponse
    {
        try {
            $locations = $this->fleetService->getAllDeliveryMenLocations();
            return $this->json([
                'success' => true,
                'data' => $locations,
                'count' => count($locations),
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign delivery to nearest delivery man
     * POST /api/fleet/assign-nearest
     */
    #[Route('/assign-nearest', methods: ['POST'], name: 'assign_nearest')]
    public function assignNearest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['destinationLatitude'], $data['destinationLongitude'])) {
            return $this->json(['success' => false, 'message' => 'Missing destination GPS'], 400);
        }

        try {
            $destLat = (float) $data['destinationLatitude'];
            $destLng = (float) $data['destinationLongitude'];
            
            $nearest = $this->fleetService->findNearestAvailableDeliveryMan($destLat, $destLng);
            if (!$nearest) {
                return $this->json(['success' => false, 'message' => 'No available delivery man'], 404);
            }

            // Create or update delivery
            $delivery = new Delivery();
            $delivery->setDestination_latitude($destLat);
            $delivery->setDestination_longitude($destLng);
            $delivery->setDeliveryMan($nearest);
            $delivery->setStatus('assigned');
            $delivery->setDelivery_address('Destination: ' . $destLat . ', ' . $destLng);
            
            if ($nearest->getLatitude() && $nearest->getLongitude()) {
                $eta = $this->fleetService->calculateETA(
                    $nearest->getLatitude(),
                    $nearest->getLongitude(),
                    $destLat,
                    $destLng
                );
                $delivery->setEstimated_time($eta);
            }

            $this->em->persist($delivery);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Delivery assigned',
                'deliveryId' => $delivery->getDelivery_id(),
                'assignedTo' => [
                    'id' => $nearest->getDelivery_man_id(),
                    'name' => $nearest->getName(),
                    'latitude' => $nearest->getLatitude(),
                    'longitude' => $nearest->getLongitude(),
                ],
                'estimatedTime' => $delivery->getEstimated_time(),
                'eta' => $delivery->getEstimated_time() . ' min',
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate ETA for a delivery
     * GET /api/fleet/eta/{deliveryId}
     */
    #[Route('/eta/{deliveryId}', methods: ['GET'], name: 'eta')]
    public function calculateETA(int $deliveryId): JsonResponse
    {
        try {
            $delivery = $this->deliveryRepository->find($deliveryId);
            if (!$delivery) {
                return $this->json(['success' => false, 'message' => 'Delivery not found'], 404);
            }

            $user = $delivery->getDeliveryMan();
            if (!$user || !$user->getLatitude() || !$user->getLongitude()) {
                return $this->json(['success' => false, 'message' => 'Delivery man or location not found'], 400);
            }

            if (!$delivery->getDestination_latitude() || !$delivery->getDestination_longitude()) {
                return $this->json(['success' => false, 'message' => 'Destination not set'], 400);
            }

            $eta = $this->fleetService->calculateETA(
                $user->getLatitude(),
                $user->getLongitude(),
                $delivery->getDestination_latitude(),
                $delivery->getDestination_longitude()
            );

            return $this->json([
                'success' => true,
                'deliveryId' => $deliveryId,
                'eta' => $eta,
                'unit' => 'minutes',
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get fleet optimization suggestions
     * GET /api/fleet/optimization
     */
    #[Route('/optimization', methods: ['GET'], name: 'optimization')]
    public function optimizeFleet(): JsonResponse
    {
        try {
            $suggestions = $this->fleetService->getOptimizationSuggestions();
            return $this->json([
                'success' => true,
                'data' => $suggestions,
                'count' => count($suggestions),
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get distance between two delivery men
     * GET /api/fleet/distance/{id1}/{id2}
     */
    #[Route('/distance/{id1}/{id2}', methods: ['GET'], name: 'distance')]
    public function getDistance(int $id1, int $id2): JsonResponse
    {
        try {
            $dm1 = $this->deliveryManRepository->find($id1);
            $dm2 = $this->deliveryManRepository->find($id2);
            
            if (!$dm1 || !$dm2) {
                return $this->json(['success' => false, 'message' => 'One or both delivery men not found'], 404);
            }

            $distance = $this->fleetService->getDistanceBetweenDeliveryMen($dm1, $dm2);
            if ($distance === null) {
                return $this->json(['success' => false, 'message' => 'GPS positions not available'], 400);
            }

            return $this->json([
                'success' => true,
                'from' => $dm1->getName(),
                'to' => $dm2->getName(),
                'distance' => round($distance, 2),
                'unit' => 'km',
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
