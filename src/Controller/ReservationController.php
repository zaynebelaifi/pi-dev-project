<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\RestaurantTable;
use App\Repository\ReservationRepository;
use App\Repository\RestaurantTableRepository;
use App\Repository\User1Repository;
use App\Service\ReservationWeatherService;
use App\Service\SmartTableMatcher;
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
    public function book(
        Request $request,
        EntityManagerInterface $em,
        RestaurantTableRepository $tableRepo,
        ReservationRepository $reservationRepo,
        User1Repository $user1Repository,
        SmartTableMatcher $smartTableMatcher,
    ): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Invalid request data.'], 400);
        }

        $guestCount = (int) ($data['guests'] ?? 0);
        if ($guestCount < 1) {
            return $this->json(['success' => false, 'error' => 'Please select a valid number of guests.'], 400);
        }

        $dateInput = (string) ($data['date'] ?? '');
        $timeInput = (string) ($data['time'] ?? '');
        $occasion = (string) ($data['occasion'] ?? '');
        $mobilityNeeds = (bool) ($data['mobility_needs'] ?? false);

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

        $tableId = (int) ($data['table_id'] ?? 0);
        $table = $tableId > 0 ? $tableRepo->find($tableId) : null;

        if (!$table instanceof RestaurantTable) {
            $clientId = (int) $request->getSession()->get('user_id', 0);
            $recommendation = $smartTableMatcher->recommend(
                date: $reservationDate,
                time: $reservationTime,
                guests: $guestCount,
                occasion: $occasion,
                mobilityNeeds: $mobilityNeeds,
                clientId: $clientId > 0 ? $clientId : null,
            );

            if (!$recommendation) {
                return $this->json([
                    'success' => false,
                    'error' => 'No table matches your preferences for that time. Please adjust the time or party size.',
                ], 409);
            }

            $table = $recommendation['table'];
        }

        if ($table->getStatus() !== 'AVAILABLE') {
            return $this->json(['success' => false, 'error' => 'Selected table is no longer available. Please choose another.'], 409);
        }

        if ($reservationRepo->isTableBookedAt((int) $table->getTableId(), $reservationDate, $reservationTime)) {
            return $this->json([
                'success' => false,
                'error' => 'This table was just booked at that time. Please try another suggestion.',
            ], 409);
        }

        if ($guestCount > $table->getCapacity()) {
            return $this->json([
                'success' => false,
                'error' => sprintf('Table #%d only seats %d guests.', $table->getTableId(), $table->getCapacity()), // ✅
            ], 400);
        }

        $clientId = $this->resolveReservationClientId($request, $user1Repository);
        if ($clientId === null) {
            return $this->json([
                'success' => false,
                'error' => 'Reservation profile not found. Please re-login or contact support.',
            ], 400);
        }

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

    private function resolveReservationClientId(Request $request, User1Repository $user1Repository): ?int
    {
        $session = $request->getSession();

        $sessionUserId = (int) $session->get('user_id', 0);
        if ($sessionUserId > 0) {
            $legacyById = $user1Repository->find($sessionUserId);
            if ($legacyById !== null && $legacyById->getId() !== null) {
                return (int) $legacyById->getId();
            }
        }

        $sessionEmail = strtolower(trim((string) $session->get('user_email', '')));
        if ($sessionEmail !== '') {
            $legacyByEmail = $user1Repository->createQueryBuilder('u')
                ->andWhere('LOWER(u.email) = :email')
                ->setParameter('email', $sessionEmail)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($legacyByEmail !== null && $legacyByEmail->getId() !== null) {
                return (int) $legacyByEmail->getId();
            }
        }

        return null;
    }

    #[Route('/smart-match', name: 'app_reservation_smart_match', methods: ['POST'])]
    public function smartMatch(Request $request, SmartTableMatcher $smartTableMatcher): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Invalid request data.'], 400);
        }

        $dateInput = (string) ($data['date'] ?? '');
        $timeInput = (string) ($data['time'] ?? '');
        $guests = (int) ($data['guests'] ?? 0);
        $occasion = (string) ($data['occasion'] ?? '');
        $mobilityNeeds = (bool) ($data['mobility_needs'] ?? false);

        $reservationDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
        $reservationTime = \DateTimeImmutable::createFromFormat('!H:i', $timeInput);

        if (!$reservationDate || !$reservationTime || $guests < 1) {
            return $this->json([
                'success' => false,
                'error' => 'Please provide date, time and number of guests for smart matching.',
            ], 400);
        }

        $clientId = (int) $request->getSession()->get('user_id', 0);
        $recommendations = $smartTableMatcher->recommendRanked(
            date: $reservationDate,
            time: $reservationTime,
            guests: $guests,
            occasion: $occasion,
            mobilityNeeds: $mobilityNeeds,
            clientId: $clientId > 0 ? $clientId : null,
            limit: 3,
        );

        if ($recommendations === []) {
            return $this->json([
                'success' => false,
                'error' => 'No suitable table found for the selected constraints.',
            ], 404);
        }

        $recommendation = $recommendations[0];

        /** @var RestaurantTable $table */
        $table = $recommendation['table'];

        $alternatives = [];
        foreach ($recommendations as $candidate) {
            /** @var RestaurantTable $candidateTable */
            $candidateTable = $candidate['table'];
            $alternatives[] = [
                'table_id' => $candidateTable->getTableId(),
                'capacity' => $candidateTable->getCapacity(),
                'score' => $candidate['score'],
                'confidence' => $candidate['confidence'] ?? 'Medium',
                'explanation' => implode(' ', array_slice($candidate['reasons'] ?? [], 0, 2)),
            ];
        }

        $weatherContext = null;
        if (isset($recommendation['weather']) && is_array($recommendation['weather'])) {
            $weatherContext = [
                'city' => $recommendation['weather']['city'] ?? 'Tunis',
                'main' => $recommendation['weather']['main'] ?? null,
                'description' => $recommendation['weather']['description'] ?? null,
                'temp' => $recommendation['weather']['temp'] ?? null,
            ];
        }

        return $this->json([
            'success' => true,
            'table_id' => $table->getTableId(),
            'capacity' => $table->getCapacity(),
            'score' => $recommendation['score'],
            'confidence' => $recommendation['confidence'] ?? 'Medium',
            'reasons' => $recommendation['reasons'],
            'explanation' => implode(' ', array_slice($recommendation['reasons'], 0, 2)),
            'alternatives' => $alternatives,
            'weather' => $weatherContext,
        ]);
    }

    #[Route('/weather/day', name: 'app_reservation_weather_day', methods: ['GET'])]
    public function weatherDay(Request $request, ReservationWeatherService $reservationWeatherService): Response
    {
        $dateInput = trim((string) $request->query->get('date', ''));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);

        if (!$date || $dateInput === '') {
            return $this->json([
                'success' => false,
                'error' => 'Please provide a valid date in Y-m-d format.',
            ], 400);
        }

        $weather = $reservationWeatherService->getDailyState($date);
        if ($weather === null) {
            return $this->json([
                'success' => false,
                'error' => 'Weather forecast is unavailable for that day.',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'weather' => $weather,
        ]);
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