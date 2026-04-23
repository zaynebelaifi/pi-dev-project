<?php

namespace App\Controller;

use App\Repository\IngredientRepository;
use App\Repository\WasterecordRepository;
use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryRepository;
use App\Service\AdminAnalyticsService;
use App\Service\ExpiredIngredientWasteService;
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
}