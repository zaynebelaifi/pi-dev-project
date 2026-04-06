<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Form\DeliveryType;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/delivery')]
final class DeliveryController extends AbstractController
{
    #[Route(name: 'app_delivery_index', methods: ['GET'])]
    public function index(DeliveryRepository $deliveryRepository): Response
    {
        return $this->render('delivery/index.html.twig', [
            'deliveries' => $deliveryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_delivery_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $delivery = new Delivery();
        $form = $this->createForm(DeliveryType::class, $delivery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($delivery);
            $entityManager->flush();

            return $this->redirectToRoute('app_delivery_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('delivery/new.html.twig', [
            'delivery' => $delivery,
            'form' => $form,
        ]);
    }

    #[Route('/{delivery_id}', name: 'app_delivery_show', methods: ['GET'])]
    public function show(Delivery $delivery): Response
    {
        return $this->render('delivery/show.html.twig', [
            'delivery' => $delivery,
        ]);
    }

    #[Route('/{delivery_id}/edit', name: 'app_delivery_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
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

    #[Route('/{delivery_id}', name: 'app_delivery_delete', methods: ['POST'])]
    public function delete(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$delivery->getDelivery_id(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($delivery);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_delivery_index', [], Response::HTTP_SEE_OTHER);
    }
}
