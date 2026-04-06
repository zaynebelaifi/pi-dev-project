<?php

namespace App\Controller;

use App\Entity\DeliveryMan;
use App\Form\DeliveryManType;
use App\Repository\DeliveryManRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/delivery/man')]
final class DeliveryManController extends AbstractController
{
    #[Route(name: 'app_delivery_man_index', methods: ['GET'])]
    public function index(DeliveryManRepository $deliveryManRepository): Response
    {
        return $this->render('delivery_man/index.html.twig', [
            'delivery_men' => $deliveryManRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_delivery_man_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $deliveryMan = new DeliveryMan();
        $form = $this->createForm(DeliveryManType::class, $deliveryMan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($deliveryMan);
            $entityManager->flush();

            return $this->redirectToRoute('app_delivery_man_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('delivery_man/new.html.twig', [
            'delivery_man' => $deliveryMan,
            'form' => $form,
        ]);
    }

    #[Route('/{delivery_man_id}', name: 'app_delivery_man_show', methods: ['GET'])]
    public function show(DeliveryMan $deliveryMan): Response
    {
        return $this->render('delivery_man/show.html.twig', [
            'delivery_man' => $deliveryMan,
        ]);
    }

    #[Route('/{delivery_man_id}/edit', name: 'app_delivery_man_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DeliveryMan $deliveryMan, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DeliveryManType::class, $deliveryMan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_delivery_man_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('delivery_man/edit.html.twig', [
            'delivery_man' => $deliveryMan,
            'form' => $form,
        ]);
    }

    #[Route('/{delivery_man_id}', name: 'app_delivery_man_delete', methods: ['POST'])]
    public function delete(Request $request, DeliveryMan $deliveryMan, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$deliveryMan->getDelivery_man_id(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($deliveryMan);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_delivery_man_index', [], Response::HTTP_SEE_OTHER);
    }
}
