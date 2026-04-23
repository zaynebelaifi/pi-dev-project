<?php

namespace App\Form\Model;

use App\Entity\FoodDonationEvent;
use Symfony\Component\Validator\Constraints as Assert;

class EventItemAssignmentData
{
    #[Assert\NotNull(message: 'Please select an event.')]
    public ?FoodDonationEvent $event = null;

    /**
     * @var EventItemAssignmentLineData[]
     */
    #[Assert\Count(min: 1, minMessage: 'Please add at least one assigned item.')]
    #[Assert\Valid]
    public array $lines = [];
}
