<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryRepository;
use App\Repository\FleetCarRepository;
use App\Service\Fleet\FleetService;
use App\Service\Fleet\RouteService;
use App\Service\Fleet\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * FleetDashboardController — admin fleet management and live tracking API.
 *
 * Page routes:
 *   GET  /admin/fleet                → Premium dashboard
 *
 * API routes (all return {status, data, message}):
 *   GET  /admin/fleet/api/status         → Full fleet snapshot
 *   GET  /admin/fleet/api/locations      → Live driver coordinates
 *   POST /admin/fleet/api/location       → Driver self-reports location
 *   GET  /admin/fleet/api/weather        → Current weather data
 *   GET  /admin/fleet/api/best-driver    → Smart assignment suggestion
 *   POST /admin/fleet/api/assign         → Assign driver+car to delivery
 *   GET  /admin/fleet/api/route          → Route data between two points
 */
#[Route('/admin/fleet', name: 'admin_fleet_')]
class FleetDashboardController extends AbstractController
{
    public function __construct(
        private readonly FleetService          $fleet,
        private readonly RouteService          $route,
        private readonly WeatherService        $weather,
        private readonly DeliveryRepository    $deliveryRepo,
        private readonly DeliveryManRepository $driverRepo,
        private readonly FleetCarRepository    $carRepo,
        private readonly string                $googleMapsApiKey,
        private readonly float                 $restaurantLat,
        private readonly float                 $restaurantLng,
    ) {}

    // ── Dashboard Page ───────────────────────────────────────────────────────

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $this->requireAdmin($request);

        $status           = $this->fleet->getFleetStatus();
        $weatherData      = $this->weather->getCurrentWeather($this->restaurantLat, $this->restaurantLng);
        $pendingDeliveries = $this->deliveryRepo->findBy(['status' => 'PENDING']);
        $availableCars    = $this->carRepo->findBy(['status' => 'AVAILABLE']);
        $ranked           = $this->fleet->findNearbyDrivers();

        return $this->render('admin/fleet/dashboard.html.twig', [
            'drivers'            => $status['drivers'],
            'cars'               => $status['cars'],
            'activeDeliveries'   => $status['active'],
            'pendingDeliveries'  => $pendingDeliveries,
            'availableCars'      => $availableCars,
            'weather'            => $weatherData,
            'rankedDrivers'      => $ranked,
            'restaurantLat'      => $this->restaurantLat,
            'restaurantLng'      => $this->restaurantLng,
            'googleMapsApiKey'   => $this->googleMapsApiKey,
        ]);
    }

    // ── API: Fleet Status ────────────────────────────────────────────────────

    #[Route('/api/status', name: 'api_status', methods: ['GET'])]
    public function apiStatus(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        return $this->ok($this->fleet->getFleetStatus(), 'Fleet status retrieved.');
    }

    // ── API: Live Driver Locations ───────────────────────────────────────────

    #[Route('/api/locations', name: 'api_locations', methods: ['GET'])]
    public function apiLocations(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $locations = $this->fleet->getActiveDriverLocations();
        return $this->ok($locations, sprintf('%d driver(s) located.', count($locations)));
    }

    // ── API: Driver Reports Own Location ────────────────────────────────────

    #[Route('/api/location', name: 'api_location_update', methods: ['POST'])]
    public function apiUpdateLocation(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $driverId = (int) ($data['driver_id'] ?? 0);
        $lat      = (float) ($data['lat'] ?? 0);
        $lng      = (float) ($data['lng'] ?? 0);

        if (!$driverId || !$lat || !$lng) {
            return $this->err('Missing driver_id, lat, or lng.', 400);
        }

        $this->fleet->updateDriverLocation($driverId, $lat, $lng);
        return $this->ok(['driver_id' => $driverId, 'lat' => $lat, 'lng' => $lng], 'Location updated.');
    }

    // ── API: Current Weather ─────────────────────────────────────────────────

    #[Route('/api/weather', name: 'api_weather', methods: ['GET'])]
    public function apiWeather(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $this->weather->getCurrentWeather($this->restaurantLat, $this->restaurantLng);
        return $this->ok($data, 'Weather data retrieved.');
    }

    // ── API: Smart Driver Assignment Suggestion ──────────────────────────────

    #[Route('/api/best-driver', name: 'api_best_driver', methods: ['GET'])]
    public function apiBestDriver(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $ranked = $this->fleet->findNearbyDrivers();

        if (empty($ranked)) {
            return $this->err('No available drivers found.', 404);
        }

        $weather    = $this->weather->getCurrentWeather($this->restaurantLat, $this->restaurantLng);
        $multiplier = $this->weather->getETAMultiplier($weather);
        $best       = $ranked[0];
        $driver     = $best['driver'];

        return $this->ok([
            'driver' => [
                'id'               => $driver->getId(),
                'name'             => $driver->getName(),
                'phone'            => $driver->getPhone(),
                'vehicleType'      => $driver->getVehicleType(),
                'rating'           => $driver->getRating(),
                'activeDeliveries' => $driver->getActiveDeliveries(),
            ],
            'distance'           => $best['distance'],
            'score'              => $best['score'],
            'etaMin'             => (int) ceil($best['etaMin'] * $multiplier),
            'weatherMultiplier'  => $multiplier,
            'allDrivers'         => array_map(fn($r) => [
                'id'       => $r['driver']->getId(),
                'name'     => $r['driver']->getName(),
                'distance' => $r['distance'],
                'score'    => $r['score'],
                'etaMin'   => (int) ceil($r['etaMin'] * $multiplier),
            ], $ranked),
        ], 'Best driver found.');
    }

    // ── API: Assign Driver + Car to Delivery ─────────────────────────────────

    #[Route('/api/assign', name: 'api_assign', methods: ['POST'])]
    public function apiAssign(Request $request): JsonResponse
    {
        $this->requireAdmin($request);

        $data       = json_decode($request->getContent(), true) ?? [];
        $deliveryId = (int) ($data['delivery_id'] ?? 0);
        $driverId   = (int) ($data['driver_id'] ?? 0);
        $carId      = (int) ($data['car_id'] ?? 0);

        if (!$deliveryId || !$driverId || !$carId) {
            return $this->err('delivery_id, driver_id, and car_id are required.', 400);
        }

        $result = $this->fleet->assignDriverToDelivery($deliveryId, $driverId, $carId);

        return $result['success']
            ? $this->ok($result, $result['message'])
            : $this->err($result['message'], 422);
    }

    // ── API: Route Between Two Points ────────────────────────────────────────

    #[Route('/api/route', name: 'api_route', methods: ['GET'])]
    public function apiRoute(Request $request): JsonResponse
    {
        $this->requireAdmin($request);

        $startLat = (float) $request->query->get('startLat', $this->restaurantLat);
        $startLng = (float) $request->query->get('startLng', $this->restaurantLng);
        $endLat   = (float) $request->query->get('endLat', 0);
        $endLng   = (float) $request->query->get('endLng', 0);

        if (!$endLat || !$endLng) {
            return $this->err('endLat and endLng are required.', 400);
        }

        $routeData  = $this->route->getRouteData($startLat, $startLng, $endLat, $endLng);
        $weather    = $this->weather->getCurrentWeather($this->restaurantLat, $this->restaurantLng);
        $multiplier = $this->weather->getETAMultiplier($weather);

        $routeData['adjustedEtaMin'] = (int) ceil(($routeData['durationMin'] ?? 0) * $multiplier);
        $routeData['weatherWarning'] = $weather['isBad']
            ? "⚠️ {$weather['condition']} detected — ETA extended by " . (int) (($multiplier - 1) * 100) . '%'
            : null;

        return $this->ok($routeData, 'Route calculated.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function requireAdmin(Request $request): void
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Admin access required.');
        }
    }

    private function ok(array $data, string $message = 'OK'): JsonResponse
    {
        return $this->json(['status' => 'success', 'data' => $data, 'message' => $message]);
    }

    private function err(string $message, int $code = 400): JsonResponse
    {
        return $this->json(['status' => 'error', 'data' => null, 'message' => $message], $code);
    }
}
