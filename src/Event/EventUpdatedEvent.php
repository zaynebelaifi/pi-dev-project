<?php

namespace App\Event;

use App\Entity\FoodDonationEvent;

final class EventUpdatedEvent
{
    private bool $smsSent = false;
    private int $recipientCount = 0;
    private int $sentCount = 0;
    private ?string $error = null;

    public function __construct(private readonly FoodDonationEvent $event)
    {
    }

    public function getEvent(): FoodDonationEvent
    {
        return $this->event;
    }

    public function markSmsDispatchResult(bool $smsSent, int $recipientCount, int $sentCount, ?string $error): void
    {
        $this->smsSent = $smsSent;
        $this->recipientCount = max(0, $recipientCount);
        $this->sentCount = max(0, $sentCount);
        $this->error = $error;
    }

    public function isSmsSent(): bool
    {
        return $this->smsSent;
    }

    public function getRecipientCount(): int
    {
        return $this->recipientCount;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
