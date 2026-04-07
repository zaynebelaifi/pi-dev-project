<?php

namespace App\Controller;

use App\Entity\FleetCar;
use App\Form\FleetCarType;
use App\Repository\FleetCarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fleet/car')]
final class FleetCarController extends AbstractController
{
    #[Route(name: 'app_fleet_car_index', methods: ['GET'])]
    public function index(FleetCarRepository $fleetCarRepository): Response
    {
        return $this->render('fleet_car/index.html.twig', [
            'fleet_cars' => $fleetCarRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_fleet_car_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $fleetCar = new FleetCar();
        $form = $this->createForm(FleetCarType::class, $fleetCar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($fleetCar);
            $entityManager->flush();

            return $this->redirectToRoute('app_fleet_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fleet_car/new.html.twig', [
            'fleet_car' => $fleetCar,
            'form' => $form,
        ]);
    }

    #[Route('/{car_id}', name: 'app_fleet_car_show', methods: ['GET'])]
    public function show(FleetCar $fleetCar): Response
    {
        return $this->render('fleet_car/show.html.twig', [
            'fleet_car' => $fleetCar,
        ]);
    }

    #[Route('/{car_id}/edit', name: 'app_fleet_car_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FleetCar $fleetCar, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FleetCarType::class, $fleetCar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_fleet_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fleet_car/edit.html.twig', [
            'fleet_car' => $fleetCar,
            'form' => $form,
        ]);
    }

    #[Route('/{car_id}', name: 'app_fleet_car_delete', methods: ['POST'])]
    public function delete(Request $request, FleetCar $fleetCar, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fleetCar->getCar_id(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($fleetCar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_fleet_car_index', [], Response::HTTP_SEE_OTHER);
    }
}
