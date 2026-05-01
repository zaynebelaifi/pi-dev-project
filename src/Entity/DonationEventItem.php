<?php

namespace App\Entity;

use App\Repository\DonationEventItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DonationEventItemRepository::class)]
#[ORM\Table(name: 'donation_event_item')]
#[ORM\UniqueConstraint(name: 'uniq_event_item_pair', columns: ['event_id', 'item_id'])]
class DonationEventItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FoodDonationEvent::class, inversedBy: 'donationEventItems')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'donation_event_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Event is required.')]
    private ?FoodDonationEvent $event = null;

    #[ORM\ManyToOne(targetEntity: Dish::class, inversedBy: 'donationEventItems')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Item is required.')]
    private ?Dish $item = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive(message: 'Quantity must be greater than zero.')]
    private int $quantity = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?FoodDonationEvent
    {
        return $this->event;
    }

    public function setEvent(?FoodDonationEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getItem(): ?Dish
    {
        return $this->item;
    }

    public function setItem(?Dish $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }
}
