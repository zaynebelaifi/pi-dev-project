<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\DeliveryRepository;
use App\Repository\OrderRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders')]
final class OrderController extends AbstractController
{
    #[Route('', name: 'app_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $repo, DeliveryRepository $deliveryRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search    = trim((string) $request->query->get('search', ''));
        $sort      = $request->query->get('sort', 'order_date');
        $direction = $request->query->get('direction', 'DESC');

        $orders = $repo->searchAndSort($search, $sort, $direction);
        $deliveryMap = [];

        $orderIds = array_map(
            static fn (Order $order): ?int => $order->getOrderId(), // ✅
            $orders
        );

        foreach ($deliveryRepository->findByOrderIds(array_filter($orderIds)) as $delivery) {
            $deliveryMap[$delivery->getOrderId()] = $delivery; // ✅
        }

        return $this->render('orders/index.html.twig', [
            'orders'      => $orders,
            'deliveries'  => $deliveryMap,
            'search'      => $search,
            'sort'        => $sort,
            'direction'   => $direction,
            'pending'     => $repo->countByStatus('PENDING'),
            'prepared'    => $repo->countByStatus('PREPARED'),
            'delivered'   => $repo->countByStatus('DELIVERED'),
            'revenue'     => $repo->getTotalRevenue(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $order = new Order();
        $form  = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setOrderDate(new \DateTime()); // ✅
            $em->persist($order);
            $em->flush();
            $this->addFlash('success', 'Order created successfully.');
            return $this->redirectToRoute('app_order_index');
        }

        return $this->render('orders/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Order updated.');
            return $this->redirectToRoute('app_order_index');
        }

        return $this->render('orders/edit.html.twig', [
            'form'  => $form->createView(),
            'order' => $order,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getOrderId(), $request->request->get('_token'))) { // ✅
            $em->remove($order);
            $em->flush();
            $this->addFlash('success', 'Order deleted.');
        }
        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/status/{status}', name: 'app_order_status', methods: ['POST'])]
    public function updateStatus(Request $request, Order $order, string $status, EntityManagerInterface $em): Response
    {
        $allowed = ['PENDING', 'PREPARED', 'DELIVERED'];
        if (in_array($status, $allowed) && $this->isCsrfTokenValid('status'.$order->getOrderId(), $request->request->get('_token'))) { // ✅
            $order->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Order status updated to ' . $status . '.');
        }
        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/create-from-cart', name: 'app_order_create_from_cart', methods: ['GET'])]
    public function createFromCart(Request $request, EntityManagerInterface $em): Response
    {
        $cartItems  = $request->query->get('cart_items');
        $orderTotal = $request->query->get('order_total');
        $orderType  = $request->query->get('order_type');
        $sessionUserId = (int) $request->getSession()->get('user_id', 0);

        if (!$cartItems || !$orderTotal || !$orderType) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid order data.']);
        }

        if (!in_array($orderType, ['DINE_IN', 'DELIVERY'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid order type.']);
        }

        if (!is_numeric($orderTotal) || (float) $orderTotal < 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid order total.']);
        }

        $clientId = $this->resolveClientIdForOrder($em->getConnection(), $sessionUserId);
        if ($clientId === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unable to identify a valid client account for this order. Please sign in and try again.',
            ], 400);
        }

        try {
            $order = new Order();
            $order->setClientId($clientId);
            $order->setOrderType($orderType);
            $order->setOrderDate(new \DateTime());
            $order->setStatus('PENDING');
            $order->setTotalAmount((string) $orderTotal);
            $order->setCartItems($cartItems);

            $em->persist($order);
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }

        return new JsonResponse([
            'success'  => true,
            'message'  => 'Your order has been created successfully!',
            'order_id' => $order->getOrderId(),
        ]);
    }

    private function resolveClientIdForOrder(Connection $connection, int $sessionUserId): ?int
    {
        $referencedTable = $connection->fetchOne(
            "SELECT REFERENCED_TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'orders'
               AND COLUMN_NAME = 'client_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        // If no FK is defined, prefer logged-in user id and then a safe default.
        if (!$referencedTable) {
            if ($sessionUserId > 0) {
                return $sessionUserId;
            }

            $fallbackUserId = $connection->fetchOne('SELECT id FROM `user` ORDER BY id ASC LIMIT 1');
            if ($fallbackUserId !== false && $fallbackUserId !== null) {
                return (int) $fallbackUserId;
            }

            return 1;
        }

        $table = strtolower((string) $referencedTable);

        if ($table === 'user1') {
            return $this->resolveLegacyUser1ClientId($connection, $sessionUserId);
        }

        if ($table !== 'user') {
            return null;
        }

        if ($sessionUserId > 0) {
            $existsInUser = (int) $connection->fetchOne('SELECT COUNT(*) FROM `user` WHERE id = :id', ['id' => $sessionUserId]);
            if ($existsInUser > 0) {
                return $sessionUserId;
            }
        }

        $fallbackId = $connection->fetchOne('SELECT id FROM `user` ORDER BY id ASC LIMIT 1');
        if ($fallbackId === false || $fallbackId === null) {
            return null;
        }

        return (int) $fallbackId;
    }

    private function resolveLegacyUser1ClientId(Connection $connection, int $sessionUserId): ?int
    {
        if ($sessionUserId > 0) {
            $existsInUser1 = (int) $connection->fetchOne('SELECT COUNT(*) FROM `user1` WHERE id = :id', ['id' => $sessionUserId]);
            if ($existsInUser1 > 0) {
                return $sessionUserId;
            }

            $user = $connection->fetchAssociative(
                "SELECT
                    COALESCE(NULLIF(TRIM(CONCAT_WS(' ', first_name, last_name)), ''), email) AS full_name,
                    email,
                    password_hash,
                    role
                 FROM `user`
                 WHERE id = :id
                 LIMIT 1",
                ['id' => $sessionUserId]
            );

            if (is_array($user) && !empty($user['email'])) {
                $email = strtolower(trim((string) $user['email']));
                $existingByEmail = $connection->fetchOne('SELECT id FROM `user1` WHERE email = :email LIMIT 1', ['email' => $email]);
                if ($existingByEmail !== false && $existingByEmail !== null) {
                    return (int) $existingByEmail;
                }

                $name = trim((string) ($user['full_name'] ?? ''));
                if ($name === '') {
                    $name = $email;
                }

                $password = (string) ($user['password_hash'] ?? '');
                if ($password === '') {
                    $password = hash('sha256', $email);
                }

                $role = strtoupper(trim((string) ($user['role'] ?? 'ROLE_CLIENT')));
                if ($role === '') {
                    $role = 'ROLE_CLIENT';
                }

                $newUser1Id = $this->nextLegacyUser1Id($connection);

                $connection->insert('user1', [
                    'id' => $newUser1Id,
                    'name' => substr($name, 0, 255),
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                    'status' => 'ACTIVE',
                ]);

                return $newUser1Id;
            }
        }

        $fallbackUser1Id = $connection->fetchOne('SELECT id FROM `user1` ORDER BY id ASC LIMIT 1');
        if ($fallbackUser1Id !== false && $fallbackUser1Id !== null) {
            return (int) $fallbackUser1Id;
        }

        // Seed user1 from the earliest user row if user1 is still empty.
        $seedUser = $connection->fetchAssociative(
            "SELECT
                COALESCE(NULLIF(TRIM(CONCAT_WS(' ', first_name, last_name)), ''), email) AS full_name,
                email,
                password_hash,
                role
             FROM `user`
             ORDER BY id ASC
             LIMIT 1"
        );

        if (!is_array($seedUser) || empty($seedUser['email'])) {
            return null;
        }

        $seedEmail = strtolower(trim((string) $seedUser['email']));
        $existingSeed = $connection->fetchOne('SELECT id FROM `user1` WHERE email = :email LIMIT 1', ['email' => $seedEmail]);
        if ($existingSeed !== false && $existingSeed !== null) {
            return (int) $existingSeed;
        }

        $seedName = trim((string) ($seedUser['full_name'] ?? ''));
        if ($seedName === '') {
            $seedName = $seedEmail;
        }

        $seedPassword = (string) ($seedUser['password_hash'] ?? '');
        if ($seedPassword === '') {
            $seedPassword = hash('sha256', $seedEmail);
        }

        $seedRole = strtoupper(trim((string) ($seedUser['role'] ?? 'ROLE_CLIENT')));
        if ($seedRole === '') {
            $seedRole = 'ROLE_CLIENT';
        }

        $insertedId = $this->nextLegacyUser1Id($connection);

        $connection->insert('user1', [
            'id' => $insertedId,
            'name' => substr($seedName, 0, 255),
            'email' => $seedEmail,
            'password' => $seedPassword,
            'role' => $seedRole,
            'status' => 'ACTIVE',
        ]);

        return $insertedId;
    }

    private function nextLegacyUser1Id(Connection $connection): int
    {
        $nextId = $connection->fetchOne('SELECT COALESCE(MAX(id), -1) + 1 FROM `user1`');
        if ($nextId === false || $nextId === null) {
            return 1;
        }

        return max(1, (int) $nextId);
    }
}