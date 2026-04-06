<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FoodDonationItemRepository;

#[ORM\Entity(repositoryClass: FoodDonationItemRepository::class)]
#[ORM\Table(name: 'food_donation_items')]
class FoodDonationItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $donation_event_id = null;

    public function getDonation_event_id(): ?int
    {
        return $this->donation_event_id;
    }

    public function setDonation_event_id(int $donation_event_id): self
    {
        $this->donation_event_id = $donation_event_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $item_id = null;

    public function getItem_id(): ?int
    {
        return $this->item_id;
    }

    public function setItem_id(int $item_id): self
    {
        $this->item_id = $item_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $quantity = null;

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

}
