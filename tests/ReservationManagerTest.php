<?php

namespace App\Tests\Service;

use App\Entity\Reservation;
use App\Service\ReservationManager;
use PHPUnit\Framework\TestCase;

class ReservationManagerTest extends TestCase
{
    public function testValidReservation()
    {
        $reservation = new Reservation();
        $reservation->setNumberOfGuests(4);
        $reservation->setReservationDate(new \DateTime('tomorrow'));

        $manager = new ReservationManager();

        $this->assertTrue($manager->validate($reservation));
    }

    public function testReservationWithZeroGuests()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = new Reservation();
        $reservation->setNumberOfGuests(0);
        $reservation->setReservationDate(new \DateTime('tomorrow'));

        $manager = new ReservationManager();
        $manager->validate($reservation);
    }

    public function testReservationWithPastDate()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = new Reservation();
        $reservation->setNumberOfGuests(2);
        $reservation->setReservationDate(new \DateTime('yesterday'));

        $manager = new ReservationManager();
        $manager->validate($reservation);
    }
}