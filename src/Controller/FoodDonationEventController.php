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
    public function index(Request $request, FoodDonationEventRepository $foodDonationEventRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $search = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', '');
        $sort = $request->query->get('sort', 'event_date');
        $direction = $request->query->get('direction', 'asc');

        return $this->render('admin/food_donation_event/index.html.twig', [
            'food_donation_events' => $foodDonationEventRepository->findFilteredEvents($search, $status, $sort, $direction),
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'app_food_donation_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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
            $entityManager->persist($foodDonationEvent);
            $entityManager->flush();

            $this->addFlash('success', 'Donation event created successfully.');
            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
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

        return $this->render('admin/food_donation_event/show.html.twig', [
            'food_donation_event' => $foodDonationEvent,
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
            $entityManager->flush();

            $this->addFlash('success', 'Donation event updated successfully.');
            return $this->redirectToRoute('app_food_donation_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/food_donation_event/edit.html.twig', [
            'food_donation_event' => $foodDonationEvent,
            'form' => $form,
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

    private function denyUnlessAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }
}
