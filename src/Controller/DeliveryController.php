<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Form\DeliveryType;
use App\Repository\DeliveryRepository;
use App\Repository\DeliveryManRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/delivery')]
final class DeliveryController extends AbstractController
{
    #[Route(name: 'app_delivery_index', methods: ['GET'])]
    public function index(Request $request, DeliveryRepository $deliveryRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search = trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'created_at');
        $direction = $request->query->get('direction', 'DESC');

        return $this->render('delivery/index.html.twig', [
            'deliveries' => $deliveryRepository->searchAndSort($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'app_delivery_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, DeliveryManRepository $deliveryManRepository): Response
    {
        $delivery = new Delivery();
        $form = $this->createForm(DeliveryType::class, $delivery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Auto-generate order ID
            $delivery->setOrder_id(random_int(100000, 999999));

            // Set estimated time to 30 minutes
            $delivery->setEstimated_time(30);

            // Set status to pending
            $delivery->setStatus('PENDING');

            // Set created_at timestamp
            $delivery->setCreated_at(new \DateTime());

            // Find available delivery man
            $availableDeliveryMen = $deliveryManRepository->findAvailableDeliveryMen();
            if (!empty($availableDeliveryMen)) {
                // Assign the first available delivery man (highest rated)
                $delivery->setDeliveryMan($availableDeliveryMen[0]);
            }

            $entityManager->persist($delivery);
            $entityManager->flush();

            return $this->redirectToRoute('app_delivery_confirmation', ['id' => $delivery->getDelivery_id()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('delivery/new.html.twig', [
            'delivery' => $delivery,
            'form' => $form,
        ]);
    }

    #[Route('/details/{id}', name: 'app_delivery_show', methods: ['GET'])]
    public function show(Request $request, Delivery $delivery): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('delivery/show.html.twig', [
            'delivery' => $delivery,
        ]);
    }

    #[Route('/confirmation/{id}', name: 'app_delivery_confirmation', methods: ['GET'])]
    public function confirmation(Delivery $delivery): Response
    {
        return $this->render('delivery/confirmation.html.twig', [
            'delivery' => $delivery,
        ]);
    }

    #[Route('/checkout', name: 'app_delivery_checkout', methods: ['POST'])]
    public function checkout(Request $request, EntityManagerInterface $entityManager, DeliveryManRepository $deliveryManRepository, SessionInterface $session): Response
    {
        try {
            if (!$this->isCsrfTokenValid('delivery-checkout', $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('app_home');
            }

            $rawCartItems = $request->request->get('cart_items', '[]');
            $orderTotal = $request->request->get('order_total', '0');
            $deliveryAddress = $request->request->get('delivery_address');
            $recipientName = $request->request->get('recipient_name');
            $recipientPhone = $request->request->get('recipient_phone');
            $pickupLocation = $request->request->get('pickup_location');
            $deliveryNotes = $request->request->get('delivery_notes');

            if (!$deliveryAddress || !$recipientName || !$recipientPhone || !$pickupLocation) {
                $this->addFlash('error', 'Please complete all required delivery details.');
                return $this->redirectToRoute('app_home');
            }

            $items = json_decode($rawCartItems, true);
            if (!is_array($items)) {
                $items = [];
            }

            $cartSummary = array_map(static function ($item) {
                return sprintf('%s (%s TND)', $item['name'] ?? '', number_format((float) ($item['price'] ?? 0), 2, '.', ''));
            }, $items);

            $delivery = new Delivery();
            $delivery->setOrder_id(random_int(100000, 999999));
            $delivery->setDelivery_address($deliveryAddress);
            $delivery->setRecipient_name($recipientName);
            $delivery->setRecipient_phone($recipientPhone);
            $delivery->setPickup_location($pickupLocation);
            $delivery->setDelivery_notes($deliveryNotes);
            $delivery->setCart_items(implode("\n", $cartSummary));
            $delivery->setOrder_total((string) number_format((float) $orderTotal, 2, '.', ''));
            $delivery->setEstimated_time(30);
            $delivery->setStatus('PENDING');
            $delivery->setCreated_at(new \DateTime());
            $delivery->setUpdated_at(new \DateTime());

            $availableDeliveryMen = $deliveryManRepository->findAvailableDeliveryMen();
            if (!empty($availableDeliveryMen)) {
                $delivery->setDeliveryMan($availableDeliveryMen[0]);
            }

            $entityManager->persist($delivery);
            $entityManager->flush();

            $session->set('client_phone', $recipientPhone);
            $session->set('client_name', $recipientName);

            // Debug: Check if delivery was created
            if (!$delivery->getDelivery_id()) {
                $this->addFlash('error', 'Failed to create delivery order.');
                return $this->redirectToRoute('app_home');
            }

            $this->addFlash('success', 'Your delivery order has been created successfully.');

            return $this->redirectToRoute('app_delivery_confirmation', ['id' => $delivery->getDelivery_id()], Response::HTTP_SEE_OTHER);
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while processing your order: ' . $e->getMessage());
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/client-orders', name: 'app_client_orders', methods: ['GET'])]
    public function clientOrders(Request $request, DeliveryRepository $deliveryRepository): Response
    {
        $session = $request->getSession();
        $recipientPhone = $session->get('client_phone');
        $recipientName = $session->get('client_name');

        $liveOrders = [];
        $historyOrders = [];

        if ($recipientPhone) {
            $liveOrders = $deliveryRepository->findActiveByRecipientPhone($recipientPhone);
            $historyOrders = $deliveryRepository->findDeliveredByRecipientPhone($recipientPhone);
        }

        return $this->render('delivery/client_orders.html.twig', [
            'recipientName' => $recipientName,
            'recipientPhone' => $recipientPhone,
            'liveOrders' => $liveOrders,
            'historyOrders' => $historyOrders,
        ]);
    }

    #[Route('/create', name: 'app_delivery_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, DeliveryManRepository $deliveryManRepository, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'ROLE_CLIENT') {
            return $this->redirectToRoute('app_home');
        }

        $cartItems = json_decode($request->query->get('cart_items', '[]'), true);
        $orderTotal = $request->query->get('order_total', '0');

        if ($request->isMethod('POST')) {
            $rawCartItems = $request->request->get('cart_items', '[]');
            $orderTotal = $request->request->get('order_total', '0');
            $deliveryAddress = trim((string) $request->request->get('delivery_address', ''));
            $recipientName = trim((string) $request->request->get('recipient_name', ''));
            $recipientPhone = trim((string) $request->request->get('recipient_phone', ''));
            $pickupLocation = trim((string) $request->request->get('pickup_location', ''));
            $deliveryNotes = trim((string) $request->request->get('delivery_notes', ''));

            if ($deliveryAddress === '' || $recipientName === '' || $recipientPhone === '' || $pickupLocation === '') {
                $this->addFlash('error', 'Please complete all required delivery details.');
                return $this->redirectToRoute('app_delivery_create', ['cart_items' => $rawCartItems, 'order_total' => $orderTotal]);
            }

            $items = json_decode($rawCartItems, true);
            if (!is_array($items)) {
                $items = [];
            }

            $cartSummary = array_map(static function ($item) {
                return sprintf('%s (%s TND)', $item['name'] ?? '', number_format((float) ($item['price'] ?? 0), 2, '.', ''));
            }, $items);

            $delivery = new Delivery();
            $delivery->setOrder_id(random_int(100000, 999999));
            $delivery->setDelivery_address($deliveryAddress);
            $delivery->setRecipient_name($recipientName);
            $delivery->setRecipient_phone($recipientPhone);
            $delivery->setPickup_location($pickupLocation);
            $delivery->setDelivery_notes($deliveryNotes);
            $delivery->setCart_items(implode("\n", $cartSummary));
            $delivery->setOrder_total((string) number_format((float) $orderTotal, 2, '.', ''));
            $delivery->setEstimated_time(30);
            $delivery->setStatus('PENDING');
            $delivery->setCreated_at(new \DateTime());
            $delivery->setUpdated_at(new \DateTime());

            $availableDeliveryMen = $deliveryManRepository->findAvailableDeliveryMen();
            if (!empty($availableDeliveryMen)) {
                $delivery->setDeliveryMan($availableDeliveryMen[0]);
            }

            $entityManager->persist($delivery);
            $entityManager->flush();

            $session->set('client_phone', $recipientPhone);
            $session->set('client_name', $recipientName);

            return $this->redirectToRoute('app_delivery_confirmation', ['id' => $delivery->getDelivery_id()]);
        }

        return $this->render('delivery/create.html.twig', [
            'cart_items' => $cartItems,
            'order_total' => $orderTotal,
        ]);
    }

    #[Route('/track/{id}', name: 'app_delivery_tracking', methods: ['GET', 'POST'])]
    public function track(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_CLIENT' || $session->get('client_phone') !== $delivery->getRecipient_phone()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST') && $delivery->getStatus() === 'DELIVERED') {
            if ($delivery->getRating() !== null) {
                $this->addFlash('error', 'This delivery has already been rated.');
            } else {
                $rating = $request->request->get('rating');
                if (!in_array($rating, ['1', '2', '3', '4', '5'], true)) {
                    $this->addFlash('error', 'Please select a valid rating between 1 and 5.');
                } else {
                    $delivery->setRating((int)$rating);
                    $entityManager->flush();
                    $this->addFlash('success', 'Thank you for rating your delivery!');
                }
            }
        }

        return $this->render('delivery/tracking.html.twig', [
            'delivery' => $delivery,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_delivery_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(DeliveryType::class, $delivery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_delivery_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('delivery/edit.html.twig', [
            'delivery' => $delivery,
            'form' => $form,
        ]);
    }

    #[Route('/driver', name: 'app_driver_deliveries', methods: ['GET'])]
    public function driverDeliveries(Request $request, DeliveryRepository $deliveryRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return $this->redirectToRoute('app_login');
        }

        $deliveryManId = $request->getSession()->get('delivery_man_id');
        if (!$deliveryManId) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('delivery/driver_deliveries.html.twig', [
            'deliveries' => $deliveryRepository->findByDeliveryManId($deliveryManId),
        ]);
    }

    #[Route('/{id}/driver-status', name: 'app_delivery_driver_status', methods: ['POST'])]
    public function updateDriverStatus(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return $this->redirectToRoute('app_login');
        }

        $deliveryManId = $request->getSession()->get('delivery_man_id');
        if (!$deliveryManId || $delivery->getDeliveryMan()?->getDelivery_man_id() !== $deliveryManId) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('driver_status' . $delivery->getDelivery_id(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_driver_deliveries');
        }

        $action = $request->request->get('action');
        if ($action === 'cancel') {
            $delivery->setStatus('CANCELLED');
        } elseif ($action === 'in_transit') {
            $delivery->setStatus('IN_TRANSIT');
        } elseif ($action === 'delivered') {
            $delivery->setStatus('DELIVERED');
            $delivery->setActual_delivery_date(new \DateTime());
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_driver_deliveries');
    }

    #[Route('/{id}', name: 'app_delivery_delete', methods: ['POST'])]
    public function delete(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete'.$delivery->getDelivery_id(), $request->request->get('_token'))) {
            $entityManager->remove($delivery);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_delivery_index', [], Response::HTTP_SEE_OTHER);
    }
}
