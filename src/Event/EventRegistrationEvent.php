<?php

namespace App\Event;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class EventRegistrationEvent extends Event
{
    private bool $smsSent = false;

    public function __construct(
        private readonly User $user,
        private readonly FoodDonationEvent $event,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEvent(): FoodDonationEvent
    {
        return $this->event;
    }

    public function markSmsSent(bool $smsSent): void
    {
        $this->smsSent = $smsSent;
    }

    public function isSmsSent(): bool
    {
        return $this->smsSent;
    }
}
