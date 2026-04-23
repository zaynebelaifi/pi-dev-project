<?php

namespace App\Controller;

use App\Entity\FoodDonationEvent;
use App\Entity\FoodDonationItem;
use App\Form\FoodDonationEventType;
use App\Repository\DishRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use App\Service\TwilioSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/food/donation/event')]
final class FoodDonationEventController extends AbstractController
{
    public function __construct(
        private FoodDonationEventRepository $foodDonationEventRepository,
        private FoodDonationItemRepository $foodDonationItemRepository,
        private EventRegistrationRepository $eventRegistrationRepository,
        private DishRepository $dishRepository,
        private TwilioSmsService $twilioSmsService,
        private EntityManagerInterface $entityManager,
        private DompdfWrapperInterface $dompdf,
        private LoggerInterface $logger,
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

            $this->logger->info('Event created, calling SMS service', [
                'eventId' => (int) ($foodDonationEvent->getDonationEventId() ?? 0),
                'charityName' => (string) ($foodDonationEvent->getCharityName() ?? ''),
            ]);

            try {
                $smsResult = $this->twilioSmsService->sendEventCreatedSms($foodDonationEvent);
                $this->logger->info('Event created SMS service returned', [
                    'eventId' => (int) ($foodDonationEvent->getDonationEventId() ?? 0),
                    'result' => $smsResult,
                ]);

                if ((int) ($smsResult['failed'] ?? 0) > 0) {
                    $this->addFlash('warning', 'Event created, but some SMS notifications failed to send.');
                }
            } catch (\Throwable $exception) {
                $this->logger->error('Event created SMS service failed', [
                    'eventId' => (int) ($foodDonationEvent->getDonationEventId() ?? 0),
                    'error' => $exception->getMessage(),
                    'exception' => get_class($exception),
                ]);
                $this->addFlash('warning', 'Event created, but SMS notifications could not be sent.');
            }

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
<<<<<<< HEAD
        $eventId = (int) ($foodDonationEvent->getDonationEventId() ?? 0);
        $rawItems = $eventId > 0 ? $this->foodDonationItemRepository->findByDonationEventId($eventId) : [];
        $eventItems = array_map(static fn (array $item): array => [
            'name' => (string) ($item['dishName'] ?? 'Unnamed item'),
            'quantity' => (int) ($item['quantity'] ?? 0),
            'itemId' => (int) ($item['itemId'] ?? 0),
        ], $rawItems);
=======
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }
>>>>>>> ca885d35be836dd5c79d91d70d89d96a3c7663bc

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

        $form = $this->createForm(FoodDonationEventType::class, $foodDonationEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $foodDonationEvent->setUpdated_at(new \DateTimeImmutable());
            $foodDonationEvent->setSmsReminderSent(false);
            $entityManager->flush();

            try {
                $smsResult = $this->twilioSmsService->sendEventEditedSms($foodDonationEvent);
                if ((int) ($smsResult['failed'] ?? 0) > 0) {
                    $this->addFlash('warning', 'Event updated, but some SMS notifications failed to send.');
                }
            } catch (\Throwable) {
                $this->addFlash('warning', 'Event updated, but SMS notifications could not be sent.');
            }

            $this->addFlash('success', 'Donation event updated successfully.');
            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
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

        if ($this->isCsrfTokenValid('delete'.$foodDonationEvent->getDonation_event_id(), $request->request->get('_token'))) {
            $entityManager->remove($foodDonationEvent);
            $entityManager->flush();
            $this->addFlash('success', 'Donation event deleted successfully.');
        }

        return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
    }

<<<<<<< HEAD
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
=======
    private function denyUnlessAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
>>>>>>> ca885d35be836dd5c79d91d70d89d96a3c7663bc
    }
}
