<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\DeliveryMan;
use App\Form\DeliveryType;
use App\Message\WhatsAppNotificationMessage;
use App\Repository\DeliveryRepository;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\DeliveryManRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
#[Route('/delivery')]
final class DeliveryController extends AbstractController
{
    #[Route(name: 'app_delivery_index', methods: ['GET'])]
    public function index(Request $request, DeliveryRepository $deliveryRepository, PaginatorInterface $paginator): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search = trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'created_at');
        $direction = $request->query->get('direction', 'DESC');

        $qb = $deliveryRepository->searchAndSortQueryBuilder($search, $sort, $direction);
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 25);

        $pagination = $paginator->paginate(
            $qb,
            $page,
            $limit
        );

        return $this->render('delivery/index.html.twig', [
            'pagination' => $pagination,
            'deliveries' => $pagination->getItems(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'app_delivery_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, DeliveryManRepository $deliveryManRepository, \App\Service\AIPriorityService $aiPriorityService): Response
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
            $delivery->setUpdated_at(new \DateTime());

            // Find available delivery men and build ordered candidate list by score
            $availableDeliveryMen = $deliveryManRepository->findAvailableDeliveryMen();
            if (!empty($availableDeliveryMen)) {
                $candidates = [];
                foreach ($availableDeliveryMen as $dm) {
                    try {
                        $score = $aiPriorityService->scoreDriverForDelivery($delivery, $dm);
                    } catch (\Throwable $e) {
                        $score = -INF;
                    }
                    $candidates[] = ['id' => $dm->getId(), 'score' => $score];
                }
                usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
                $candidateIds = array_map(fn($c) => $c['id'], $candidates);
                $delivery->setCandidateDeliveryMen($candidateIds);
                $delivery->setCandidateIndex(0);
                if (count($candidateIds) > 0) {
                    $first = $entityManager->getRepository(DeliveryMan::class)->find($candidateIds[0]);
                    if ($first) {
                        $delivery->setDeliveryMan($first);
                        $delivery->setStatus('PENDING_ASSIGNMENT');
                    }
                }
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
    public function checkout(Request $request, EntityManagerInterface $entityManager, DeliveryManRepository $deliveryManRepository, SessionInterface $session, ValidatorInterface $validator, UserRepository $userRepository): Response
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
            $latitude = $request->request->get('current_latitude');
            $longitude = $request->request->get('current_longitude');
            $orderTotal = $request->request->get('order_total', '0');

            $clientUser = null;
            if ($session->get('user_role') === 'ROLE_CLIENT') {
                $userId = $session->get('user_id');
                if ($userId) {
                    $clientUser = $userRepository->find($userId);
                }
            }

            if (!$recipientPhone && $clientUser && $clientUser->getPhone()) {
                $recipientPhone = $clientUser->getPhone();
            }
            if (!$recipientName && $clientUser) {
                $recipientName = trim($clientUser->getFirstName() . ' ' . $clientUser->getLastName());
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
            $delivery->setDelivery_address(trim((string) $deliveryAddress));
            $delivery->setRecipient_name(trim((string) $recipientName));
            $normalizedPhone = $this->normalizePhone(trim((string) $recipientPhone));
            $delivery->setRecipient_phone($normalizedPhone);
            $delivery->setPickup_location(trim((string) $pickupLocation));
            $delivery->setDelivery_notes(trim((string) $deliveryNotes));
            if ($latitude !== null && $latitude !== '') {
                $delivery->setCurrent_latitude(trim((string) $latitude));
            }
            if ($longitude !== null && $longitude !== '') {
                $delivery->setCurrent_longitude(trim((string) $longitude));
            }
            $delivery->setCart_items(implode("\n", $cartSummary));
            $delivery->setOrder_total((string) number_format((float) $orderTotal, 2, '.', ''));
            $delivery->setEstimated_time(30);
            $delivery->setStatus('PENDING');
            $delivery->setCreated_at(new \DateTime());
            $delivery->setUpdated_at(new \DateTime());

            $errors = $this->validateDelivery($delivery, $validator);
            if (!empty($errors)) {
                foreach ($errors as $message) {
                    $this->addFlash('error', $message);
                }
                return $this->redirectToRoute('app_home');
            }

            $this->assignDeliveryMan($delivery, $deliveryManRepository);

            $entityManager->persist($delivery);

            $this->syncClientPhoneWithProfile($session, $userRepository, $recipientPhone);

            $entityManager->flush();

            $session->set('client_phone', $this->normalizePhone($recipientPhone));
            $session->set('client_name', $recipientName);

            $this->addFlash('success', 'Your delivery order has been created successfully.');

            return $this->redirectToRoute('app_delivery_confirmation', ['id' => $delivery->getDelivery_id()], Response::HTTP_SEE_OTHER);
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while processing your order: ' . $e->getMessage());
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/client-orders', name: 'app_client_orders', methods: ['GET'])]
    public function clientOrders(Request $request, DeliveryRepository $deliveryRepository, UserRepository $userRepository): Response
    {
        $session = $request->getSession();

        if ($session->get('user_role') !== 'ROLE_CLIENT') {
            return $this->redirectToRoute('app_login');
        }

        $recipientPhone = $this->normalizePhone($session->get('client_phone'));
        $recipientName = $session->get('client_name');

        if (!$recipientPhone) {
            $userId = $session->get('user_id');
            if ($userId) {
                $user = $userRepository->find($userId);
                if ($user) {
                    if ($user->getPhone()) {
                        $recipientPhone = $this->normalizePhone($user->getPhone());
                        $session->set('client_phone', $recipientPhone);
                    }
                    if (!$recipientName) {
                        $recipientName = trim($user->getFirstName() . ' ' . $user->getLastName());
                        $session->set('client_name', $recipientName);
                    }
                    if (!$recipientPhone && $recipientName) {
                        $recentOrder = $deliveryRepository->findLatestByRecipientName($recipientName);
                        if ($recentOrder) {
                            $recipientPhone = $this->normalizePhone($recentOrder->getRecipient_phone());
                            $session->set('client_phone', $recipientPhone);
                        }
                    }
                }
            }
        }

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
    public function create(Request $request, EntityManagerInterface $entityManager, DeliveryManRepository $deliveryManRepository, SessionInterface $session, ValidatorInterface $validator, UserRepository $userRepository): Response
    {
        if ($session->get('user_role') !== 'ROLE_CLIENT') {
            return $this->redirectToRoute('app_home');
        }

        $cartItems = json_decode($request->query->get('cart_items', '[]'), true);
        $orderTotal = $request->query->get('order_total', '0');

        if ($request->isMethod('POST')) {
            $rawCartItems = $request->request->get('cart_items', '[]');
            $orderTotal = $request->request->get('order_total', '0');
            $deliveryAddress = $request->request->get('delivery_address');
            $recipientName = $request->request->get('recipient_name');
            $recipientPhone = $request->request->get('recipient_phone');
            $pickupLocation = $request->request->get('pickup_location');
            $deliveryNotes = $request->request->get('delivery_notes');

            $clientUser = null;
            if ($session->get('user_role') === 'ROLE_CLIENT') {
                $userId = $session->get('user_id');
                if ($userId) {
                    $clientUser = $userRepository->find($userId);
                }
            }

            if (!$recipientPhone && $clientUser && $clientUser->getPhone()) {
                $recipientPhone = $clientUser->getPhone();
            }
            if (!$recipientName && $clientUser) {
                $recipientName = trim($clientUser->getFirstName() . ' ' . $clientUser->getLastName());
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
            $delivery->setDelivery_address(trim((string) $deliveryAddress));
            $delivery->setRecipient_name(trim((string) $recipientName));
            $normalizedPhone = $this->normalizePhone(trim((string) $recipientPhone));
            $delivery->setRecipient_phone($normalizedPhone);
            $delivery->setPickup_location(trim((string) $pickupLocation));
            $delivery->setDelivery_notes(trim((string) $deliveryNotes));
            $delivery->setCart_items(implode("\n", $cartSummary));
            $delivery->setOrder_total((string) number_format((float) $orderTotal, 2, '.', ''));
            $delivery->setEstimated_time(30);
            $delivery->setStatus('PENDING');
            $delivery->setCreated_at(new \DateTime());
            $delivery->setUpdated_at(new \DateTime());

            $errors = $this->validateDelivery($delivery, $validator);
            if (!empty($errors)) {
                return $this->render('delivery/create.html.twig', [
                    'cart_items' => $items,
                    'order_total' => $orderTotal,
                    'form_values' => [
                        'delivery_address' => $deliveryAddress,
                        'recipient_name' => $recipientName,
                        'recipient_phone' => $recipientPhone,
                        'pickup_location' => $pickupLocation,
                        'delivery_notes' => $deliveryNotes,
                    ],
                    'errors' => $errors,
                ]);
            }

            $this->assignDeliveryMan($delivery, $deliveryManRepository);

            $entityManager->persist($delivery);
            $this->syncClientPhoneWithProfile($session, $userRepository, $recipientPhone);
            $entityManager->flush();

            $session->set('client_phone', $this->normalizePhone($recipientPhone));
            $session->set('client_name', $recipientName);

            return $this->redirectToRoute('app_delivery_confirmation', ['id' => $delivery->getDelivery_id()]);
        }

        $formValues = [
            'recipient_name' => '',
            'recipient_phone' => '',
        ];
        if ($session->get('user_role') === 'ROLE_CLIENT') {
            $formValues['recipient_name'] = $session->get('client_name') ?? '';
            $formValues['recipient_phone'] = $this->normalizePhone($session->get('client_phone')) ?? '';

            $userId = $session->get('user_id');
            $clientUser = $userId ? $userRepository->find($userId) : null;
            if ($clientUser) {
                if (!$formValues['recipient_name']) {
                    $formValues['recipient_name'] = trim($clientUser->getFirstName() . ' ' . $clientUser->getLastName());
                }
                if (!$formValues['recipient_phone']) {
                    $formValues['recipient_phone'] = $this->normalizePhone($clientUser->getPhone()) ?? '';
                }
            }
        }

        return $this->render('delivery/create.html.twig', [
            'cart_items' => $cartItems,
            'order_total' => $orderTotal,
            'form_values' => $formValues,
        ]);
    }

    #[Route('/save', name: 'app_delivery_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $entityManager, DeliveryRepository $deliveryRepository, DeliveryManRepository $deliveryManRepository, ValidatorInterface $validator, UserRepository $userRepository, SessionInterface $session, \Psr\Log\LoggerInterface $logger): JsonResponse
    {
        try {
            if (!$this->isCsrfTokenValid('delivery-save', $request->request->get('_token'))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid CSRF token.'
                ], Response::HTTP_FORBIDDEN);
            }

            $orderId = $request->request->get('order_id');
            if (!$orderId || !is_numeric($orderId)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid order ID.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $deliveryAddress = $request->request->get('delivery_address');
            $recipientName = $request->request->get('recipient_name');
            $recipientPhone = $request->request->get('recipient_phone');
            $pickupLocation = $request->request->get('pickup_location');
            $deliveryNotes = $request->request->get('delivery_notes');
            $latitude = $request->request->get('current_latitude');
            $longitude = $request->request->get('current_longitude');
            $rawCartItems = $request->request->get('cart_items', '[]');
            $orderTotal = $request->request->get('order_total', '0');

            $delivery = $deliveryRepository->findOneBy(['order_id' => (int) $orderId]);
            $isNew = false;
            if (!$delivery) {
                $delivery = new Delivery();
                $delivery->setOrder_id((int) $orderId);
                $delivery->setCreated_at(new \DateTime());
                $isNew = true;
            }

            $delivery->setDelivery_address(trim((string) $deliveryAddress));
            $delivery->setRecipient_name(trim((string) $recipientName));
            $normalizedPhone = $this->normalizePhone(trim((string) $recipientPhone));
            $delivery->setRecipient_phone($normalizedPhone);
            $delivery->setPickup_location(trim((string) $pickupLocation));
            $delivery->setDelivery_notes(trim((string) $deliveryNotes));
            // Normalize cart items: accept JSON array or newline-separated string
            $items = [];
            $maybe = $rawCartItems;
            if ($maybe && is_string($maybe)) {
                $decoded = json_decode($maybe, true);
                if (is_array($decoded)) {
                    $items = $decoded;
                } else {
                    // treat as newline separated lines
                    $items = array_filter(array_map('trim', preg_split('/\r?\n/', $maybe)));
                }
            }
            $cartSummary = [];
            foreach ($items as $it) {
                if (is_array($it)) {
                    $cartSummary[] = sprintf('%s (%s TND)', $it['name'] ?? '', number_format((float) ($it['price'] ?? 0), 2, '.', ''));
                } else {
                    $cartSummary[] = (string) $it;
                }
            }
            $delivery->setCart_items(implode("\n", $cartSummary));
            $delivery->setOrder_total((string) number_format((float) $orderTotal, 2, '.', ''));
            $delivery->setCurrent_latitude(trim((string) $latitude));
            $delivery->setCurrent_longitude(trim((string) $longitude));
            $delivery->setEstimated_time(30);
            $delivery->setStatus('PENDING');
            $delivery->setUpdated_at(new \DateTime());

            $errors = $this->validateDelivery($delivery, $validator);
            if (!empty($errors)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => implode(' ', $errors),
                    'errors' => $errors,
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($isNew) {
                $this->assignDeliveryMan($delivery, $deliveryManRepository);
                $entityManager->persist($delivery);
            } else {
                if (!$delivery->getDeliveryMan()) {
                    $this->assignDeliveryMan($delivery, $deliveryManRepository);
                }
            }

            $this->syncClientPhoneWithProfile($session, $userRepository, $recipientPhone);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'delivery_id' => $delivery->getDelivery_id(),
            ]);
        } catch (\Throwable $e) {
            $logger->error('Error in delivery save: ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse([
                'success' => false,
                'message' => 'Server error while saving delivery. ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/track/{id}', name: 'app_delivery_tracking', methods: ['GET', 'POST'])]
    public function track(Request $request, Delivery $delivery, EntityManagerInterface $entityManager, UserRepository $userRepository, HttpClientInterface $httpClient, \Psr\Log\LoggerInterface $logger): Response
    {
        // Handle rating submission if delivered
        if ($request->isMethod('POST') && $delivery->getStatus() === 'DELIVERED') {
            $deliveryManRating = $request->request->get('delivery_man_rating');
            $restaurantRating = $request->request->get('restaurant_rating');
            $reviewText = trim((string) $request->request->get('review_text', ''));
            $reviewEmail = trim((string) $request->request->get('review_email', ''));
            $hasUpdate = false;

            if ($deliveryManRating) {
                $delivery->setRating((int) $deliveryManRating);
                if ($delivery->getDeliveryMan()) {
                    $this->refreshDeliveryManRating($delivery->getDeliveryMan(), (int) $deliveryManRating);
                }
                $hasUpdate = true;
            }
            if ($restaurantRating) {
                $delivery->setRestaurant_rating((int) $restaurantRating);
                $hasUpdate = true;
            }

            if ($hasUpdate) {
                $entityManager->flush();
                $this->addFlash('success', 'Thank you for submitting your ratings!');
            }

            if ($reviewText === '' && ($deliveryManRating || $restaurantRating)) {
                $parts = [];
                if ($deliveryManRating) {
                    $parts[] = sprintf('Delivery rating: %s/5.', (string) $deliveryManRating);
                }
                if ($restaurantRating) {
                    $parts[] = sprintf('Restaurant rating: %s/5.', (string) $restaurantRating);
                }
                $reviewText = implode(' ', $parts);
            }

            if ($reviewText !== '') {
                $session = $request->getSession();
                $customerName = $delivery->getRecipient_name() ?? $delivery->getRecipientName() ?? 'Customer';
                $orderId = $delivery->getOrder_id() ?? $delivery->getOrderId();
                $ratingValue = $deliveryManRating ?: $restaurantRating ?: null;

                if ($reviewEmail === '') {
                    $reviewEmail = (string) ($session->get('user_email') ?? '');
                }
                if ($reviewEmail === '') {
                    $userId = $session->get('user_id');
                    if ($userId) {
                        $user = $userRepository->find($userId);
                        if ($user && method_exists($user, 'getEmail')) {
                            $reviewEmail = (string) ($user->getEmail() ?? '');
                        }
                    }
                }
                if ($reviewEmail === '') {
                    $reviewEmail = 'no-reply@example.com';
                }

                $feedbackBaseUrl = rtrim((string) ($_ENV['FEEDBACK_AI_BASE_URL'] ?? 'http://localhost:8000'), '/');

                try {
                    $httpClient->request('POST', $feedbackBaseUrl . '/webhook/review', [
                        'json' => [
                            'order_id' => (string) $orderId,
                            'customer_name' => (string) $customerName,
                            'customer_email' => (string) $reviewEmail,
                            'review_text' => $reviewText,
                            'rating' => $ratingValue ? (int) $ratingValue : null,
                        ],
                        'timeout' => 4,
                    ]);
                    $this->addFlash('success', 'Thank you! Your review has been submitted for AI verification.');
                } catch (\Throwable $e) {
                    $logger->warning('Feedback AI webhook failed: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('error', 'We saved your ratings, but the review service is unavailable right now. Please try again later.');
                }
            }
        }

        return $this->render('delivery/tracking.html.twig', [
            'delivery' => $delivery,
        ]);
    }

    #[Route('/{id}/location', name: 'app_delivery_update_location', methods: ['GET', 'POST'])]
    public function updateLocation(Request $request, Delivery $delivery, EntityManagerInterface $em, \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient): Response
    {
        if ($request->isMethod('GET')) {
            return $this->json([
                'client_latitude' => $delivery->getCurrentLatitude(),
                'client_longitude' => $delivery->getCurrentLongitude(),
                'driver_latitude' => $delivery->getDriverLatitude(),
                'driver_longitude' => $delivery->getDriverLongitude(),
            ]);
        }

        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $deliveryManId = (int) ($session->get('delivery_man_id') ?? 0);
        if ($deliveryManId <= 0 || $delivery->getDeliveryMan()?->getDelivery_man_id() !== $deliveryManId) {
            return $this->json(['success' => false, 'message' => 'Unauthorized for this delivery'], 403);
        }

        $data = json_decode($request->getContent() ?: '{}', true);
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lon = isset($data['lon']) ? (float) $data['lon'] : null;
        if ($lat === null || $lon === null) {
            return $this->json(['success' => false, 'message' => 'Missing coordinates'], 400);
        }

        $delivery->setDriverLatitude((string) $lat);
        $delivery->setDriverLongitude((string) $lon);
        $em->flush();

        // Try to notify WebSocket broadcast server (if running)
        try {
            $broadcastUrl = 'http://127.0.0.1:3001/broadcast';
            $httpClient->request('POST', $broadcastUrl, [
                'json' => [
                    'delivery_id' => $delivery->getDelivery_id(),
                    'driver_latitude' => (string) $lat,
                    'driver_longitude' => (string) $lon,
                ],
                'timeout' => 1,
            ]);
        } catch (\Throwable $e) {
            // ignore broadcast failures in case WS server is not running
        }

        return $this->json([
            'success' => true,
            'driver_latitude' => $lat,
            'driver_longitude' => $lon,
        ]);
    }

    #[Route('/delivery/{id}/mark-delivered', name: 'app_delivery_mark_delivered', methods: ['POST'])]
    public function markDelivered(Request $request, Delivery $delivery, EntityManagerInterface $em, MessageBusInterface $bus, \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient): Response
    {
        if ($delivery->getStatus() === 'DELIVERED') {
            return $this->json(['success' => false, 'message' => 'This delivery has already been marked as delivered.'], 400);
        }

        $data = json_decode($request->getContent() ?: '', true);
        $lat = is_array($data) && isset($data['latitude']) ? (float) $data['latitude'] : null;
        $lon = is_array($data) && isset($data['longitude']) ? (float) $data['longitude'] : null;
        if ($lat === null || $lon === null) {
            return $this->json(['success' => false, 'message' => 'GPS coordinates are required'], 400);
        }
        // Ensure the requester is the assigned delivery man
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $deliveryManId = (int) ($session->get('delivery_man_id') ?? 0);
        if ($deliveryManId <= 0 || $delivery->getDeliveryMan()?->getDelivery_man_id() !== $deliveryManId) {
            return $this->json(['success' => false, 'message' => 'Unauthorized for this delivery'], 403);
        }

        // Use the client's saved coordinates (delivery current latitude/longitude) for geofence check.
        // If missing, attempt a reverse-geocode (forward geocode) using the delivery address as a fallback.
        $clientLat = $delivery->getCurrentLatitude();
        $clientLon = $delivery->getCurrentLongitude();
        if ($clientLat === null || $clientLon === null || $clientLat === '' || $clientLon === '') {
            $address = $delivery->getDeliveryAddress();
            if (!$address) {
                return $this->json([
                    'success' => false,
                    'message' => 'Client location and delivery address are missing. Cannot verify delivery proximity.',
                ], 400);
            }

            try {
                $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($address);
                $resp = $httpClient->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => 'FirstProject/1.0 (+http://localhost)'
                    ],
                    'timeout' => 3,
                ]);
                $arr = $resp->toArray(false);
                if (is_array($arr) && count($arr) > 0 && isset($arr[0]['lat']) && isset($arr[0]['lon'])) {
                    $clientLat = (string) $arr[0]['lat'];
                    $clientLon = (string) $arr[0]['lon'];
                    // persist these coordinates on the delivery so future checks don't need geocoding
                    $delivery->setCurrentLatitude($clientLat);
                    $delivery->setCurrentLongitude($clientLon);
                    $em->flush();
                } else {
                    return $this->json([
                        'success' => false,
                        'message' => 'Could not determine client coordinates from delivery address.',
                    ], 400);
                }
            } catch (\Throwable $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Failed to geocode delivery address: ' . $e->getMessage(),
                ], 500);
            }
        }

        $clientLatF = (float) $clientLat;
        $clientLonF = (float) $clientLon;

        $distance = $this->haversineDistanceMeters($lat, $lon, $clientLatF, $clientLonF);
        if ($distance > 50.0) {
            $distanceMeters = (int) round($distance);
            return $this->json([
                'success' => false,
                'message' => sprintf(
                    'You must be within 50 meters of the client delivery address (you are %dm away)',
                    $distanceMeters
                ),
            ], 400);
        }

        $delivery->setStatus('DELIVERED');
        $delivery->setActual_delivery_date(new \DateTimeImmutable());
        $em->flush();

        // After marking this delivery as delivered, try to reassign any pending deliveries
        try {
            $this->reassignPendingDeliveriesForFreedDriver($deliveryManId, $em, $bus);
        } catch (\Throwable $e) {
            // do not prevent response on reassignment failures
        }

        $phone = $delivery->getRecipient_phone() ?? $delivery->getRecipientPhone();
        if ($phone) {
            $orderId = $delivery->getOrder_id() ?? $delivery->getOrderId();
            // Use WhatsApp template if available: delivery confirmation
            $recipientName = $delivery->getRecipient_name() ?? $delivery->getRecipientName() ?? 'Customer';
            $template = 'delivery_confirmation_5';
            $params = [trim((string) $recipientName), (string) $orderId];
            $text = sprintf('Hi %s, your order #%s was delivered. The delivery man is waiting. Enjoy your meal!', $recipientName, (string) $orderId);
            $bus->dispatch(new WhatsAppNotificationMessage((int) ($delivery->getDelivery_id() ?? $delivery->getDeliveryId()), (string) $phone, $text, $template, $params));
        }

        return $this->json(['success' => true]);
    }

    private function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function refreshDeliveryManRating(DeliveryMan $deliveryMan, int $newRating): void
    {
        $currentRating = (float) $deliveryMan->getRating();
        if ($currentRating > 0) {
            $deliveryMan->setRating(round(($currentRating + $newRating) / 2, 1));
        } else {
            $deliveryMan->setRating($newRating);
        }
    }

    private function assignDeliveryMan(Delivery $delivery, DeliveryManRepository $deliveryManRepository, array $excludedDeliveryManIds = []): ?DeliveryMan
    {
        // Build a full ranked candidate list using AI scoring (includes busy drivers)
        $allDeliveryMen = $deliveryManRepository->findAll();
        if (empty($allDeliveryMen)) {
            return null;
        }

        $ai = null;
        try {
            $ai = $this->container->get(\App\Service\AIPriorityService::class);
        } catch (\Throwable $e) {
            $ai = null;
        }

        $candidates = [];
        foreach ($allDeliveryMen as $dm) {
            if (in_array((int) $dm->getId(), $excludedDeliveryManIds, true)) {
                continue;
            }
            try {
                $score = $ai ? $ai->scoreDriverForDelivery($delivery, $dm) : 0.0;
            } catch (\Throwable $e) {
                $score = 0.0;
            }
            $candidates[] = ['id' => $dm->getId(), 'score' => $score];
        }
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        $candidateIds = array_map(fn($c) => $c['id'], $candidates);
        $delivery->setCandidateDeliveryMen($candidateIds);

        // Find first currently available candidate, otherwise keep unassigned but pending
        $available = $deliveryManRepository->findAvailableDeliveryMen();
        $availableIds = array_map(
            fn($d) => (int) $d->getId(),
            array_filter(
                $available,
                fn($d) => !in_array((int) $d->getId(), $excludedDeliveryManIds, true)
            )
        );

        $assigned = null;
        $assignedIndex = null;
        foreach ($candidateIds as $i => $id) {
            if (in_array($id, $availableIds, true)) {
                $assigned = $deliveryManRepository->find($id);
                $assignedIndex = $i;
                break;
            }
        }

        $delivery->setCandidateIndex($assignedIndex !== null ? $assignedIndex : 0);
        if ($assigned) {
            $delivery->setDeliveryMan($assigned);
            $delivery->setStatus('PENDING_ASSIGNMENT');
            return $assigned;
        }

        // No one available right now, mark pending assignment and wait for reassignment when drivers free up
        $delivery->setDeliveryMan(null);
        $delivery->setStatus('PENDING_ASSIGNMENT');
        return null;
    }

    private function reassignPendingDeliveriesForFreedDriver(int $freedDeliveryManId, EntityManagerInterface $entityManager, MessageBusInterface $bus): void
    {
        $repo = $entityManager->getRepository(Delivery::class);
        $pending = $repo->createQueryBuilder('d')
            ->andWhere('d.status IN (:statuses)')
            ->setParameter('statuses', ['PENDING_ASSIGNMENT', 'UNASSIGNED'])
            ->getQuery()
            ->getResult();
        if (empty($pending)) return;

        $freed = $entityManager->getRepository(DeliveryMan::class)->find($freedDeliveryManId);
        if (!$freed) return;

        foreach ($pending as $delivery) {
            $candidates = $delivery->getCandidateDeliveryMen() ?? [];
            if (!is_array($candidates) || empty($candidates)) {
                /** @var DeliveryManRepository $deliveryManRepository */
                $deliveryManRepository = $this->container->get(DeliveryManRepository::class);
                $this->assignDeliveryMan($delivery, $deliveryManRepository);
                $entityManager->flush();
                $candidates = $delivery->getCandidateDeliveryMen() ?? [];
                if (!is_array($candidates) || empty($candidates)) {
                    continue;
                }
            }

            $currentIndex = $delivery->getCandidateIndex() ?? 0;
            $foundIndex = array_search($freedDeliveryManId, $candidates, true);
            if ($foundIndex === false) continue;

            // Assign if delivery currently unassigned, or freed driver ranks higher than current assigned
            $shouldAssign = false;
            if ($delivery->getDeliveryMan() === null) {
                $shouldAssign = true;
            } elseif ($foundIndex < $currentIndex) {
                $shouldAssign = true;
            }

            if (!$shouldAssign) continue;

            $delivery->setDeliveryMan($freed);
            $delivery->setCandidateIndex($foundIndex);
            $delivery->setStatus('PENDING_ASSIGNMENT');
            $entityManager->flush();

            // Notify the freed driver about the available delivery
            $phone = $freed->getPhone();
            if ($phone) {
                $text = sprintf('A delivery has become available and is assigned to you: order #%s', (string) $delivery->getOrder_id());
                try {
                    $bus->dispatch(new WhatsAppNotificationMessage((int) ($delivery->getDelivery_id() ?? $delivery->getDeliveryId()), (string) $phone, $text, null, []));
                } catch (\Throwable $e) {
                    // ignore notification failures
                }
            }
        }
    }

    private function sendCancellationEmailToRecipient(
    Delivery $delivery, 
    UserRepository $userRepository, 
    \Symfony\Component\Mailer\MailerInterface $mailer
): void {
    // 1. Resolve phone and user
    $phone = $delivery->getRecipient_phone();
    $user = $phone ? $userRepository->findOneByPhoneLoose((string) $phone) : null;

    // 2. Extract email - if no email, we can't send anything
    $recipientEmail = ($user && method_exists($user, 'getEmail')) ? trim((string) $user->getEmail()) : null;

    if (!$recipientEmail) {
        return; 
    }

    // 3. Prepare data for the template
    $orderId = $delivery->getOrder_id() ?? 'N/A';
    $recipientName = $delivery->getRecipient_name() ?? 'Customer';

    // 4. Create the TemplatedEmail
    $email = (new TemplatedEmail())
        ->from('Zayneb.Elaifi@esprit.tn') // Use your verified sender
        ->to($recipientEmail)
        ->subject(sprintf('Order #%s Delivery Cancelled', $orderId))
        ->htmlTemplate('emails/delivery_cancellation.html.twig') // Ensure this filename matches exactly
        ->context([
            'orderId' => $orderId,
            'recipientName' => $recipientName,
            // Pass the tel: link here
            'supportUrl' => 'tel:50916717', 
            'supportPhone' => '50916717'
        ]);

    // 5. Send
    try {
        $mailer->send($email);
    } catch (\Exception $e) {
        // If it fails, the app won't crash, it just won't send the mail
    }
}
    private function syncClientPhoneWithProfile(SessionInterface $session, UserRepository $userRepository, ?string $phone): void
    {
        if (!$phone || !$session->get('user_role') || $session->get('user_role') !== 'ROLE_CLIENT') {
            return;
        }

        $userId = $session->get('user_id');
        if (!$userId) {
            return;
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return;
        }

        if ($phone) {
            $normalized = $this->normalizePhone($phone);
            $existingPhone = $this->normalizePhone($user->getPhone());
            if ($normalized && $normalized !== $existingPhone) {
                $user->setPhone($normalized);
            }
            $session->set('client_phone', $normalized);
        }

        if (!$session->get('client_name')) {
            $session->set('client_name', trim($user->getFirstName() . ' ' . $user->getLastName()));
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $phone);
        if (!$normalized) {
            return null;
        }

        if (!str_starts_with($normalized, '216')) {
            $normalized = '216' . ltrim($normalized, '+');
        }

        return $normalized;
    }

    private function validateDelivery(Delivery $delivery, ValidatorInterface $validator): array
    {
        $violations = $validator->validate($delivery);
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }

        return $errors;
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
    public function driverDeliveries(Request $request, DeliveryRepository $deliveryRepository, DeliveryManRepository $deliveryManRepository): Response
    {
        $session = $request->getSession();

        if ($session->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return $this->redirectToRoute('app_login');
        }

        $deliveryManId = (int) ($session->get('delivery_man_id') ?? 0);
        if ($deliveryManId <= 0) {
            $driverEmail = strtolower(trim((string) $session->get('user_email', '')));
            if ($driverEmail !== '') {
                $deliveryMan = $deliveryManRepository->createQueryBuilder('dm')
                    ->andWhere('LOWER(dm.email) = :email')
                    ->setParameter('email', $driverEmail)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($deliveryMan && $deliveryMan->getDelivery_man_id()) {
                    $deliveryManId = $deliveryMan->getDelivery_man_id();
                    $session->set('delivery_man_id', $deliveryManId);
                }
            }
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(5, (int) $request->query->get('limit', 15));
        $search = trim((string) $request->query->get('search', '')) ?: null;
        $sort = $request->query->get('sort', 'created_at');
        $direction = $request->query->get('direction', 'DESC');
        $status = $request->query->get('status', null);

        $deliveries = [];
        $total = 0;
        if ($deliveryManId > 0) {
            $pageData = $deliveryRepository->searchAndSortPaginated($search, $sort, $direction, $page, $limit, $deliveryManId, $status);
            $deliveries = $pageData['results'];
            $total = $pageData['total'];
        }

        $totalPages = (int) ceil($total / $limit ?: 1);

        return $this->render('delivery/driver_deliveries.html.twig', [
            'deliveries' => $deliveries,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'status' => $status,
        ]);
    }

    #[Route('/driver/data', name: 'app_driver_deliveries_data', methods: ['GET'])]
    public function driverDeliveriesData(Request $request, DeliveryRepository $deliveryRepository, DeliveryManRepository $deliveryManRepository): JsonResponse
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $deliveryManId = (int) ($session->get('delivery_man_id') ?? 0);
        if ($deliveryManId <= 0) {
            return new JsonResponse(['results' => [], 'total' => 0]);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(5, (int) $request->query->get('limit', 15));
        $search = trim((string) $request->query->get('search', '')) ?: null;
        $sort = $request->query->get('sort', 'created_at');
        $direction = $request->query->get('direction', 'DESC');
        $status = $request->query->get('status', null);

        $pageData = $deliveryRepository->searchAndSortPaginated($search, $sort, $direction, $page, $limit, $deliveryManId, $status);

        $html = $this->renderView('delivery/_driver_table.html.twig', [
            'deliveries' => $pageData['results'],
        ]);

        return new JsonResponse([
            'html' => $html,
            'total' => $pageData['total'],
        ]);
    }

    #[Route('/{id}/driver-status', name: 'app_delivery_driver_status', methods: ['POST'])]
    public function updateDriverStatus(
        Request $request,
        Delivery $delivery,
        EntityManagerInterface $entityManager,
        MessageBusInterface $bus,
        UserRepository $userRepository,
        \Symfony\Component\Mailer\MailerInterface $mailer,
        \Psr\Log\LoggerInterface $logger
    ): Response
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
        $delivery->setUpdated_at(new \DateTimeImmutable());
        $entityManager->flush();

        if ($action === 'cancel') {
            try {
                $this->sendCancellationEmailToRecipient($delivery, $userRepository, $mailer);
            } catch (\Throwable $e) {
                $logger->warning('Failed to send cancellation email: '.$e->getMessage(), ['exception' => $e]);
            }

            try {
                $this->reassignPendingDeliveriesForFreedDriver((int) $deliveryManId, $entityManager, $bus);
            } catch (\Throwable $e) {
                $logger->warning('Failed to reassign pending deliveries after cancellation: '.$e->getMessage(), ['exception' => $e]);
            }
        }

        return $this->redirectToRoute('app_driver_deliveries');
    }

    #[Route('/{id}/candidate/accept', name: 'app_delivery_candidate_accept', methods: ['POST'])]
    public function acceptCandidate(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return $this->redirectToRoute('app_login');
        }

        $deliveryManId = $request->getSession()->get('delivery_man_id');
        if (!$deliveryManId || $delivery->getDeliveryMan()?->getDelivery_man_id() !== $deliveryManId) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('candidate_accept' . $delivery->getDelivery_id(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_driver_deliveries');
        }

        $delivery->setStatus('ASSIGNED');
        $delivery->setCandidateDeliveryMen(null);
        $delivery->setCandidateIndex(null);
        $entityManager->flush();

        return $this->redirectToRoute('app_driver_deliveries');
    }

    #[Route('/{id}/candidate/decline', name: 'app_delivery_candidate_decline', methods: ['POST'])]
    public function declineCandidate(
        Request $request,
        Delivery $delivery,
        EntityManagerInterface $entityManager,
        MessageBusInterface $bus,
        DeliveryManRepository $deliveryManRepository
    ): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_DELIVERY_MAN') {
            return $this->redirectToRoute('app_login');
        }

        $deliveryManId = $request->getSession()->get('delivery_man_id');
        if (!$deliveryManId || $delivery->getDeliveryMan()?->getDelivery_man_id() !== $deliveryManId) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('candidate_decline' . $delivery->getDelivery_id(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_driver_deliveries');
        }

        $candidates = $delivery->getCandidateDeliveryMen() ?? [];
        $index = $delivery->getCandidateIndex() ?? 0;
        $index++;
        if ($index >= count($candidates)) {
            // No more candidates in the current list: regenerate candidates and keep pending assignment.
            $delivery->setDeliveryMan(null);
            $this->assignDeliveryMan($delivery, $deliveryManRepository, [(int) $deliveryManId]);
            $entityManager->flush();
            return $this->redirectToRoute('app_driver_deliveries');
        }

        $nextId = $candidates[$index];
        $next = $entityManager->getRepository(DeliveryMan::class)->find($nextId);
        if (!$next) {
            $delivery->setDeliveryMan(null);
            $this->assignDeliveryMan($delivery, $deliveryManRepository, [(int) $deliveryManId]);
            $entityManager->flush();
            return $this->redirectToRoute('app_driver_deliveries');
        }

        $delivery->setDeliveryMan($next);
        $delivery->setCandidateIndex($index);
        $delivery->setStatus('PENDING_ASSIGNMENT');
        $entityManager->flush();

        // notify next driver if phone present
        $phone = $next->getPhone();
        if ($phone) {
            $text = sprintf('New delivery available: order #%s', (string) $delivery->getOrder_id());
            $bus->dispatch(new WhatsAppNotificationMessage((int) ($delivery->getDelivery_id() ?? $delivery->getDeliveryId()), (string) $phone, $text, null, []));
        }

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
