<?php

namespace App\Form\Model;

use App\Entity\Dish;
use Symfony\Component\Validator\Constraints as Assert;

class EventItemAssignmentLineData
{
    #[Assert\NotNull(message: 'Please choose an item.')]
    public ?Dish $item = null;

    #[Assert\Positive(message: 'Quantity must be greater than zero.')]
    public int $quantity = 1;

    public ?int $assignmentId = null;
}
