<?php

namespace App\Controller;

use App\Entity\RestaurantTable;
use App\Form\RestaurantTableType;
use App\Repository\RestaurantTableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RestaurantTableController extends AbstractController
{
    #[Route('/tables', name: 'table_index')]
    public function index(Request $request, RestaurantTableRepository $repo): Response
    {
        $search    = $request->query->get('search', '');
        $sort      = $request->query->get('sort', 'table_id');
        $direction = $request->query->get('direction', 'ASC');

        $tables    = $repo->searchAndSort($search, $sort, $direction);
        $available = $repo->countByStatus('available');
        $occupied  = $repo->countOccupied();

        return $this->render('restaurant_table/index.html.twig', [
            'tables'    => $tables,
            'total'     => count($tables),
            'available' => $available,
            'occupied'  => $occupied,
            'search'    => $search,
            'sort'      => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/tables/new', name: 'table_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $table = new RestaurantTable();
        $form  = $this->createForm(RestaurantTableType::class, $table);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($table);
            $em->flush();
            return $this->redirectToRoute('table_index');
        }

        return $this->render('restaurant_table/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tables/{id}/edit', name: 'table_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RestaurantTable $table, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RestaurantTableType::class, $table);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('table_index');
        }

        return $this->render('restaurant_table/edit.html.twig', [
            'form'  => $form->createView(),
            'table' => $table,
        ]);
    }

    #[Route('/tables/{id}/toggle', name: 'table_toggle', methods: ['POST'])]
    public function toggle(Request $request, RestaurantTable $table, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $table->getTableId(), $request->request->get('_token'))) { // ✅
            $newStatus = $table->getStatus() === 'AVAILABLE' ? 'OCCUPIED' : 'AVAILABLE'; // ✅ uppercase to match your DB values
            $table->setStatus($newStatus);
            $em->flush();
        }

        return $this->redirectToRoute('table_index');
    }

    #[Route('/tables/{id}/delete', name: 'table_delete', methods: ['POST'])]
    public function delete(Request $request, RestaurantTable $table, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $table->getTableId(), $request->request->get('_token'))) { // ✅
            $em->remove($table);
            $em->flush();
        }

        return $this->redirectToRoute('table_index');
    }

    #[Route('/api/tables/available', name: 'api_tables_available', methods: ['GET'])]
    public function available(RestaurantTableRepository $repo): Response
    {
        $tables = $repo->findBy(['status' => 'AVAILABLE']); // ✅ uppercase to match DB

        $data = array_map(fn($t) => [
            'id'       => $t->getTableId(),  // ✅
            'capacity' => $t->getCapacity(),
            'status'   => $t->getStatus(),
        ], $tables);

        return $this->json($data);
    }
}