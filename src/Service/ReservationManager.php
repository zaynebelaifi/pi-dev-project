<?php
namespace App\Service;

use App\Entity\Reservation;

class ReservationManager
{
    public function validate(Reservation $reservation): bool
    {
        if ($reservation->getNumberOfGuests() <= 0) {
            throw new \InvalidArgumentException('Number of guests must be greater than zero');
        }

        if ($reservation->getReservationDate() < new \DateTime('today')) {
            throw new \InvalidArgumentException('Reservation date cannot be in the past');
        }

        return true;
    }
}