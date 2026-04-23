<?php

namespace App\Controller;

use App\Repository\IngredientRepository;
use App\Repository\WasterecordRepository;
use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryRepository;
use App\Service\ExpiredIngredientWasteService;
use App\Repository\UserRepository;
use App\Repository\FleetCarRepository;
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

    #[Route('/users/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id);
        if ($user) {
            $user->setBanned(!$user->isBanned());
            $entityManager->flush();
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
        $deliveries = $deliveryRepository->searchAndSort($search, $sort, $direction);

        $viewData = [
            'deliveries' => $deliveries,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ];

        $isAjaxRequest = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
        if ($isAjaxRequest) {
            return new JsonResponse([
                'success' => true,
                'resultsHtml' => $this->renderView('admin/_deliveries_results.html.twig', $viewData),
            ]);
        }

        return $this->render('admin/deliveries.html.twig', [
            'deliveries' => $deliveries,
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
        $sort = (string) $request->query->get('sort', 'car_id');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));
        $direction = $direction === 'ASC' ? 'ASC' : 'DESC';

        $allowedSorts = [
            'car_id' => 'c.car_id',
            'make' => 'c.make',
            'model' => 'c.model',
            'license_plate' => 'c.license_plate',
            'vehicle_type' => 'c.vehicle_type',
        ];
        $sortField = $allowedSorts[$sort] ?? 'c.car_id';
        
        $queryBuilder = $fleetCarRepository->createQueryBuilder('c');
        if ($search) {
            $queryBuilder
                ->where('c.make LIKE :search OR c.model LIKE :search OR c.license_plate LIKE :search OR c.vehicle_type LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $vehicles = $queryBuilder->orderBy($sortField, $direction)->getQuery()->getResult();

        $viewData = [
            'vehicles' => $vehicles,
            'deliveryMen' => $deliveryManRepository->findAll(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ];

        $isAjaxRequest = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
        if ($isAjaxRequest) {
            return new JsonResponse([
                'success' => true,
                'resultsHtml' => $this->renderView('admin/_vehicles_results.html.twig', $viewData),
            ]);
        }

        return $this->render('admin/vehicles.html.twig', [
            'vehicles' => $vehicles,
            'deliveryMen' => $viewData['deliveryMen'],
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
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
        EntityManagerInterface $entityManager,
        IngredientRepository $ingredientRepository,
        WasterecordRepository $wasterecordRepository,
    ): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $conn = $entityManager->getConnection();
        $today = new \DateTimeImmutable('today');
        $from = $today->sub(new \DateInterval('P6D'));
        $previousFrom = $from->sub(new \DateInterval('P7D'));

        $summary = $conn->fetchAssociative(
            'SELECT
                COUNT(*) AS total_deliveries,
                COALESCE(SUM(order_total), 0) AS total_revenue,
                COALESCE(AVG(order_total), 0) AS avg_order,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN order_total ELSE 0 END), 0) AS today_revenue
             FROM delivery'
        ) ?: [];

        $statusRows = $conn->fetchAllAssociative(
            'SELECT UPPER(COALESCE(status, "UNKNOWN")) AS status_name, COUNT(*) AS total
             FROM delivery
             GROUP BY UPPER(COALESCE(status, "UNKNOWN"))'
        );

        $currentRangeRevenue = (float) ($conn->fetchOne(
            'SELECT COALESCE(SUM(order_total), 0)
             FROM delivery
             WHERE created_at >= :fromDate AND created_at < :toDate',
            [
                'fromDate' => $from->format('Y-m-d 00:00:00'),
                'toDate' => $today->modify('+1 day')->format('Y-m-d 00:00:00'),
            ]
        ) ?: 0);

        $previousRangeRevenue = (float) ($conn->fetchOne(
            'SELECT COALESCE(SUM(order_total), 0)
             FROM delivery
             WHERE created_at >= :fromDate AND created_at < :toDate',
            [
                'fromDate' => $previousFrom->format('Y-m-d 00:00:00'),
                'toDate' => $from->format('Y-m-d 00:00:00'),
            ]
        ) ?: 0);

        $trendRows = $conn->fetchAllAssociative(
            'SELECT DATE(created_at) AS day_key,
                    COUNT(*) AS deliveries,
                    COALESCE(SUM(order_total), 0) AS revenue
             FROM delivery
             WHERE created_at >= :fromDate
             GROUP BY DATE(created_at)
             ORDER BY day_key ASC',
            ['fromDate' => $from->format('Y-m-d 00:00:00')]
        );

        $trendMap = [];
        foreach ($trendRows as $row) {
            $trendMap[(string) $row['day_key']] = [
                'deliveries' => (int) $row['deliveries'],
                'revenue' => (float) $row['revenue'],
            ];
        }

        $dailySeries = [];
        $maxDailyRevenue = 0.0;
        for ($i = 0; $i < 7; $i++) {
            $day = $from->modify('+' . $i . ' day');
            $key = $day->format('Y-m-d');
            $point = $trendMap[$key] ?? ['deliveries' => 0, 'revenue' => 0.0];
            $maxDailyRevenue = max($maxDailyRevenue, (float) $point['revenue']);
            $dailySeries[] = [
                'label' => $day->format('D'),
                'fullDate' => $day->format('d M'),
                'deliveries' => (int) $point['deliveries'],
                'revenue' => (float) $point['revenue'],
            ];
        }

        $topWasteRows = $conn->fetchAllAssociative(
            'SELECT COALESCE(i.name, "Unknown") AS ingredient,
                    COALESCE(SUM(w.quantityWasted), 0) AS quantity
             FROM wasterecord w
             LEFT JOIN ingredient i ON i.id = w.ingredientId
             GROUP BY w.ingredientId, i.name
             ORDER BY quantity DESC
             LIMIT 5'
        );

        $topWaste = array_map(static fn (array $row): array => [
            'ingredient' => (string) $row['ingredient'],
            'quantity' => (float) $row['quantity'],
        ], $topWasteRows);

        $maxWaste = 0.0;
        foreach ($topWaste as $item) {
            $maxWaste = max($maxWaste, $item['quantity']);
        }

        $roleRows = $conn->fetchAllAssociative(
            'SELECT UPPER(COALESCE(role, "UNKNOWN")) AS role_name, COUNT(*) AS total
             FROM `user`
             GROUP BY UPPER(COALESCE(role, "UNKNOWN"))'
        );

        $roleLabels = [
            'ROLE_ADMIN' => 'Admin',
            'ADMIN' => 'Admin',
            'ROLE_CLIENT' => 'Client',
            'CLIENT' => 'Client',
            'ROLE_DELIVERY_MAN' => 'Delivery',
            'DELIVERY_MAN' => 'Delivery',
            'DELIVERY' => 'Delivery',
        ];

        $roleDistribution = [];
        foreach ($roleRows as $row) {
            $raw = (string) $row['role_name'];
            $label = $roleLabels[$raw] ?? $raw;
            if (!isset($roleDistribution[$label])) {
                $roleDistribution[$label] = 0;
            }
            $roleDistribution[$label] += (int) $row['total'];
        }

        $totalUsers = array_sum($roleDistribution);

        $statusDistribution = [
            'PENDING' => 0,
            'ASSIGNED' => 0,
            'DELIVERED' => 0,
            'OTHER' => 0,
        ];
        foreach ($statusRows as $row) {
            $status = (string) $row['status_name'];
            $count = (int) $row['total'];
            if (isset($statusDistribution[$status])) {
                $statusDistribution[$status] += $count;
            } else {
                $statusDistribution['OTHER'] += $count;
            }
        }

        $revenueDeltaPct = $previousRangeRevenue > 0
            ? (($currentRangeRevenue - $previousRangeRevenue) / $previousRangeRevenue) * 100
            : ($currentRangeRevenue > 0 ? 100.0 : 0.0);

        return $this->render('admin/analytics.html.twig', [
            'summary' => [
                'totalDeliveries' => (int) ($summary['total_deliveries'] ?? 0),
                'totalRevenue' => (float) ($summary['total_revenue'] ?? 0),
                'avgOrder' => (float) ($summary['avg_order'] ?? 0),
                'todayRevenue' => (float) ($summary['today_revenue'] ?? 0),
            ],
            'statusDistribution' => $statusDistribution,
            'dailySeries' => $dailySeries,
            'maxDailyRevenue' => $maxDailyRevenue,
            'topWaste' => $topWaste,
            'maxWaste' => $maxWaste,
            'ingredientHealth' => [
                'total' => $ingredientRepository->count([]),
                'lowStock' => $ingredientRepository->countLowStock(),
                'expired' => $ingredientRepository->countExpired($today),
                'inventoryValue' => $ingredientRepository->sumInventoryValue(),
                'totalWaste' => $wasterecordRepository->totalWastedQuantity(),
            ],
            'roleDistribution' => $roleDistribution,
            'totalUsers' => $totalUsers,
            'revenueDeltaPct' => $revenueDeltaPct,
        ]);
    }
}