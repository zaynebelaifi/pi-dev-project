<?php

declare(strict_types=1);

namespace App\OrdersReservationsAnalyticsBundle\Controller;

use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryRepository;
use App\Repository\FleetCarRepository;
use App\Repository\IngredientRepository;
use App\Repository\OrderRepository;
use App\Repository\ReservationRepository;
use App\Repository\WasterecordRepository;
use App\Service\ExpiredIngredientWasteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
final class OrdersReservationsAnalyticsController extends AbstractController
{
    #[Route('/orders-reservations-analytics', name: 'app_admin_orders_reservations_analytics', methods: ['GET'])]
    public function index(
        Request $request,
        DeliveryRepository $deliveryRepository,
        DeliveryManRepository $deliveryManRepository,
        FleetCarRepository $fleetCarRepository,
        IngredientRepository $ingredientRepository,
        WasterecordRepository $wasterecordRepository,
        ExpiredIngredientWasteService $expiredWasteService,
        ReservationRepository $reservationRepository,
        OrderRepository $orderRepository,
    ): Response {
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
            'reservationCount' => $reservationRepository->count([]),
            'reservationConfirmedCount' => $reservationRepository->countByStatus('CONFIRMED'),
            'reservationCancelledCount' => $reservationRepository->countByStatus('CANCELLED'),
            'orderCount' => $orderRepository->count([]),
            'orderPendingCount' => $orderRepository->countByStatus('PENDING'),
            'orderPreparedCount' => $orderRepository->countByStatus('PREPARED'),
            'orderDeliveredCount' => $orderRepository->countByStatus('DELIVERED'),
            'orderRevenue' => $orderRepository->getTotalRevenue(),
            'showOnlyOrdersReservations' => true,
        ]);
    }
}
