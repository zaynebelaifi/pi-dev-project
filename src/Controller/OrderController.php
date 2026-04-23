<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\DeliveryRepository;
use App\Repository\OrderRepository;
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

        $pending = $repo->countByStatus('PENDING');
        $prepared = $repo->countByStatus('PREPARED');
        $delivered = $repo->countByStatus('DELIVERED');
        $revenue = $repo->getTotalRevenue();

        $viewData = [
            'orders' => $orders,
            'deliveries' => $deliveryMap,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'pending' => $pending,
            'prepared' => $prepared,
            'delivered' => $delivered,
            'revenue' => $revenue,
        ];

        $isAjaxRequest = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
        if ($isAjaxRequest) {
            return new JsonResponse([
                'success' => true,
                'resultsHtml' => $this->renderView('orders/_results.html.twig', $viewData),
            ]);
        }

        return $this->render('orders/index.html.twig', [
            'orders'      => $orders,
            'deliveries'  => $deliveryMap,
            'search'      => $search,
            'sort'        => $sort,
            'direction'   => $direction,
            'pending'     => $pending,
            'prepared'    => $prepared,
            'delivered'   => $delivered,
            'revenue'     => $revenue,
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

        if (!$cartItems || !$orderTotal || !$orderType) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid order data.']);
        }

        if (!in_array($orderType, ['DINE_IN', 'DELIVERY'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid order type.']);
        }

        $order = new Order();
        $order->setClientId(1);              // ✅
        $order->setOrderType($orderType);    // ✅
        $order->setOrderDate(new \DateTime()); // ✅
        $order->setStatus('PENDING');
        $order->setTotalAmount($orderTotal); // ✅
        $order->setCartItems($cartItems);    // ✅

        $em->persist($order);
        $em->flush();

        return new JsonResponse([
            'success'  => true,
            'message'  => 'Your order has been created successfully!',
            'order_id' => $order->getOrderId(), // ✅
        ]);
    }
}