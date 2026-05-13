<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\DeliveryMan;
use App\Repository\IngredientRepository;
use App\Repository\WasterecordRepository;
use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryRepository;
use App\Service\AdminAnalyticsService;
use App\Service\ExpiredIngredientWasteService;
use App\Service\WhatsAppNotificationService;
use App\Utils\AiStockInsightService;
use App\Repository\UserRepository;
use App\Repository\FleetCarRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        Request $request,
        DeliveryRepository $deliveryRepository,
        DeliveryManRepository $deliveryManRepository,
        FleetCarRepository $fleetCarRepository,
        IngredientRepository $ingredientRepository,
        WasterecordRepository $wasterecordRepository,
        ExpiredIngredientWasteService $expiredWasteService,
    ): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $autoMoved = $expiredWasteService->moveExpiredStockToWaste();

        $today = new \DateTimeImmutable('today');

        return $this->render('admin/dashboard.html.twig', [
            'deliveryCount' => $deliveryRepository->count([]),
            'pendingCount' => $deliveryRepository->count(['status' => 'PENDING']),
            'assignedCount' => $deliveryRepository->count(['status' => 'ASSIGNED']),
            'deliveryManCount' => $deliveryManRepository->count([]),
            'ingredientCount' => $ingredientRepository->count([]),
            'lowStockCount' => $ingredientRepository->countLowStock(),
            'expiredCount' => $ingredientRepository->countExpired($today),
            'wasteCount' => $wasterecordRepository->count([]),
            'inventoryValue' => $ingredientRepository->sumInventoryValue(),
            'totalWasteQuantity' => $wasterecordRepository->totalWastedQuantity(),
            'autoWasteMoved' => $autoMoved,
            'vehicleCount' => $fleetCarRepository->count([]),
        ]);
    }

    #[Route('/diagnostics', name: 'app_admin_diagnostics', methods: ['GET'])]
    public function diagnostics(Request $request, Connection $connection): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->json(['error' => 'unauthorized'], 403);
        }

        $dsn = (string) ($_ENV['MESSENGER_TRANSPORT_DSN'] ?? '');
        $whatsappUrl = (string) ($_ENV['WHATSAPP_API_URL'] ?? '');
        $whatsappToken = (string) ($_ENV['WHATSAPP_API_TOKEN'] ?? '');
        $orsKey = (string) ($_ENV['ORS_API_KEY'] ?? '');
        $restLat = (string) ($_ENV['RESTAURANT_LAT'] ?? '');
        $restLon = (string) ($_ENV['RESTAURANT_LON'] ?? '');

        $sm = $connection->createSchemaManager();
        $tables = array_map(fn($t) => $t->getName(), $sm->listTables());

        $checks = [
            [
                'label' => 'Messenger DSN',
                'ok' => $dsn !== '',
                'detail' => $dsn ? 'Configured' : 'Missing MESSENGER_TRANSPORT_DSN',
            ],
            [
                'label' => 'WhatsApp API',
                'ok' => $whatsappUrl !== '' && $whatsappToken !== '',
                'detail' => ($whatsappUrl && $whatsappToken) ? 'Configured' : 'Missing WHATSAPP_API_URL or WHATSAPP_API_TOKEN',
            ],
            [
                'label' => 'Mapping API',
                'ok' => $orsKey !== '',
                'detail' => $orsKey ? 'Configured' : 'Missing ORS_API_KEY',
            ],
            [
                'label' => 'Restaurant Coordinates',
                'ok' => $restLat !== '' && $restLon !== '',
                'detail' => ($restLat && $restLon) ? 'Configured' : 'Missing RESTAURANT_LAT/RESTAURANT_LON',
            ],
            [
                'label' => 'Messenger Queue Table',
                'ok' => in_array('messenger_messages', $tables, true),
                'detail' => in_array('messenger_messages', $tables, true) ? 'Table exists' : 'Missing messenger_messages table',
            ],
        ];

        return $this->json([
            'ok' => !in_array(false, array_column($checks, 'ok'), true),
            'checks' => $checks,
        ]);
    }

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/support-queue', name: 'app_admin_support_queue', methods: ['GET'])]
    public function supportQueue(Request $request): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $feedbackBaseUrl = rtrim((string) ($_ENV['FEEDBACK_AI_BASE_URL'] ?? 'http://127.0.0.1:8001'), '/');

        return $this->render('admin/support_queue.html.twig', [
            'feedbackBaseUrl' => $feedbackBaseUrl,
        ]);
    }

    #[Route('/users/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        WhatsAppNotificationService $whatsAppNotificationService,
    ): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('danger', sprintf('Unable to ban user #%d. Please try again.', $id));

            return $this->redirectToRoute('app_admin_users');
        }

        $name = trim(sprintf('%s %s', (string) $user->getFirstName(), (string) $user->getLastName()));
        $identity = '' !== $name ? $name : (string) $user->getEmail();

        if (!$this->isCsrfTokenValid('ban'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', sprintf('Unable to ban user #%d %s. Please try again.', $user->getId(), $identity));

            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->isBanned()) {
            $this->addFlash('warning', sprintf('User #%d %s is already banned.', $user->getId(), $identity));

            return $this->redirectToRoute('app_admin_users');
        }

        try {
            $user->setBanned(true);
            $entityManager->flush();
            $this->addFlash('success', sprintf('User #%d %s has been banned successfully.', $user->getId(), $identity));

            if (!$whatsAppNotificationService->notifyUserBanned($user, $identity)) {
                $this->addFlash('warning', sprintf('User #%d %s was banned, but the WhatsApp notification could not be sent.', $user->getId(), $identity));
            }
        } catch (\Throwable) {
            $this->addFlash('danger', sprintf('Unable to ban user #%d %s. Please try again.', $user->getId(), $identity));
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id);
        if ($user) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/deliveries', name: 'app_admin_deliveries', methods: ['GET'])]
    public function deliveries(Request $request, DeliveryRepository $deliveryRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search = trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'created_at');
        $direction = $request->query->get('direction', 'DESC');

        return $this->render('admin/deliveries.html.twig', [
            'deliveries' => $deliveryRepository->searchAndSort($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/deliveries/{id}/assign-car', name: 'app_admin_assign_car', methods: ['POST'])]
    public function assignCar(int $id, Request $request, DeliveryRepository $deliveryRepository, FleetCarRepository $fleetCarRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $delivery = $deliveryRepository->find($id);
        if ($delivery) {
            $carId = $request->request->get('car_id');
            if ($carId) {
                $car = $fleetCarRepository->find($carId);
                if ($car) {
                    $delivery->setFleetCar($car);
                    $entityManager->flush();
                    $this->addFlash('success', 'Car assigned successfully!');
                }
            }
        }

        return $this->redirectToRoute('app_admin_deliveries');
    }

    #[Route('/deliveries/{id}/remove-car', name: 'app_admin_remove_car', methods: ['POST'])]
    public function removeCar(int $id, Request $request, DeliveryRepository $deliveryRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $delivery = $deliveryRepository->find($id);
        if ($delivery) {
            $delivery->setFleetCar(null);
            $entityManager->flush();
            $this->addFlash('success', 'Car unassigned successfully!');
        }

        return $this->redirectToRoute('app_admin_deliveries');
    }

    #[Route('/vehicles', name: 'app_admin_vehicles', methods: ['GET'])]
    public function vehicles(Request $request, FleetCarRepository $fleetCarRepository, DeliveryManRepository $deliveryManRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search = trim((string) $request->query->get('search', ''));
        
        $queryBuilder = $fleetCarRepository->createQueryBuilder('c');
        if ($search) {
            $queryBuilder
                ->where('c.make LIKE :search OR c.model LIKE :search OR c.license_plate LIKE :search OR c.vehicle_type LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        $vehicles = $queryBuilder->orderBy('c.car_id', 'DESC')->getQuery()->getResult();

        return $this->render('admin/vehicles.html.twig', [
            'vehicles' => $vehicles,
            'deliveryMen' => $deliveryManRepository->findAll(),
            'search' => $search,
        ]);
    }

    #[Route('/fleet-dashboard', name: 'admin_fleet_dashboard', methods: ['GET'])]
    public function fleetDashboard(Request $request, DeliveryRepository $deliveryRepository, DeliveryManRepository $deliveryManRepository): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $now = new \DateTimeImmutable();
        $onlineCutoff = $now->modify('-5 minutes');
        $deliveryMen = $deliveryManRepository->findBy([], ['name' => 'ASC']);
        $activeDeliveries = $this->findActiveAssignedDeliveries($deliveryRepository);
        $activeDeliveryByDriverId = $this->indexActiveDeliveriesByDriver($activeDeliveries);
        $onlineDeliveryMen = array_filter(
            $deliveryMen,
            fn (DeliveryMan $deliveryMan): bool => $this->isDeliveryManOnline($deliveryMan, $onlineCutoff)
        );

        $markers = [];
        foreach ($deliveryMen as $deliveryMan) {
            $latitude = $this->toFloatOrNull($deliveryMan->getCurrentLatitude());
            $longitude = $this->toFloatOrNull($deliveryMan->getCurrentLongitude());
            if (null === $latitude || null === $longitude) {
                continue;
            }

            $activeDelivery = $activeDeliveryByDriverId[$deliveryMan->getDelivery_man_id() ?? 0] ?? null;
            $markers[] = [
                'id' => $deliveryMan->getDelivery_man_id(),
                'name' => $deliveryMan->getName() ?: 'Unnamed driver',
                'lat' => $latitude,
                'lng' => $longitude,
                'online' => $this->isDeliveryManOnline($deliveryMan, $onlineCutoff),
                'activeDelivery' => $activeDelivery ? ('#'.$activeDelivery->getDelivery_id()) : 'None',
                'lastSeen' => $deliveryMan->getLastSeenAt()?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        }

        $alerts = $this->buildFleetAlerts($activeDeliveries);
        $pendingDeliveries = $deliveryRepository->createQueryBuilder('d')
            ->andWhere('d.deliveryMan IS NULL')
            ->andWhere('UPPER(COALESCE(d.status, :pending)) NOT IN (:closedStatuses)')
            ->setParameter('pending', 'PENDING')
            ->setParameter('closedStatuses', ['DELIVERED', 'CANCELLED'])
            ->orderBy('d.created_at', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        return $this->render('admin/fleet_dashboard.html.twig', [
            'onlineCount' => count($onlineDeliveryMen),
            'activeCount' => count($activeDeliveries),
            'alertCount' => count($alerts),
            'alerts' => $alerts,
            'pendingDeliveries' => $pendingDeliveries,
            'markers' => $markers,
            'hasMarkers' => count($markers) > 0,
        ]);
    }

    #[Route('/fleet-dashboard/deliveries/{id}/ai-assign', name: 'admin_fleet_dashboard_ai_assign', methods: ['POST'])]
    public function aiAssignDelivery(
        int $id,
        Request $request,
        DeliveryRepository $deliveryRepository,
        DeliveryManRepository $deliveryManRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $delivery = $deliveryRepository->find($id);
        if (!$delivery || !$this->isCsrfTokenValid('fleet_ai_assign'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', sprintf('Unable to assign delivery #%d. Please try again.', $id));

            return $this->redirectToRoute('admin_fleet_dashboard');
        }

        if ($delivery->getDeliveryMan()) {
            $this->addFlash('warning', sprintf('Delivery #%d is already assigned.', $delivery->getDelivery_id()));

            return $this->redirectToRoute('admin_fleet_dashboard');
        }

        $onlineCutoff = new \DateTimeImmutable('-5 minutes');
        $onlineDeliveryMen = array_values(array_filter(
            $deliveryManRepository->findAll(),
            fn (DeliveryMan $deliveryMan): bool => $this->isDeliveryManOnline($deliveryMan, $onlineCutoff)
        ));

        if ([] === $onlineDeliveryMen) {
            $this->addFlash('warning', 'No online delivery man available.');

            return $this->redirectToRoute('admin_fleet_dashboard');
        }

        $activeDeliveryByDriverId = $this->indexActiveDeliveriesByDriver($this->findActiveAssignedDeliveries($deliveryRepository));
        $bestDeliveryMan = $this->chooseBestDeliveryMan($delivery, $onlineDeliveryMen, $activeDeliveryByDriverId);
        if (!$bestDeliveryMan) {
            $this->addFlash('warning', 'No online delivery man available.');

            return $this->redirectToRoute('admin_fleet_dashboard');
        }

        $delivery->setDeliveryMan($bestDeliveryMan);
        if (!$delivery->getStatus() || in_array(strtoupper((string) $delivery->getStatus()), ['PENDING', 'CREATED'], true)) {
            $delivery->setStatus('ASSIGNED');
        }
        $delivery->setUpdated_at(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', sprintf('Delivery #%d assigned to %s.', $delivery->getDelivery_id(), $bestDeliveryMan->getName() ?: 'selected driver'));

        return $this->redirectToRoute('admin_fleet_dashboard');
    }

    #[Route('/vehicles/{id}/assign-driver', name: 'app_admin_assign_driver', methods: ['POST'])]
    public function assignDriver(int $id, Request $request, FleetCarRepository $fleetCarRepository, DeliveryManRepository $deliveryManRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $vehicle = $fleetCarRepository->find($id);
        if ($vehicle) {
            $driverId = $request->request->get('driver_id');
            if ($driverId) {
                $driver = $deliveryManRepository->find($driverId);
                if ($driver) {
                    $oldDriverId = $vehicle->getDelivery_man_id();
                    if ($oldDriverId && $oldDriverId !== $driverId) {
                        $oldDriver = $deliveryManRepository->find($oldDriverId);
                        if ($oldDriver && $oldDriver->getVehicle_number() === $vehicle->getLicense_plate()) {
                            $oldDriver->setVehicle_type(null);
                            $oldDriver->setVehicle_number(null);
                        }
                    }

                    $vehicle->setDelivery_man_id($driverId);
                    $driver->setVehicle_type($vehicle->getVehicle_type());
                    $driver->setVehicle_number($vehicle->getLicense_plate());

                    $entityManager->flush();
                    $this->addFlash('success', sprintf('Vehicle assigned to %s successfully!', $driver->getName()));
                } else {
                    $this->addFlash('error', 'Driver not found.');
                }
            } else {
                $this->addFlash('error', 'Please select a driver.');
            }
        }

        return $this->redirectToRoute('app_admin_vehicles');
    }

    #[Route('/vehicles/{id}/unassign-driver', name: 'app_admin_unassign_driver', methods: ['POST'])]
    public function unassignDriver(int $id, Request $request, FleetCarRepository $fleetCarRepository, DeliveryManRepository $deliveryManRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $vehicle = $fleetCarRepository->find($id);
        if ($vehicle) {
            $oldDriverId = $vehicle->getDelivery_man_id();
            if ($oldDriverId) {
                $oldDriver = $deliveryManRepository->find($oldDriverId);
                if ($oldDriver && $oldDriver->getVehicle_number() === $vehicle->getLicense_plate()) {
                    $oldDriver->setVehicle_type(null);
                    $oldDriver->setVehicle_number(null);
                }
            }

            $vehicle->setDelivery_man_id(null);
            $entityManager->flush();
            $this->addFlash('success', 'Vehicle unassigned successfully!');
        }

        return $this->redirectToRoute('app_admin_vehicles');
    }

    #[Route('/analytics', name: 'app_admin_analytics', methods: ['GET'])]
    public function analytics(
        Request $request,
        AdminAnalyticsService $adminAnalyticsService,
    ): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        // Release the session lock before running analytics queries.
        $session->save();

        $viewData = $adminAnalyticsService->buildAnalyticsViewData(
            (string) $request->query->get('waste_period', 'Month'),
            (string) $request->query->get('revenue_period', 'Month'),
            trim((string) $request->query->get('revenue_from', '')),
            trim((string) $request->query->get('revenue_to', '')),
            (string) $request->query->get('revenue_sort', 'revenue_desc')
        );

        return $this->render('admin/analytics.html.twig', $viewData);
    }

    #[Route('/analytics/stock-chat', name: 'app_admin_stock_chat', methods: ['POST'])]
    public function stockChat(
        Request $request,
        AdminAnalyticsService $adminAnalyticsService,
        AiStockInsightService $aiStockInsightService,
    ): JsonResponse
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Release session lock to avoid blocking parallel requests.
        $session->save();

        $payload = json_decode((string) $request->getContent(), true);
        $question = trim((string) ($payload['question'] ?? ''));

        if ('' === $question) {
            return new JsonResponse(['message' => 'Question is required.'], Response::HTTP_BAD_REQUEST);
        }

        $context = $adminAnalyticsService->buildChatContext();
        $result = $aiStockInsightService->answerQuestion($question, $context);

        return new JsonResponse([
            'answer' => (string) ($result['answer'] ?? ''),
            'usedFallback' => (bool) ($result['usedFallback'] ?? false),
            'fallbackReason' => (string) ($result['reason'] ?? ''),
        ]);
    }

    /**
     * @return Delivery[]
     */
    private function findActiveAssignedDeliveries(DeliveryRepository $deliveryRepository): array
    {
        return $deliveryRepository->createQueryBuilder('d')
            ->leftJoin('d.deliveryMan', 'dm')
            ->addSelect('dm')
            ->andWhere('d.deliveryMan IS NOT NULL')
            ->andWhere('UPPER(COALESCE(d.status, :pending)) NOT IN (:closedStatuses)')
            ->setParameter('pending', 'PENDING')
            ->setParameter('closedStatuses', ['DELIVERED', 'CANCELLED'])
            ->orderBy('d.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Delivery[] $activeDeliveries
     *
     * @return array<int, Delivery>
     */
    private function indexActiveDeliveriesByDriver(array $activeDeliveries): array
    {
        $indexed = [];
        foreach ($activeDeliveries as $delivery) {
            $deliveryManId = $delivery->getDeliveryMan()?->getDelivery_man_id();
            if ($deliveryManId && !isset($indexed[$deliveryManId])) {
                $indexed[$deliveryManId] = $delivery;
            }
        }

        return $indexed;
    }

    private function isDeliveryManOnline(DeliveryMan $deliveryMan, \DateTimeImmutable $onlineCutoff): bool
    {
        $lastSeenAt = $deliveryMan->getLastSeenAt();

        return $lastSeenAt instanceof \DateTimeInterface
            && \DateTimeImmutable::createFromInterface($lastSeenAt) >= $onlineCutoff;
    }

    /**
     * @param Delivery[] $activeDeliveries
     *
     * @return array<int, string>
     */
    private function buildFleetAlerts(array $activeDeliveries): array
    {
        $alerts = [];
        foreach ($activeDeliveries as $delivery) {
            $deliveryMan = $delivery->getDeliveryMan();
            if (!$deliveryMan) {
                continue;
            }

            $driverLatitude = $this->toFloatOrNull($deliveryMan->getCurrentLatitude());
            $driverLongitude = $this->toFloatOrNull($deliveryMan->getCurrentLongitude());
            $destinationLatitude = $this->toFloatOrNull($delivery->getCurrentLatitude());
            $destinationLongitude = $this->toFloatOrNull($delivery->getCurrentLongitude());
            if (
                null === $driverLatitude
                || null === $driverLongitude
                || null === $destinationLatitude
                || null === $destinationLongitude
            ) {
                continue;
            }

            $distanceKm = $this->haversineDistanceKm($driverLatitude, $driverLongitude, $destinationLatitude, $destinationLongitude);
            if ($distanceKm > 3.0) {
                $alerts[] = sprintf(
                    'Delivery man %s is %.1f km away from delivery #%d destination.',
                    $deliveryMan->getName() ?: 'Unknown',
                    $distanceKm,
                    $delivery->getDelivery_id()
                );
            }
        }

        return $alerts;
    }

    /**
     * @param DeliveryMan[] $onlineDeliveryMen
     * @param array<int, Delivery> $activeDeliveryByDriverId
     */
    private function chooseBestDeliveryMan(Delivery $delivery, array $onlineDeliveryMen, array $activeDeliveryByDriverId): ?DeliveryMan
    {
        usort($onlineDeliveryMen, function (DeliveryMan $first, DeliveryMan $second) use ($delivery, $activeDeliveryByDriverId): int {
            $firstActive = isset($activeDeliveryByDriverId[$first->getDelivery_man_id() ?? 0]);
            $secondActive = isset($activeDeliveryByDriverId[$second->getDelivery_man_id() ?? 0]);
            if ($firstActive !== $secondActive) {
                return $firstActive <=> $secondActive;
            }

            $firstDistance = $this->distanceFromDeliveryManToDelivery($first, $delivery);
            $secondDistance = $this->distanceFromDeliveryManToDelivery($second, $delivery);
            if ($firstDistance !== $secondDistance) {
                return $firstDistance <=> $secondDistance;
            }

            return strcmp((string) $first->getName(), (string) $second->getName());
        });

        return $onlineDeliveryMen[0] ?? null;
    }

    private function distanceFromDeliveryManToDelivery(DeliveryMan $deliveryMan, Delivery $delivery): float
    {
        $driverLatitude = $this->toFloatOrNull($deliveryMan->getCurrentLatitude());
        $driverLongitude = $this->toFloatOrNull($deliveryMan->getCurrentLongitude());
        $destinationLatitude = $this->toFloatOrNull($delivery->getCurrentLatitude());
        $destinationLongitude = $this->toFloatOrNull($delivery->getCurrentLongitude());
        if (
            null === $driverLatitude
            || null === $driverLongitude
            || null === $destinationLatitude
            || null === $destinationLongitude
        ) {
            return INF;
        }

        return $this->haversineDistanceKm($driverLatitude, $driverLongitude, $destinationLatitude, $destinationLongitude);
    }

    private function haversineDistanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371.0;
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $fromLatitude = deg2rad($fromLatitude);
        $toLatitude = deg2rad($toLatitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitude) * cos($toLatitude) * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        if (null === $value || '' === $value || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
