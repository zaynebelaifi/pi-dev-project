<?php

namespace App\Controller;

use App\Event\EventCreatedEvent;
use App\Event\EventUpdatedEvent;
use App\Entity\FoodDonationEvent;
use App\Entity\FoodDonationItem;
use App\Entity\User;
use App\Form\FoodDonationEventType;
use App\Repository\DishRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/food/donation/event')]
final class FoodDonationEventController extends AbstractController
{
    public function __construct(
        private FoodDonationEventRepository $foodDonationEventRepository,
        private FoodDonationItemRepository $foodDonationItemRepository,
        private EventRegistrationRepository $eventRegistrationRepository,
        private DishRepository $dishRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private DompdfWrapperInterface $dompdf,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route(name: 'app_food_donation_event_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $search = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', '');
        $sort = $request->query->get('sort', 'event_date');
        $direction = $request->query->get('direction', 'asc');
        $newEventId = $request->query->getInt('newEventId', 0);
        $events = $this->foodDonationEventRepository->findFilteredEvents($search, $status, $sort, $direction);

        $eventIds = array_values(array_filter(array_map(
            static fn (FoodDonationEvent $event): ?int => $event->getDonationEventId(),
            $events
        )));

        $itemCountsByEvent = $this->foodDonationItemRepository->countByEventIds($eventIds);
        $eventItemsMap = $this->foodDonationItemRepository->findGroupedByEventIds($eventIds);

        return $this->render('admin/food_donation_event/index.html.twig', [
            'food_donation_events' => $events,
            'availableDishes' => $this->dishRepository->findAll(),
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
            'newEventId' => $newEventId > 0 ? $newEventId : null,
            'itemCountsByEvent' => $itemCountsByEvent,
            'eventItemsMap' => $eventItemsMap,
        ]);
    }

    #[Route('/new', name: 'app_food_donation_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $foodDonationEvent = new FoodDonationEvent();
        $form = $this->createForm(FoodDonationEventType::class, $foodDonationEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $now = new \DateTimeImmutable();
            $foodDonationEvent->setCreated_at($now);
            $foodDonationEvent->setUpdated_at($now);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($foodDonationEvent);
            $this->entityManager->flush();

            $organizer = $this->resolveCurrentUser($request);
            $this->eventDispatcher->dispatch(new EventCreatedEvent($foodDonationEvent, $organizer));

            $this->addFlash('success', 'Donation event created successfully.');
            return $this->redirectToRoute('app_food_donation_event_index', [
                'newEventId' => $foodDonationEvent->getDonationEventId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/food_donation_event/new.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_event_show', methods: ['GET'])]
    public function show(Request $request, FoodDonationEvent $foodDonationEvent): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $eventId = (int) ($foodDonationEvent->getDonationEventId() ?? 0);
        $rawItems = $eventId > 0 ? $this->foodDonationItemRepository->findByDonationEventId($eventId) : [];
        $eventItems = array_map(static fn (array $item): array => [
            'name' => (string) ($item['dishName'] ?? 'Unnamed item'),
            'quantity' => (int) ($item['quantity'] ?? 0),
            'itemId' => (int) ($item['itemId'] ?? 0),
        ], $rawItems);

        return $this->render('admin/food_donation_event/show.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'eventItems' => $eventItems,
        ]);
    }

    #[Route('/{donation_event_id}/edit', name: 'app_food_donation_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FoodDonationEvent $foodDonationEvent, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $isAjax = $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        $form = $this->createForm(FoodDonationEventType::class, $foodDonationEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $foodDonationEvent->setUpdated_at(new \DateTimeImmutable());
            $foodDonationEvent->setSmsReminderSent(false);
            $entityManager->flush();

            $organizer = $this->resolveCurrentUser($request);
            $updatedEvent = new EventUpdatedEvent($foodDonationEvent, $organizer);
            $this->eventDispatcher->dispatch($updatedEvent);

            if ($isAjax) {
                if (!$updatedEvent->isSmsDispatchSuccessful()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $updatedEvent->getSmsErrorMessage() ?? 'Event saved but SMS delivery failed. Please retry before choosing items.',
                        'sms' => [
                            'recipientCount' => $updatedEvent->getSmsRecipientCount(),
                            'sentCount' => $updatedEvent->getSmsSentCount(),
                        ],
                    ], Response::HTTP_CONFLICT);
                }

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Donation event updated successfully.',
                    'eventId' => (int) $foodDonationEvent->getDonationEventId(),
                    'sms' => [
                        'recipientCount' => $updatedEvent->getSmsRecipientCount(),
                        'sentCount' => $updatedEvent->getSmsSentCount(),
                    ],
                    'redirectUrl' => $this->generateUrl('app_food_donation_event_index', [
                        'newEventId' => $foodDonationEvent->getDonationEventId(),
                    ]),
                ]);
            }

            $this->addFlash('success', 'Donation event updated successfully.');
            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($isAjax && $form->isSubmitted()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please fix the highlighted fields and try again.',
                'errors' => $this->collectFormErrors($form),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('admin/food_donation_event/edit.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'form' => $form,
            'autoCalculatedStatus' => $foodDonationEvent->getEventDate() instanceof \DateTimeInterface
                ? FoodDonationEvent::calculateAutoStatus($foodDonationEvent->getEventDate())
                : FoodDonationEvent::STATUS_SCHEDULED,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_event_delete', methods: ['POST'])]
    public function delete(Request $request, FoodDonationEvent $foodDonationEvent, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $isAjax = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
        $token = (string) $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete'.$foodDonationEvent->getDonation_event_id(), $token)) {
            if ($isAjax) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid security token. Please refresh and try again.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Invalid security token. Please refresh and try again.');

            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
        }

        $entityManager->remove($foodDonationEvent);
        $entityManager->flush();

        if ($isAjax) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Donation event deleted successfully.',
                'eventId' => (int) $foodDonationEvent->getDonationEventId(),
            ]);
        }

        $this->addFlash('success', 'Donation event deleted successfully.');

        return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{donation_event_id}/export-pdf', name: 'app_food_donation_event_export_pdf', methods: ['GET'])]
    public function exportPdf(FoodDonationEvent $foodDonationEvent): Response
    {
        $eventId = (int) $foodDonationEvent->getDonationEventId();
        $items = $this->foodDonationItemRepository->findByDonationEventId($eventId);
        $registeredUsersCount = $this->eventRegistrationRepository->countByEventIds([$eventId])[$eventId] ?? 0;

        $eventTitle = sprintf(
            'Food Donation Event #%d - %s',
            $eventId,
            (string) ($foodDonationEvent->getCharityName() ?? 'BIG 4 Community')
        );

        $html = $this->renderView('admin/food_donation_event/export_pdf.html.twig', [
            'event' => $foodDonationEvent,
            'eventTitle' => $eventTitle,
            'registeredUsersCount' => $registeredUsersCount,
            'items' => $items,
        ]);

        $filename = sprintf('food-donation-event-%d.pdf', $eventId);

        return $this->dompdf->getStreamResponse($html, $filename, [
            'Attachment' => true,
        ]);
    }

    #[Route('/{id}/assign-items', name: 'app_food_donation_event_assign_items', methods: ['POST'])]
    public function assignItems(int $id, Request $request): RedirectResponse
    {
        $event = $this->foodDonationEventRepository->find($id);
        if (!$event) {
            $this->addFlash('error', 'Donation event not found.');

            return $this->redirectToRoute('app_food_donation_event_index');
        }

        if (!$this->isCsrfTokenValid('assign-items', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token. Please try again.');

            return $this->redirectToRoute('app_food_donation_event_index');
        }

        $selectedItems = $request->request->all('items');
        if (!is_array($selectedItems)) {
            $selectedItems = [];
        }

<<<<<<< HEAD
        $selectedCount = 0;
        foreach ($selectedItems as $itemData) {
            if (is_array($itemData) && isset($itemData['selected']) && (string) $itemData['selected'] === '1') {
                $selectedCount++;
            }
        }

        $maxItems = max(0, (int) ($event->getTotalQuantity() ?? 0));
        if ($selectedCount > $maxItems) {
            $this->addFlash('error', sprintf(
                'You can only assign up to %d items for this event. You selected %d.',
                $maxItems,
                $selectedCount
            ));

            return $this->redirectToRoute('app_food_donation_event_index', [
                'newEventId' => $event->getDonationEventId(),
            ]);
        }

=======
>>>>>>> final2
        $addedCount = 0;
        foreach ($selectedItems as $itemId => $itemData) {
            if (!is_array($itemData)) {
                continue;
            }

            $isSelected = isset($itemData['selected']) && (string) $itemData['selected'] === '1';
            if (!$isSelected) {
                continue;
            }

            $dishId = (int) $itemId;
            $quantity = max(1, (int) ($itemData['quantity'] ?? 1));
            if ($dishId <= 0) {
                continue;
            }

            $existing = $this->foodDonationItemRepository->findOneBy([
                'donation_event_id' => $event->getDonationEventId(),
                'item_id' => $dishId,
            ]);

            if ($existing instanceof FoodDonationItem) {
                $existing->setQuantity($quantity);
                $addedCount++;
                continue;
            }

            $item = (new FoodDonationItem())
                ->setDonationEventId((int) $event->getDonationEventId())
                ->setItemId($dishId)
                ->setQuantity($quantity);

            $this->entityManager->persist($item);
            $addedCount++;
        }

        $this->entityManager->flush();

        if ($addedCount > 0) {
            $this->addFlash('success', 'Items successfully assigned to the event!');
        } else {
            $this->addFlash('error', 'No items were selected.');
        }

        return $this->redirectToRoute('app_food_donation_event_index');
    }

<<<<<<< HEAD
=======
>>>>>>> Stashed changes
>>>>>>> final2
    private function denyUnlessAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }

    private function resolveCurrentUser(Request $request): ?User
    {
        $securityUser = $this->getUser();
        if ($securityUser instanceof User) {
            return $securityUser;
        }

        $sessionUserId = $request->getSession()->get('user_id');
        if (!is_numeric($sessionUserId)) {
            return null;
        }

        return $this->userRepository->find((int) $sessionUserId);
    }

    /**
     * @return array<string, string[]>
     */
    private function collectFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true, true) as $error) {
            $origin = $error->getOrigin();
            if ($origin === null) {
                continue;
            }

            $field = $origin->getName();
            if ($field === $form->getName()) {
                $field = '_form';
            }

            $errors[$field] ??= [];
            $errors[$field][] = $error->getMessage();
        }

        return $errors;
    }
}
