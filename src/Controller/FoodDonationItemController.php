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
    public function index(Request $request, FoodDonationItemRepository $foodDonationItemRepository, DishRepository $dishRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $sort = $request->query->get('sort', 'donation_event_id');
        $direction = $request->query->get('direction', 'asc');

        $dishNames = [];
        foreach ($dishRepository->findAll() as $dish) {
            $dishNames[$dish->getId()] = $dish->getName();
        }

        return $this->render('admin/food_donation_item/index.html.twig', [
            'food_donation_items' => $foodDonationItemRepository->findFilteredItems($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'dish_names' => $dishNames,
        ]);
    }

    #[Route('/new', name: 'app_food_donation_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, FoodDonationEventRepository $eventRepository, DishRepository $dishRepository, IngredientRepository $ingredientRepository, EntityManagerInterface $entityManager): Response
    {
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
    public function show(FoodDonationItem $foodDonationItem): Response
    {
        return $this->render('admin/food_donation_item/show.html.twig', [
            'food_donation_item' => $foodDonationItem,
        ]);
    }

    #[Route('/{donation_event_id}/{item_id}/edit', name: 'app_food_donation_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FoodDonationItem $foodDonationItem, FoodDonationEventRepository $eventRepository, DishRepository $dishRepository, EntityManagerInterface $entityManager): Response
    {
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
        if ($this->isCsrfTokenValid('delete'.$foodDonationItem->getDonation_event_id().'_'.$foodDonationItem->getItem_id(), $request->request->get('_token'))) {
            $entityManager->remove($foodDonationItem);
            $entityManager->flush();
            $this->addFlash('success', 'Donation item deleted successfully.');
        }

        return $this->redirectToRoute('app_food_donation_item_index', [], Response::HTTP_SEE_OTHER);
    }
}
