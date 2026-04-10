<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\RestaurantTable;
use App\Repository\ReservationRepository;
use App\Repository\RestaurantTableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route('', name: 'app_reservation_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $repo): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search    = trim((string) $request->query->get('search', ''));
        $sort      = $request->query->get('sort', 'reservationDate');
        $direction = $request->query->get('direction', 'DESC');

        return $this->render('Reservation/index.html.twig', [
            'reservations' => $repo->searchAndSort($search, $sort, $direction),
            'search'       => $search,
            'sort'         => $sort,
            'direction'    => $direction,
            'confirmed'    => $repo->countByStatus('CONFIRMED'),
            'cancelled'    => $repo->countByStatus('CANCELLED'),
        ]);
    }

    #[Route('/book', name: 'app_reservation_book', methods: ['POST'])]
    public function book(Request $request, EntityManagerInterface $em, RestaurantTableRepository $tableRepo): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Invalid request data.'], 400);
        }

        $tableId = (int) ($data['table_id'] ?? 0);
        if ($tableId === 0) {
            return $this->json(['success' => false, 'error' => 'Please select a table.'], 400);
        }

        $table = $tableRepo->find($tableId);
        if (!$table || $table->getStatus() !== 'AVAILABLE') {
            return $this->json(['success' => false, 'error' => 'Selected table is no longer available. Please choose another.'], 409);
        }

        $guestCount = (int) ($data['guests'] ?? 0);
        if ($guestCount < 1) {
            return $this->json(['success' => false, 'error' => 'Please select a valid number of guests.'], 400);
        }

        if ($guestCount > $table->getCapacity()) {
            return $this->json([
                'success' => false,
                'error' => sprintf('Table #%d only seats %d guests.', $table->getTableId(), $table->getCapacity()), // ✅
            ], 400);
        }

        $dateInput = (string) ($data['date'] ?? '');
        $timeInput = (string) ($data['time'] ?? '');

        $reservationDate     = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
        $reservationTime     = \DateTimeImmutable::createFromFormat('!H:i', $timeInput);
        $reservationDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i', sprintf('%s %s', $dateInput, $timeInput));

        if (!$reservationDate || !$reservationTime || !$reservationDateTime) {
            return $this->json(['success' => false, 'error' => 'Please choose a valid reservation date and time.'], 400);
        }

        $openingTime = '08:00';
        $lastArrival = '23:30';

        if ($timeInput < $openingTime || $timeInput > $lastArrival) {
            return $this->json([
                'success' => false,
                'error' => sprintf('Reservations are available between %s and %s.', $openingTime, $lastArrival),
            ], 400);
        }

        if ($reservationDateTime < new \DateTimeImmutable('now')) {
            return $this->json(['success' => false, 'error' => 'Please choose a reservation time in the future.'], 400);
        }

        $clientId = (int) $request->getSession()->get('user_id', 0);

        try {
            $reservation = new Reservation();
            $reservation->setClientId($clientId);
            $reservation->setTable($table);
            $reservation->setReservationDate($reservationDate);
            $reservation->setReservationTime($reservationTime);
            $reservation->setNumberOfGuests($guestCount);
            $reservation->setStatus('CONFIRMED');

            $table->setStatus('RESERVED');
            $em->persist($table);
            $em->persist($reservation);
            $em->flush();

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(\App\Form\ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($reservation->getStatus() === 'CANCELLED') {
                $table = $reservation->getTable();
                if ($table) {
                    $table->setStatus('AVAILABLE');
                    $em->persist($table);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Reservation updated.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('Reservation/edit.html.twig', [
            'form'        => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getReservationId(), $request->request->get('_token'))) { // ✅
            $table = $reservation->getTable();
            if ($table) {
                $table->setStatus('AVAILABLE');
                $em->persist($table);
            }
            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Reservation deleted.');
        }
        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$reservation->getReservationId(), $request->request->get('_token'))) { // ✅
            $reservation->setStatus('CANCELLED');
            $table = $reservation->getTable();
            if ($table) {
                $table->setStatus('AVAILABLE');
                $em->persist($table);
            }
            $em->flush();
            $this->addFlash('success', 'Reservation cancelled.');
        }
        return $this->redirectToRoute('app_reservation_index');
    }
}