<?php

namespace App\Controller;

use App\Entity\DonationEventItem;
use App\Entity\FoodDonationItem;
use App\Form\EventItemAssignmentType;
use App\Form\Model\EventItemAssignmentData;
use App\Form\Model\EventItemAssignmentLineData;
use App\Repository\DonationEventItemRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DonationItemController extends AbstractController
{
    public function __construct(
        private readonly DonationEventItemRepository $donationEventItemRepository,
        private readonly FoodDonationEventRepository $foodDonationEventRepository,
        private readonly FoodDonationItemRepository $foodDonationItemRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function show(string $id): Response
    {
        $eventId = $this->parseEventId($id);

        $event = $this->foodDonationEventRepository->find($eventId);
        if ($event === null) {
            throw $this->createNotFoundException('Donation event not found.');
        }

        $assignedItems = $this->donationEventItemRepository->findGroupedItemsForEvent($eventId);
        $totalItems = count($assignedItems);
        $totalQuantity = array_sum(array_map(static fn (array $row): int => $row['quantity'], $assignedItems));

        return $this->render('donation_item/show.html.twig', [
            'event' => $event,
            'assignedItems' => $assignedItems,
            'eventId' => $eventId,
            'totalItems' => $totalItems,
            'totalQuantity' => $totalQuantity,
        ]);
    }

    public function edit(string $id, Request $request): Response
    {
        $eventId = $this->parseEventId($id);
        $event = $this->foodDonationEventRepository->find($eventId);
        if ($event === null) {
            throw $this->createNotFoundException('Donation event not found.');
        }

        $assignmentData = new EventItemAssignmentData();
        $assignmentData->event = $event;

        $currentAssignments = $this->donationEventItemRepository->findByEventOrdered($eventId);
        foreach ($currentAssignments as $assignment) {
            $line = new EventItemAssignmentLineData();
            $line->assignmentId = $assignment->getId();
            $line->item = $assignment->getItem();
            $line->quantity = $assignment->getQuantity();
            $assignmentData->lines[] = $line;
        }

        if ($assignmentData->lines === []) {
            $assignmentData->lines[] = new EventItemAssignmentLineData();
        }

        $form = $this->createForm(EventItemAssignmentType::class, $assignmentData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedEvent = $assignmentData->event;
            if ($selectedEvent === null || $selectedEvent->getDonationEventId() === null) {
                $this->addFlash('error', 'Please select a valid event.');
                return $this->redirectToRoute('donation_item_edit', ['id' => $id]);
            }

            $groupedByItemId = [];
            foreach ($assignmentData->lines as $line) {
                if ($line->item === null || $line->quantity < 1 || $line->item->getId() === null) {
                    continue;
                }

                $itemId = (int) $line->item->getId();
                if (!isset($groupedByItemId[$itemId])) {
                    $groupedByItemId[$itemId] = ['item' => $line->item, 'quantity' => 0];
                }

                $groupedByItemId[$itemId]['quantity'] += $line->quantity;
            }

            foreach ($currentAssignments as $existingAssignment) {
                $this->entityManager->remove($existingAssignment);
            }

            foreach ($groupedByItemId as $row) {
                $newAssignment = (new DonationEventItem())
                    ->setEvent($selectedEvent)
                    ->setItem($row['item'])
                    ->setQuantity((int) $row['quantity']);
                $this->entityManager->persist($newAssignment);
            }

            $this->syncLegacyFoodDonationItems((int) $selectedEvent->getDonationEventId(), $groupedByItemId);

            $this->entityManager->flush();

            $this->addFlash('success', 'Event assignment updated successfully.');

            return $this->redirectToRoute('donation_item_show', [
                'id' => (string) $selectedEvent->getDonationEventId(),
            ]);
        }

        return $this->render('donation_item/edit.html.twig', [
            'eventId' => $eventId,
            'event' => $event,
            'form' => $form,
        ]);
    }

    public function removeItem(string $id, int $assignmentId, Request $request): Response
    {
        $eventId = $this->parseEventId($id);
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('remove_item_'.$assignmentId, $token)) {
            $this->addFlash('error', 'Invalid request token.');
            return $this->redirectToRoute('donation_item_show', ['id' => (string) $eventId]);
        }

        $assignment = $this->donationEventItemRepository->find($assignmentId);
        if ($assignment === null || $assignment->getEvent()?->getDonationEventId() !== $eventId) {
            $this->addFlash('error', 'Assigned item not found.');
            return $this->redirectToRoute('donation_item_show', ['id' => (string) $eventId]);
        }

        $this->entityManager->remove($assignment);

        $legacy = $this->foodDonationItemRepository->findOneBy([
            'donation_event_id' => $eventId,
            'item_id' => $assignment->getItem()?->getId(),
        ]);

        if ($legacy instanceof FoodDonationItem) {
            $this->entityManager->remove($legacy);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Assigned item removed.');

        return $this->redirectToRoute('donation_item_show', ['id' => (string) $eventId]);
    }

    public function deleteEvent(string $id, Request $request): Response
    {
        $eventId = $this->parseEventId($id);
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_event_'.$eventId, $token)) {
            $this->addFlash('error', 'Invalid request token.');
            return $this->redirectToRoute('app_food_donation_item_index');
        }

        $assignments = $this->donationEventItemRepository->findBy(['event' => $eventId]);
        foreach ($assignments as $assignment) {
            $this->entityManager->remove($assignment);
        }

        $legacyRows = $this->foodDonationItemRepository->findBy(['donation_event_id' => $eventId]);
        foreach ($legacyRows as $legacyRow) {
            $this->entityManager->remove($legacyRow);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'All donation items for this event have been deleted.');

        return $this->redirectToRoute('app_food_donation_item_index');
    }

    private function parseEventId(string $id): int
    {
        if (!preg_match('/^\d+$/', $id)) {
            throw $this->createNotFoundException('Invalid donation event id format.');
        }

        return (int) $id;
    }

    /**
     * @param array<int, array{item:object, quantity:int}> $groupedByItemId
     */
    private function syncLegacyFoodDonationItems(int $eventId, array $groupedByItemId): void
    {
        $legacyRows = $this->foodDonationItemRepository->findBy(['donation_event_id' => $eventId]);
        foreach ($legacyRows as $legacyRow) {
            $this->entityManager->remove($legacyRow);
        }

        foreach ($groupedByItemId as $itemId => $row) {
            $legacy = (new FoodDonationItem())
                ->setDonationEventId($eventId)
                ->setItemId((int) $itemId)
                ->setQuantity((int) $row['quantity']);

            $this->entityManager->persist($legacy);
        }
    }
}
