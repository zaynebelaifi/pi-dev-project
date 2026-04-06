<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FoodDonationEventRepository;

#[ORM\Entity(repositoryClass: FoodDonationEventRepository::class)]
#[ORM\Table(name: 'food_donation_event')]
class FoodDonationEvent
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

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $event_date = null;

    public function getEvent_date(): ?\DateTimeInterface
    {
        return $this->event_date;
    }

    public function setEvent_date(\DateTimeInterface $event_date): self
    {
        $this->event_date = $event_date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $total_quantity = null;

    public function getTotal_quantity(): ?int
    {
        return $this->total_quantity;
    }

    public function setTotal_quantity(int $total_quantity): self
    {
        $this->total_quantity = $total_quantity;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $charity_name = null;

    public function getCharity_name(): ?string
    {
        return $this->charity_name;
    }

    public function setCharity_name(string $charity_name): self
    {
        $this->charity_name = $charity_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $delivery_id = null;

    public function getDelivery_id(): ?int
    {
        return $this->delivery_id;
    }

    public function setDelivery_id(?int $delivery_id): self
    {
        $this->delivery_id = $delivery_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $calendar_event_id = null;

    public function getCalendar_event_id(): ?string
    {
        return $this->calendar_event_id;
    }

    public function setCalendar_event_id(?string $calendar_event_id): self
    {
        $this->calendar_event_id = $calendar_event_id;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $updated_at = null;

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

}
