<?php

namespace App\Event;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class EventUpdatedEvent extends Event
{
    private bool $smsDispatchSuccessful = true;
    private int $smsRecipientCount = 0;
    private int $smsSentCount = 0;
    private ?string $smsErrorMessage = null;

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

    public function markSmsDispatchResult(bool $successful, int $recipientCount, int $sentCount, ?string $errorMessage = null): void
    {
        $this->smsDispatchSuccessful = $successful;
        $this->smsRecipientCount = max(0, $recipientCount);
        $this->smsSentCount = max(0, $sentCount);
        $this->smsErrorMessage = $errorMessage;
    }

    public function isSmsDispatchSuccessful(): bool
    {
        return $this->smsDispatchSuccessful;
    }

    public function getSmsRecipientCount(): int
    {
        return $this->smsRecipientCount;
    }

    public function getSmsSentCount(): int
    {
        return $this->smsSentCount;
    }

    public function getSmsErrorMessage(): ?string
    {
        return $this->smsErrorMessage;
    }
}
