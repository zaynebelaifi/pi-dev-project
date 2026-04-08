<?php

namespace App\Controller;

use App\Entity\FoodDonationEvent;
use App\Form\FoodDonationEventType;
use App\Repository\FoodDonationEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/food/donation/event')]
final class FoodDonationEventController extends AbstractController
{
    #[Route(name: 'app_food_donation_event_index', methods: ['GET'])]
    public function index(FoodDonationEventRepository $foodDonationEventRepository): Response
    {
        return $this->render('food_donation_event/index.html.twig', [
            'food_donation_events' => $foodDonationEventRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_food_donation_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $foodDonationEvent = new FoodDonationEvent();
        $form = $this->createForm(FoodDonationEventType::class, $foodDonationEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($foodDonationEvent);
            $entityManager->flush();

            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('food_donation_event/new.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_event_show', methods: ['GET'])]
    public function show(FoodDonationEvent $foodDonationEvent): Response
    {
        return $this->render('food_donation_event/show.html.twig', [
            'food_donation_event' => $foodDonationEvent,
        ]);
    }

    #[Route('/{donation_event_id}/edit', name: 'app_food_donation_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FoodDonationEvent $foodDonationEvent, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FoodDonationEventType::class, $foodDonationEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('food_donation_event/edit.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{donation_event_id}', name: 'app_food_donation_event_delete', methods: ['POST'])]
    public function delete(Request $request, FoodDonationEvent $foodDonationEvent, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$foodDonationEvent->getDonation_event_id(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($foodDonationEvent);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
    }
}
