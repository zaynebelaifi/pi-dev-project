<?php

namespace App\Event;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class EventCreatedEvent extends Event
{
    public function __construct(
        private readonly FoodDonationEvent $event,
        private readonly ?User $organizer = null,
    ) {
    }

    public function getEvent(): FoodDonationEvent
    {
        return $this->event;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }
}
