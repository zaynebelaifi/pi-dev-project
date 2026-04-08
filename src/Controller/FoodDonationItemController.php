<?php

namespace App\Controller;

use App\Entity\FoodDonationItem;
use App\Form\FoodDonationItemType;
use App\Repository\FoodDonationItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/food/donation/item')]
final class FoodDonationItemController extends AbstractController
{
    #[Route(name: 'app_food_donation_item_index', methods: ['GET'])]
    public function index(FoodDonationItemRepository $foodDonationItemRepository): Response
    {
        return $this->render('food_donation_item/index.html.twig', [
            'food_donation_items' => $foodDonationItemRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_food_donation_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $foodDonationItem = new FoodDonationItem();
        $form = $this->createForm(FoodDonationItemType::class, $foodDonationItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($foodDonationItem);
            $entityManager->flush();

            return $this->redirectToRoute('app_food_donation_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('food_donation_item/new.html.twig', [
            'food_donation_item' => $foodDonationItem,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_item_show', methods: ['GET'])]
    public function show(FoodDonationItem $foodDonationItem): Response
    {
        return $this->render('food_donation_item/show.html.twig', [
            'food_donation_item' => $foodDonationItem,
        ]);
    }

    #[Route('/{donation_event_id}/edit', name: 'app_food_donation_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FoodDonationItem $foodDonationItem, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FoodDonationItemType::class, $foodDonationItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_food_donation_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('food_donation_item/edit.html.twig', [
            'food_donation_item' => $foodDonationItem,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_item_delete', methods: ['POST'])]
    public function delete(Request $request, FoodDonationItem $foodDonationItem, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$foodDonationItem->getDonation_event_id(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($foodDonationItem);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_food_donation_item_index', [], Response::HTTP_SEE_OTHER);
    }
}
