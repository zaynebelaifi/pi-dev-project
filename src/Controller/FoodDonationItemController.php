<?php

namespace App\Controller;

use App\Entity\FoodDonationItem;
use App\Entity\Ingredient;
use App\Form\FoodDonationItemType;
use App\Repository\DishRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/food/donation/item')]
final class FoodDonationItemController extends AbstractController
{
    #[Route(name: 'app_food_donation_item_index', methods: ['GET'])]
    public function index(Request $request, FoodDonationItemRepository $foodDonationItemRepository, FoodDonationEventRepository $foodDonationEventRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $search = trim((string) $request->query->get('q', ''));
        $sort = $request->query->get('sort', 'donation_event_id');
        $direction = $request->query->get('direction', 'asc');

        $itemsForView = [];
        $allEvents = $foodDonationEventRepository->findBy([], ['event_date' => 'ASC']);
        $eventIds = array_values(array_filter(array_map(
            static fn ($event): int => (int) ($event->getDonationEventId() ?? 0),
            $allEvents
        )));

        $groupedItemsByEvent = $foodDonationItemRepository->findGroupedByEventIds($eventIds);

        foreach ($allEvents as $event) {
            $eventId = (int) ($event->getDonationEventId() ?? 0);
            if ($eventId <= 0) {
                continue;
            }

            $charityName = (string) ($event->getCharityName() ?? '');
            $itemsForView[$eventId] = [
                'donation_event_id' => $eventId,
                'donationEvent' => $event,
                'items' => [],
                'search_text' => $eventId . ' ' . $charityName,
            ];

            foreach ($groupedItemsByEvent[$eventId] ?? [] as $groupedItem) {
                $itemName = (string) ($groupedItem['dishName'] ?? 'Unknown');
                $itemId = (int) ($groupedItem['itemId'] ?? 0);
                $quantity = (int) ($groupedItem['quantity'] ?? 0);

                $itemsForView[$eventId]['items'][] = [
                    'assignment_id' => $itemId,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'name' => $itemName,
                ];

                $itemsForView[$eventId]['search_text'] .= ' ' . $eventId . ' ' . $itemName . ' ' . $quantity;
            }
        }

        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $itemsForView = array_filter(
                $itemsForView,
                static fn (array $row): bool => str_contains(mb_strtolower((string) $row['search_text']), $searchLower)
            );
        }

        $itemsForView = array_values($itemsForView);

        usort($itemsForView, static function (array $a, array $b) use ($sort, $direction): int {
            $multiplier = strtolower($direction) === 'desc' ? -1 : 1;
            $aEvent = $a['donationEvent'];
            $bEvent = $b['donationEvent'];

            return match ($sort) {
                'item_name' => $multiplier * strcmp((string) ($a['items'][0]['name'] ?? ''), (string) ($b['items'][0]['name'] ?? '')),
                'quantity' => $multiplier * (array_sum(array_column($a['items'], 'quantity')) <=> array_sum(array_column($b['items'], 'quantity'))),
                default => $multiplier * (((int) ($aEvent?->getDonationEventId() ?? 0)) <=> ((int) ($bEvent?->getDonationEventId() ?? 0))),
            };
        });

        return $this->render('admin/food_donation_item/index.html.twig', [
            'food_donation_items' => $itemsForView,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'app_food_donation_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, FoodDonationEventRepository $eventRepository, DishRepository $dishRepository, IngredientRepository $ingredientRepository, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $ingredientId = $request->query->getInt('ingredient_id');
        $ingredient = $ingredientId ? $ingredientRepository->find($ingredientId) : null;

        $eventChoices = [];
        foreach ($eventRepository->findAll() as $event) {
            $eventChoices[$event->getCharityName() . ' (#' . $event->getDonationEventId() . ')'] = $event->getDonationEventId();
        }

        $dishChoices = [];
        if ($ingredient instanceof Ingredient) {
            foreach ($ingredient->getDishIngredients() as $dishIngredient) {
                $dish = $dishIngredient->getDish();
                if ($dish instanceof \App\Entity\Dish) {
                    $dishChoices[$dish->getName() . ' (#' . $dish->getId() . ')'] = $dish->getId();
                }
            }
        }

        if (empty($dishChoices)) {
            foreach ($dishRepository->findAll() as $dish) {
                $dishChoices[$dish->getName() . ' (#' . $dish->getId() . ')'] = $dish->getId();
            }
        }

        $foodDonationItem = new FoodDonationItem();
        $form = $this->createForm(FoodDonationItemType::class, $foodDonationItem, [
            'event_choices' => $eventChoices,
            'dish_choices' => $dishChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($foodDonationItem);
            $entityManager->flush();

            $this->addFlash('success', 'Donation item created successfully.');
            return $this->redirectToRoute('app_food_donation_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/food_donation_item/new.html.twig', [
            'food_donation_item' => $foodDonationItem,
            'form' => $form,
            'ingredient' => $ingredient,
        ]);
    }

    #[Route('/{donation_event_id}/{item_id}', name: 'app_food_donation_item_show', methods: ['GET'])]
    public function show(Request $request, FoodDonationItem $foodDonationItem): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        return $this->render('admin/food_donation_item/show.html.twig', [
            'food_donation_item' => $foodDonationItem,
        ]);
    }

    #[Route('/{donation_event_id}/{item_id}/edit', name: 'app_food_donation_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FoodDonationItem $foodDonationItem, FoodDonationEventRepository $eventRepository, DishRepository $dishRepository, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $eventChoices = [];
        foreach ($eventRepository->findAll() as $event) {
            $eventChoices[$event->getCharityName() . ' (#' . $event->getDonationEventId() . ')'] = $event->getDonationEventId();
        }

        $dishChoices = [];
        foreach ($dishRepository->findAll() as $dish) {
            $dishChoices[$dish->getName() . ' (#' . $dish->getId() . ')'] = $dish->getId();
        }

        $form = $this->createForm(FoodDonationItemType::class, $foodDonationItem, [
            'event_choices' => $eventChoices,
            'dish_choices' => $dishChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Donation item updated successfully.');
            return $this->redirectToRoute('app_food_donation_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/food_donation_item/edit.html.twig', [
            'food_donation_item' => $foodDonationItem,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}/{item_id}', name: 'app_food_donation_item_delete', methods: ['POST'])]
    public function delete(Request $request, FoodDonationItem $foodDonationItem, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        if ($this->isCsrfTokenValid('delete'.$foodDonationItem->getDonation_event_id().'_'.$foodDonationItem->getItem_id(), $request->request->get('_token'))) {
            $entityManager->remove($foodDonationItem);
            $entityManager->flush();
            $this->addFlash('success', 'Donation item deleted successfully.');
        }

        return $this->redirectToRoute('app_food_donation_item_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyUnlessAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }
}
